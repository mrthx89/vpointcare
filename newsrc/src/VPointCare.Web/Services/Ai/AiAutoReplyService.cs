using System.Net.Http.Json;
using System.Text.Json;
using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;
using VPointCare.Web.Data.Entities;
using VPointCare.Web.Services.Waha;

namespace VPointCare.Web.Services.Ai;

public class AiAutoReplyService(
    VPointCareDbContext dbContext,
    WahaSenderService wahaSender,
    HttpClient httpClient,
    IConfiguration configuration,
    ILogger<AiAutoReplyService> logger)
{
    private static readonly HashSet<string> StopWords = new(StringComparer.OrdinalIgnoreCase)
    {
        "yang", "dari", "untuk", "dengan", "atau", "kami", "saya", "anda", "bapak", "ibu", "halo", "terima", "kasih", "pesan", "customer"
    };

    public async Task<AiAutoReplyResult?> HandleIncomingChatAsync(Guid chatId, CancellationToken cancellationToken = default)
    {
        var settings = await SettingsAsync(cancellationToken);
        if (settings is null || !settings.AutoReplyAktif)
        {
            return null;
        }

        var chat = await LoadChatContextAsync(chatId, cancellationToken);
        if (chat is null)
        {
            return null;
        }

        var latestIncoming = await dbContext.TChatDSet
            .AsNoTracking()
            .Where(x => x.IdChat == chatId && x.ArahPesan == "Masuk" && x.DikirimOlehCustomer && x.IsiPesan != null)
            .OrderByDescending(x => x.TglPesan)
            .FirstOrDefaultAsync(cancellationToken);

        if (latestIncoming is null)
        {
            return null;
        }

        var alreadyAnswered = await dbContext.TChatDSet
            .AsNoTracking()
            .AnyAsync(x => x.IdChat == chatId
                && x.ArahPesan == "Keluar"
                && x.DihasilkanOlehAi
                && x.TglPesan >= latestIncoming.TglPesan, cancellationToken);

        if (alreadyAnswered)
        {
            return AiAutoReplyResult.Skip("Pesan terakhir sudah dijawab AI.");
        }

        var decision = await ReplyDecisionAsync(settings, chat, cancellationToken);
        if (!decision.Allow)
        {
            return AiAutoReplyResult.Skip(decision.Reason);
        }

        var requestId = Guid.NewGuid();
        var prompt = await BuildPromptAsync(settings, chat, decision.Template, cancellationToken);
        var reply = decision.Template;
        object? responsePayload = null;
        var requestStatus = "Selesai";
        string? error = null;
        var usedAi = false;
        var now = DateTime.UtcNow;

        dbContext.TAiPermintaanSet.Add(new TAiPermintaan
        {
            Id = requestId,
            JenisPermintaan = "Auto Reply WhatsApp",
            ProviderAi = string.IsNullOrWhiteSpace(settings.ProviderAi) ? "OpenAI" : settings.ProviderAi,
            ModelAi = settings.ModelAi ?? configuration[$"{ProviderSection(settings.ProviderAi)}:Model"],
            IdChat = chatId,
            PromptRingkas = Limit(prompt, 2000),
            PromptJson = JsonSerializer.Serialize(new { keputusan = decision, prompt }),
            StatusPermintaan = "Diproses",
            TglMulai = now,
            TglBuat = now
        });
        await dbContext.SaveChangesAsync(cancellationToken);

        try
        {
            var generated = await GenerateReplyAsync(settings, prompt, cancellationToken);
            if (generated is not null && !string.IsNullOrWhiteSpace(generated.Text))
            {
                reply = generated.Text;
                responsePayload = generated.Payload;
                usedAi = true;
            }
        }
        catch (Exception exception)
        {
            requestStatus = "Gagal Fallback";
            error = exception.Message;
            logger.LogError(exception, "AI auto reply gagal untuk chat {ChatId}", chatId);
        }

        if (!usedAi && string.IsNullOrWhiteSpace(error))
        {
            error = $"AI tidak dipanggil karena API key kosong untuk provider {settings.ProviderAi}.";
        }

        await dbContext.TAiPermintaanSet
            .Where(x => x.Id == requestId)
            .ExecuteUpdateAsync(setters => setters
                .SetProperty(x => x.StatusPermintaan, requestStatus)
                .SetProperty(x => x.TglSelesai, DateTime.UtcNow)
                .SetProperty(x => x.PesanError, error)
                .SetProperty(x => x.TglEdit, DateTime.UtcNow), cancellationToken);

        var responseId = Guid.NewGuid();
        dbContext.TAiResponSet.Add(new TAiRespon
        {
            Id = responseId,
            IdAiPermintaan = requestId,
            JenisRespon = decision.Mode,
            ResponRingkas = reply,
            ResponJson = JsonSerializer.Serialize(responsePayload ?? new { fallback = true, reason = error }),
            TglBuat = DateTime.UtcNow
        });
        await dbContext.SaveChangesAsync(cancellationToken);

        var delivery = await StoreReplyAsync(settings, chat, reply, responseId, decision.Mode, cancellationToken);

        await dbContext.TChatSet
            .Where(x => x.Id == chatId)
            .ExecuteUpdateAsync(setters => setters
                .SetProperty(x => x.AiSudahMenyapa, x => decision.Mode == "Sapaan Jam Kerja" || x.AiSudahMenyapa)
                .SetProperty(x => x.TglAutoReplyAiTerakhir, DateTime.UtcNow)
                .SetProperty(x => x.TglDibalasTerakhir, DateTime.UtcNow)
                .SetProperty(x => x.TglChatTerakhir, DateTime.UtcNow)
                .SetProperty(x => x.JumlahPesanBelumDibaca, 0)
                .SetProperty(x => x.TglEdit, DateTime.UtcNow), cancellationToken);

        return new AiAutoReplyResult(true, false, null, decision.Mode, delivery, responseId);
    }

    public async Task SendClosingMessageAsync(Guid chatId, CancellationToken cancellationToken = default)
    {
        var settings = await SettingsAsync(cancellationToken);
        var chat = await LoadChatContextAsync(chatId, cancellationToken);
        if (settings is null || chat is null)
        {
            return;
        }

        var prompt = await BuildPromptAsync(settings, chat, "Tutup percakapan ini dengan sopan dan profesional. Ucapkan terima kasih karena telah menghubungi VPoint Care, dan sampaikan bahwa sesi percakapan ini telah ditutup. Tanyakan apakah ada hal lain yang bisa dibantu untuk ke depannya. Jangan terlalu panjang.", cancellationToken);
        var requestId = Guid.NewGuid();
        var reply = "Terima kasih telah menghubungi VPoint Care. Sesi percakapan ini telah ditutup.";
        string? error = null;

        dbContext.TAiPermintaanSet.Add(new TAiPermintaan
        {
            Id = requestId,
            JenisPermintaan = "Tutup Chat",
            ProviderAi = string.IsNullOrWhiteSpace(settings.ProviderAi) ? "OpenAI" : settings.ProviderAi,
            ModelAi = settings.ModelAi ?? configuration[$"{ProviderSection(settings.ProviderAi)}:Model"],
            IdChat = chatId,
            PromptRingkas = Limit(prompt, 2000),
            PromptJson = JsonSerializer.Serialize(new { prompt }),
            StatusPermintaan = "Diproses",
            TglMulai = DateTime.UtcNow,
            TglBuat = DateTime.UtcNow
        });
        await dbContext.SaveChangesAsync(cancellationToken);

        try
        {
            var generated = await GenerateReplyAsync(settings, prompt, cancellationToken);
            if (generated is not null)
            {
                reply = generated.Text;
            }
        }
        catch (Exception exception)
        {
            error = exception.Message;
        }

        var closingStatus = error is null ? "Selesai" : "Gagal Fallback";
        await dbContext.TAiPermintaanSet
            .Where(x => x.Id == requestId)
            .ExecuteUpdateAsync(setters => setters
                .SetProperty(x => x.StatusPermintaan, closingStatus)
                .SetProperty(x => x.TglSelesai, DateTime.UtcNow)
                .SetProperty(x => x.PesanError, error)
                .SetProperty(x => x.TglEdit, DateTime.UtcNow), cancellationToken);

        var responseId = Guid.NewGuid();
        dbContext.TAiResponSet.Add(new TAiRespon
        {
            Id = responseId,
            IdAiPermintaan = requestId,
            JenisRespon = "Tutup Chat",
            ResponRingkas = reply,
            ResponJson = "{}",
            TglBuat = DateTime.UtcNow
        });
        await dbContext.SaveChangesAsync(cancellationToken);
        await StoreReplyAsync(settings, chat, reply, responseId, "Tutup Chat", cancellationToken);
    }

    private Task<MPengaturanAi?> SettingsAsync(CancellationToken cancellationToken)
    {
        return dbContext.MPengaturanAiSet.FirstOrDefaultAsync(x => x.KodePengaturan == "DEFAULT" && !x.NonAktif, cancellationToken);
    }

    private async Task<AiChatContext?> LoadChatContextAsync(Guid chatId, CancellationToken cancellationToken)
    {
        return await (
            from chat in dbContext.TChatSet.AsNoTracking()
            join session in dbContext.SesiWhatsapps.AsNoTracking() on chat.IdSesiWhatsapp equals session.Id into sessionJoin
            from session in sessionJoin.DefaultIfEmpty()
            join instansi in dbContext.MInstansiSet.AsNoTracking() on chat.IdInstansi equals instansi.Id into instansiJoin
            from instansi in instansiJoin.DefaultIfEmpty()
            join customer in dbContext.MCustomerSet.AsNoTracking() on chat.IdCustomer equals customer.Id into customerJoin
            from customer in customerJoin.DefaultIfEmpty()
            join groupChat in dbContext.MGrupWhatsappSet.AsNoTracking() on chat.IdGrupWhatsapp equals groupChat.Id into groupJoin
            from groupChat in groupJoin.DefaultIfEmpty()
            where chat.Id == chatId
            select new AiChatContext(
                chat.Id,
                chat.JenisChat,
                chat.NomorWhatsapp,
                chat.NamaKontak,
                chat.NamaGrupWhatsapp,
                chat.AutoReplyAiAktif,
                chat.AiSudahMenyapa,
                session == null ? "default" : session.KodeSesi,
                instansi == null ? null : instansi.NamaInstansi,
                customer == null ? null : customer.NamaCustomer,
                groupChat == null ? null : groupChat.IdGrupWaha))
            .FirstOrDefaultAsync(cancellationToken);
    }

    private async Task<AiReplyDecision> ReplyDecisionAsync(MPengaturanAi settings, AiChatContext chat, CancellationToken cancellationToken)
    {
        var holiday = await ActiveHolidayAsync(settings, cancellationToken);
        if (holiday is not null && settings.AutoReplyHariLibur)
        {
            return new(true, $"Hari libur: {holiday.Name}.", "Hari Libur", FormatHolidayTemplate(settings, holiday));
        }

        if (!InsideWorkingHour(settings) && settings.AutoReplyDiluarJamKerja)
        {
            return new(true, "Di luar jam kerja.", "Luar Jam Kerja", settings.TemplateDiluarJamKerja ?? DefaultOutsideTemplate());
        }

        if (chat.AutoReplyAiAktif || settings.AutoReplyJamKerjaBerlanjut)
        {
            return new(true, "Auto reply sesi aktif.", "Berlanjut", settings.TemplateFallback ?? DefaultFallbackTemplate());
        }

        if (settings.AutoReplyJamKerjaSapaan && !chat.AiSudahMenyapa)
        {
            return new(true, "Sapaan awal jam kerja.", "Sapaan Jam Kerja", settings.TemplateJamKerjaSapaan ?? DefaultGreetingTemplate());
        }

        return new(false, "Jam kerja aktif dan sesi tidak diset auto reply berlanjut.", "Skip", "");
    }

    private async Task<HolidayInfo?> ActiveHolidayAsync(MPengaturanAi settings, CancellationToken cancellationToken)
    {
        var today = LocalNow(settings.ZonaWaktu).Date;
        var holiday = await HolidayForDateAsync(today, cancellationToken);
        if (holiday is null)
        {
            return null;
        }

        return new HolidayInfo(holiday.NamaHariLibur, today, await NextWorkingDateAsync(settings, today, cancellationToken));
    }

    private Task<MHariLibur?> HolidayForDateAsync(DateTime date, CancellationToken cancellationToken)
    {
        return dbContext.MHariLiburSet
            .AsNoTracking()
            .Where(x => !x.NonAktif
                && (x.TanggalLibur.Date == date
                    || (x.BerlakuTahunan && x.TanggalLibur.Month == date.Month && x.TanggalLibur.Day == date.Day)))
            .OrderByDescending(x => x.BerlakuTahunan)
            .ThenBy(x => x.NamaHariLibur)
            .FirstOrDefaultAsync(cancellationToken);
    }

    private async Task<DateTime?> NextWorkingDateAsync(MPengaturanAi settings, DateTime fromDate, CancellationToken cancellationToken)
    {
        var workdays = ParseWorkdays(settings.HariKerja);
        var date = fromDate.AddDays(1);
        for (var attempt = 0; attempt < 60; attempt++, date = date.AddDays(1))
        {
            var isoDay = ((int)date.DayOfWeek + 6) % 7 + 1;
            if (!workdays.Contains(isoDay))
            {
                continue;
            }

            if (await HolidayForDateAsync(date, cancellationToken) is null)
            {
                return date;
            }
        }

        return null;
    }

    private bool InsideWorkingHour(MPengaturanAi settings)
    {
        var now = LocalNow(settings.ZonaWaktu);
        var isoDay = ((int)now.DayOfWeek + 6) % 7 + 1;
        return ParseWorkdays(settings.HariKerja).Contains(isoDay)
            && now.TimeOfDay >= settings.JamKerjaMulai
            && now.TimeOfDay <= settings.JamKerjaSelesai;
    }

    private async Task<string> BuildPromptAsync(MPengaturanAi settings, AiChatContext chat, string template, CancellationToken cancellationToken)
    {
        var limit = Math.Max(1, Math.Min(settings.BatasRiwayatPesan, 20));
        var rows = await dbContext.TChatDSet
            .AsNoTracking()
            .Where(x => x.IdChat == chat.Id)
            .OrderByDescending(x => x.TglPesan)
            .Take(limit)
            .OrderBy(x => x.TglPesan)
            .ToListAsync(cancellationToken);

        var messages = rows.Select(row =>
        {
            var speaker = row.ArahPesan == "Keluar"
                ? row.DihasilkanOlehAi ? "AI Agent" : "Customer Service"
                : row.PengirimNamaKontak ?? row.PengirimNomorWhatsapp ?? "Customer";
            return $"{speaker}: {(!string.IsNullOrWhiteSpace(row.IsiPesan) ? row.IsiPesan : "[pesan non-teks]")}";
        }).ToArray();

        var latestCustomerMessage = rows.LastOrDefault(x => x.ArahPesan == "Masuk" && !string.IsNullOrWhiteSpace(x.IsiPesan))?.IsiPesan ?? "";
        var customer = chat.NamaInstansi ?? chat.NamaCustomer ?? "Belum dipetakan";
        var knowledge = await RelevantKnowledgeAsync($"{latestCustomerMessage} {string.Join(' ', messages)} {customer}", cancellationToken);

        var parts = new[]
        {
            settings.PromptSistem,
            $"Konteks customer: {customer}",
            $"Jenis chat: {chat.JenisChat}",
            "Instruksi mode: gunakan template berikut hanya sebagai arah balasan, bukan kalimat yang harus diulang persis.",
            $"Template: {template}",
            string.IsNullOrWhiteSpace(knowledge) ? null : $"Pengetahuan internal yang boleh dipakai:\n{knowledge}",
            "Riwayat chat:",
            string.Join('\n', messages),
            "Buat satu balasan WhatsApp yang halus, ringkas, natural, dan siap dikirim. AI boleh mengimprovisasi susunan kalimat agar tidak kaku atau berulang, tetapi fakta, prosedur, harga, jadwal, dan janji layanan harus mengikuti pengetahuan internal atau riwayat chat. Jika informasi tidak tersedia, minta detail tambahan atau arahkan ke customer service tanpa mengarang."
        };

        return string.Join("\n\n", parts.Where(x => !string.IsNullOrWhiteSpace(x))).Trim();
    }

    private async Task<string> RelevantKnowledgeAsync(string context, CancellationToken cancellationToken)
    {
        var rows = await dbContext.MPengetahuanSet
            .AsNoTracking()
            .Where(x => !x.NonAktif)
            .OrderBy(x => x.JudulPengetahuan)
            .Take(100)
            .Select(x => new { x.JudulPengetahuan, x.IsiPengetahuan, x.Tag })
            .ToListAsync(cancellationToken);

        if (rows.Count == 0)
        {
            return "";
        }

        var tokens = context
            .ToLowerInvariant()
            .Split(new[] { ' ', ',', '.', ';', ':', '!', '?', '(', ')', '[', ']', '{', '}', '"', '\'', '/', '\\', '-' }, StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries)
            .Where(x => x.Length >= 4 && !StopWords.Contains(x))
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .ToArray();

        var scored = rows
            .Select(row =>
            {
                var haystack = $"{row.JudulPengetahuan} {row.Tag} {row.IsiPengetahuan}".ToLowerInvariant();
                var score = tokens.Count(token => haystack.Contains(token, StringComparison.OrdinalIgnoreCase));
                return new { score, row.JudulPengetahuan, row.IsiPengetahuan };
            })
            .Where(x => x.score > 0)
            .OrderByDescending(x => x.score)
            .Take(5)
            .Select(x => $"- {x.JudulPengetahuan}: {Limit(x.IsiPengetahuan, 900)}");

        return string.Join('\n', scored);
    }

    private async Task<GeneratedReply?> GenerateReplyAsync(MPengaturanAi settings, string prompt, CancellationToken cancellationToken)
    {
        var provider = string.IsNullOrWhiteSpace(settings.ProviderAi) ? "openai" : settings.ProviderAi.ToLowerInvariant();
        var apiKey = ApiKey(settings, provider);
        if (string.IsNullOrWhiteSpace(apiKey))
        {
            return null;
        }

        return provider switch
        {
            "deepseek" => await GenerateChatCompletionReplyAsync(settings, prompt, apiKey, "DeepSeek", cancellationToken),
            "openrouter" => await GenerateChatCompletionReplyAsync(settings, prompt, apiKey, "OpenRouter", cancellationToken),
            _ => await GenerateOpenAiReplyAsync(settings, prompt, apiKey, cancellationToken)
        };
    }

    private async Task<GeneratedReply?> GenerateOpenAiReplyAsync(MPengaturanAi settings, string prompt, string apiKey, CancellationToken cancellationToken)
    {
        var baseUrl = settings.BaseUrl ?? configuration["OpenAI:BaseUrl"] ?? "https://api.openai.com/v1/responses";
        var model = settings.ModelAi ?? configuration["OpenAI:Model"] ?? "gpt-5";
        using var request = new HttpRequestMessage(HttpMethod.Post, baseUrl)
        {
            Content = JsonContent.Create(new
            {
                model,
                instructions = settings.PromptSistem,
                input = prompt,
                store = true
            })
        };
        request.Headers.Authorization = new("Bearer", apiKey);
        request.Headers.Accept.ParseAdd("application/json");

        using var response = await httpClient.SendAsync(request, cancellationToken);
        var body = await response.Content.ReadAsStringAsync(cancellationToken);
        if (!response.IsSuccessStatusCode)
        {
            throw new InvalidOperationException($"OpenAI API gagal: HTTP {(int)response.StatusCode} - {body}");
        }

        using var document = JsonDocument.Parse(body);
        var text = ExtractOutputText(document.RootElement);
        return string.IsNullOrWhiteSpace(text) ? null : new GeneratedReply(text, JsonSerializer.Deserialize<object>(body));
    }

    private async Task<GeneratedReply?> GenerateChatCompletionReplyAsync(MPengaturanAi settings, string prompt, string apiKey, string provider, CancellationToken cancellationToken)
    {
        var section = provider;
        var baseUrl = ChatCompletionEndpoint(settings.BaseUrl ?? configuration[$"{section}:BaseUrl"] ?? "");
        var model = settings.ModelAi ?? configuration[$"{section}:Model"];
        using var request = new HttpRequestMessage(HttpMethod.Post, baseUrl)
        {
            Content = JsonContent.Create(new
            {
                model,
                messages = new[]
                {
                    new { role = "system", content = settings.PromptSistem ?? "Anda adalah AI Agent customer service yang menjawab singkat, sopan, dan jelas." },
                    new { role = "user", content = prompt }
                },
                stream = false
            })
        };
        request.Headers.Authorization = new("Bearer", apiKey);
        request.Headers.Accept.ParseAdd("application/json");

        if (provider == "OpenRouter")
        {
            var siteUrl = configuration["OpenRouter:SiteUrl"];
            var siteName = configuration["OpenRouter:SiteName"];
            if (!string.IsNullOrWhiteSpace(siteUrl)) request.Headers.TryAddWithoutValidation("HTTP-Referer", siteUrl);
            if (!string.IsNullOrWhiteSpace(siteName)) request.Headers.TryAddWithoutValidation("X-Title", siteName);
        }

        using var response = await httpClient.SendAsync(request, cancellationToken);
        var body = await response.Content.ReadAsStringAsync(cancellationToken);
        if (!response.IsSuccessStatusCode)
        {
            throw new InvalidOperationException($"{provider} API gagal: HTTP {(int)response.StatusCode} - {body}");
        }

        using var document = JsonDocument.Parse(body);
        var text = JsonPathString(document.RootElement, "choices.0.message.content");
        return string.IsNullOrWhiteSpace(text) ? null : new GeneratedReply(text, JsonSerializer.Deserialize<object>(body));
    }

    private async Task<AiDeliveryResult> StoreReplyAsync(MPengaturanAi settings, AiChatContext chat, string reply, Guid responseId, string mode, CancellationToken cancellationToken)
    {
        var status = "Draft Auto Reply AI";
        DateTime? sentAt = null;
        string? error = null;

        if (settings.KirimKeWaha)
        {
            var sent = await wahaSender.SendTextAsync(chat.KodeSesi ?? "default", await WahaChatIdAsync(chat, cancellationToken), reply, "WAHA_SEND_TEXT", cancellationToken);
            status = sent.Ok ? "Terkirim WAHA" : "Gagal WAHA";
            sentAt = sent.Ok ? DateTime.UtcNow : null;
            error = sent.Error;
        }

        dbContext.TChatDSet.Add(new TChatD
        {
            Id = Guid.NewGuid(),
            IdChat = chat.Id,
            IdAiRespon = responseId,
            ArahPesan = "Keluar",
            JenisPesan = "Teks",
            IsiPesan = reply,
            DikirimOlehCustomer = false,
            DihasilkanOlehAi = true,
            TglPesan = DateTime.UtcNow,
            TglDikirim = sentAt,
            StatusKirim = status,
            PesanError = error,
            TglBuat = DateTime.UtcNow
        });
        await dbContext.SaveChangesAsync(cancellationToken);

        return new AiDeliveryResult(settings.KirimKeWaha ? "WAHA" : "DraftLokal", status, mode, error);
    }

    private async Task<string?> LatestIncomingWahaChatIdAsync(Guid chatId, CancellationToken cancellationToken)
    {
        var payloadJson = await dbContext.TChatDSet
            .AsNoTracking()
            .Where(x => x.IdChat == chatId && x.ArahPesan == "Masuk" && x.PayloadJson != null)
            .OrderByDescending(x => x.TglPesan)
            .Select(x => x.PayloadJson)
            .FirstOrDefaultAsync(cancellationToken);

        if (string.IsNullOrWhiteSpace(payloadJson))
        {
            return null;
        }

        using var document = JsonDocument.Parse(payloadJson);
        foreach (var path in new[] { "chatId", "from", "from.id", "_data.id.remote", "_data.Info.Chat", "key.remoteJid" })
        {
            var value = JsonPathString(document.RootElement, path);
            if (!string.IsNullOrWhiteSpace(value))
            {
                return NormalizeWahaChatId(value);
            }
        }

        return null;
    }

    private async Task<string> WahaChatIdAsync(AiChatContext chat, CancellationToken cancellationToken)
    {
        if (chat.JenisChat == "Grup" && !string.IsNullOrWhiteSpace(chat.IdGrupWaha))
        {
            return chat.IdGrupWaha;
        }

        var latestIncoming = await LatestIncomingWahaChatIdAsync(chat.Id, cancellationToken);
        return latestIncoming ?? NormalizeWahaChatId(chat.NomorWhatsapp);
    }

    private static string NormalizeWahaChatId(string chatIdOrNumber)
    {
        if (chatIdOrNumber.Contains('@'))
        {
            return chatIdOrNumber.EndsWith("@s.whatsapp.net", StringComparison.OrdinalIgnoreCase)
                ? chatIdOrNumber.Replace("@s.whatsapp.net", "@c.us", StringComparison.OrdinalIgnoreCase)
                : chatIdOrNumber;
        }

        var number = new string(chatIdOrNumber.Where(char.IsDigit).ToArray());
        return $"{(string.IsNullOrWhiteSpace(number) ? chatIdOrNumber : number)}@c.us";
    }

    private static string? ExtractOutputText(JsonElement root)
    {
        var direct = JsonPathString(root, "output_text");
        if (!string.IsNullOrWhiteSpace(direct))
        {
            return direct;
        }

        if (root.TryGetProperty("output", out var output) && output.ValueKind == JsonValueKind.Array)
        {
            foreach (var item in output.EnumerateArray())
            {
                if (!item.TryGetProperty("content", out var content) || content.ValueKind != JsonValueKind.Array)
                {
                    continue;
                }

                foreach (var contentItem in content.EnumerateArray())
                {
                    var text = JsonPathString(contentItem, "text") ?? JsonPathString(contentItem, "content");
                    if (!string.IsNullOrWhiteSpace(text))
                    {
                        return text;
                    }
                }
            }
        }

        return null;
    }

    private string? ApiKey(MPengaturanAi settings, string provider)
    {
        // ApiKeyTerenkripsi dari Laravel tidak didekripsi di .NET; konfigurasi appsettings dipakai sebagai sumber runtime ASP.NET.
        return provider switch
        {
            "deepseek" => configuration["DeepSeek:ApiKey"],
            "openrouter" => configuration["OpenRouter:ApiKey"],
            _ => configuration["OpenAI:ApiKey"]
        };
    }

    private static string ProviderSection(string provider)
    {
        return provider.ToLowerInvariant() switch
        {
            "deepseek" => "DeepSeek",
            "openrouter" => "OpenRouter",
            _ => "OpenAI"
        };
    }

    private static string ChatCompletionEndpoint(string baseUrl)
    {
        baseUrl = baseUrl.TrimEnd('/');
        return baseUrl.EndsWith("/chat/completions", StringComparison.OrdinalIgnoreCase) ? baseUrl : $"{baseUrl}/chat/completions";
    }

    private static string? JsonPathString(JsonElement root, string path)
    {
        var current = root;
        foreach (var segment in path.Split('.'))
        {
            if (current.ValueKind == JsonValueKind.Array && int.TryParse(segment, out var index))
            {
                if (index < 0 || index >= current.GetArrayLength())
                {
                    return null;
                }

                current = current[index];
                continue;
            }

            if (current.ValueKind != JsonValueKind.Object || !current.TryGetProperty(segment, out current))
            {
                return null;
            }
        }

        return current.ValueKind switch
        {
            JsonValueKind.String => current.GetString(),
            JsonValueKind.Number => current.GetRawText(),
            JsonValueKind.True => "true",
            JsonValueKind.False => "false",
            _ => null
        };
    }

    private static string FormatHolidayTemplate(MPengaturanAi settings, HolidayInfo holiday)
    {
        var template = settings.TemplateHariLibur ?? DefaultHolidayTemplate();
        return template
            .Replace("{nama_hari_libur}", holiday.Name)
            .Replace("{tanggal_libur}", FormatIndonesianDate(holiday.Date))
            .Replace("{tanggal_masuk_kerja}", holiday.NextWorkingDate is null ? "hari kerja berikutnya" : FormatIndonesianDate(holiday.NextWorkingDate.Value));
    }

    private static DateTime LocalNow(string timezone)
    {
        return TimeZoneInfo.ConvertTimeFromUtc(DateTime.UtcNow, ResolveTimeZone(timezone));
    }

    private static TimeZoneInfo ResolveTimeZone(string? timezone)
    {
        timezone = string.IsNullOrWhiteSpace(timezone) ? "Asia/Jakarta" : timezone;
        try
        {
            return TimeZoneInfo.FindSystemTimeZoneById(timezone);
        }
        catch (TimeZoneNotFoundException) when (timezone.Equals("Asia/Jakarta", StringComparison.OrdinalIgnoreCase))
        {
            return TimeZoneInfo.FindSystemTimeZoneById("SE Asia Standard Time");
        }
    }

    private static HashSet<int> ParseWorkdays(string value)
    {
        return value.Split(',', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries)
            .Select(x => int.TryParse(x, out var parsed) ? parsed : 0)
            .Where(x => x > 0)
            .ToHashSet();
    }

    private static string FormatIndonesianDate(DateTime date)
    {
        var days = new[] { "", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu" };
        var months = new[] { "", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember" };
        var isoDay = ((int)date.DayOfWeek + 6) % 7 + 1;
        return $"{days[isoDay]}, {date.Day} {months[date.Month]} {date.Year}";
    }

    private static string Limit(string value, int limit) => value.Length <= limit ? value : value[..limit];

    private static string DefaultOutsideTemplate() => "Terima kasih sudah menghubungi VPoint Care. Saat ini kami berada di luar jam operasional. Pesan Bapak/Ibu sudah kami terima dan akan kami tindak lanjuti pada jam kerja berikutnya.";

    private static string DefaultHolidayTemplate() => "Terima kasih sudah menghubungi VPoint Care. Hari ini kami sedang libur ({nama_hari_libur}). Pesan Bapak/Ibu tetap kami terima dan akan kami teruskan ke tim customer service. Silakan sampaikan detail kendalanya agar tim kami bisa menindaklanjuti pada hari kerja berikutnya, {tanggal_masuk_kerja}. Mohon maaf atas ketidaknyamanannya.";

    private static string DefaultGreetingTemplate() => "Halo, terima kasih sudah menghubungi VPoint Care. Saya bantu catat terlebih dahulu ya. Silakan jelaskan kendala yang sedang dialami, nanti tim customer service kami akan melanjutkan penanganannya.";

    private static string DefaultFallbackTemplate() => "Terima kasih informasinya. Pesan sudah kami terima dan akan kami teruskan ke tim terkait untuk ditindaklanjuti.";

    private sealed record AiChatContext(Guid Id, string JenisChat, string NomorWhatsapp, string? NamaKontak, string? NamaGrupWhatsapp, bool AutoReplyAiAktif, bool AiSudahMenyapa, string? KodeSesi, string? NamaInstansi, string? NamaCustomer, string? IdGrupWaha);

    private sealed record AiReplyDecision(bool Allow, string Reason, string Mode, string Template);

    private sealed record HolidayInfo(string Name, DateTime Date, DateTime? NextWorkingDate);

    private sealed record GeneratedReply(string Text, object? Payload);
}

public sealed record AiAutoReplyResult(bool Ok, bool Skipped, string? Reason, string? Mode, AiDeliveryResult? Delivery, Guid? AiResponseId)
{
    public static AiAutoReplyResult Skip(string reason) => new(true, true, reason, null, null, null);
}

public sealed record AiDeliveryResult(string ModeKirim, string Status, string AutoReplyMode, string? Error);
