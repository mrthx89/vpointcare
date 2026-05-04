using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;
using VPointCare.Web.Services.Waha;

namespace VPointCare.Web.Jobs;

public class UnansweredChatNotificationJob(
    VPointCareDbContext dbContext,
    WahaSenderService wahaSender,
    IConfiguration configuration,
    ILogger<UnansweredChatNotificationJob> logger)
{
    public async Task<UnansweredChatNotificationResult> ExecuteAsync(CancellationToken cancellationToken = default)
    {
        var settings = await dbContext.MPengaturanAiSet
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.KodePengaturan == "DEFAULT" && !x.NonAktif, cancellationToken);

        if (settings is null || !settings.NotifikasiChatBelumTerbalasAktif)
        {
            return UnansweredChatNotificationResult.Empty();
        }

        if (!await InsideWorkingScheduleAsync(settings.HariKerja, settings.JamKerjaMulai, settings.JamKerjaSelesai, settings.ZonaWaktu, cancellationToken))
        {
            return UnansweredChatNotificationResult.Empty(skippedSchedule: 1);
        }

        var recipients = await GetRecipientsAsync(settings.KodePeranPenerimaNotifikasi, cancellationToken);
        if (recipients.Count == 0)
        {
            return UnansweredChatNotificationResult.Empty();
        }

        var waitMinutes = Math.Max(1, settings.MenitTungguNotifikasi);
        var cooldownMinutes = Math.Max(1, settings.JedaNotifikasiMenit);
        var chats = await GetUnansweredChatsAsync(waitMinutes, cooldownMinutes, cancellationToken);
        var sent = 0;
        var failed = 0;
        var session = configuration["Waha:NotificationSession"] ?? "default";

        foreach (var chat in chats)
        {
            foreach (var recipient in recipients)
            {
                var message = BuildMessage(settings.TemplateNotifikasiChatBelumTerbalas, chat, recipient, waitMinutes);
                var delivery = await wahaSender.SendTextAsync(session, recipient.NomorWhatsappInternal, message, "WAHA_NOTIF_CHAT_BELUM_DIBALAS", cancellationToken);
                if (delivery.Ok)
                {
                    sent++;
                }
                else
                {
                    failed++;
                }
            }

            await dbContext.ChatMasters
                .Where(x => x.Id == chat.Id)
                .ExecuteUpdateAsync(setters => setters
                    .SetProperty(x => x.TglNotifikasiBelumTerbalasTerakhir, DateTime.UtcNow)
                    .SetProperty(x => x.JumlahNotifikasiBelumTerbalas, x => x.JumlahNotifikasiBelumTerbalas + 1)
                    .SetProperty(x => x.TglEdit, DateTime.UtcNow), cancellationToken);
        }

        var result = new UnansweredChatNotificationResult(chats.Count, sent, failed, recipients.Count, 0);
        logger.LogInformation("Notifikasi chat belum terbalas selesai. Chat={Chat}, Penerima={Recipients}, Terkirim={Sent}, Gagal={Failed}", result.ChatDiperiksa, result.Penerima, result.NotifikasiTerkirim, result.NotifikasiGagal);
        return result;
    }

    private async Task<bool> InsideWorkingScheduleAsync(string workdayConfig, TimeSpan start, TimeSpan end, string timezone, CancellationToken cancellationToken)
    {
        var localNow = TimeZoneInfo.ConvertTimeFromUtc(DateTime.UtcNow, ResolveTimeZone(timezone));
        var workdays = workdayConfig
            .Split(',', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries)
            .Select(x => int.TryParse(x, out var value) ? value : 0)
            .Where(x => x > 0)
            .ToHashSet();
        var isoDay = ((int)localNow.DayOfWeek + 6) % 7 + 1;

        if (!workdays.Contains(isoDay))
        {
            return false;
        }

        if (await IsHolidayAsync(localNow.Date, cancellationToken))
        {
            return false;
        }

        return localNow.TimeOfDay >= start && localNow.TimeOfDay <= end;
    }

    private async Task<bool> IsHolidayAsync(DateTime localDate, CancellationToken cancellationToken)
    {
        return await dbContext.MHariLiburSet
            .AsNoTracking()
            .AnyAsync(x => !x.NonAktif
                && (x.TanggalLibur.Date == localDate
                    || (x.BerlakuTahunan && x.TanggalLibur.Month == localDate.Month && x.TanggalLibur.Day == localDate.Day)), cancellationToken);
    }

    private async Task<List<NotificationRecipient>> GetRecipientsAsync(string roleCodes, CancellationToken cancellationToken)
    {
        var codes = roleCodes
            .Split(',', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries)
            .ToHashSet(StringComparer.OrdinalIgnoreCase);

        var query =
            from pengguna in dbContext.Penggunas.AsNoTracking()
            join peran in dbContext.Perans.AsNoTracking() on pengguna.IdPeran equals peran.Id into roleJoin
            from peran in roleJoin.DefaultIfEmpty()
            where !pengguna.NonAktif
                && pengguna.NomorWhatsappInternal != null
                && pengguna.NomorWhatsappInternal != ""
            select new { pengguna.Id, pengguna.NamaPengguna, pengguna.NomorWhatsappInternal, KodePeran = peran == null ? null : peran.KodePeran };

        if (codes.Count > 0)
        {
            query = query.Where(x => x.KodePeran != null && codes.Contains(x.KodePeran));
        }

        return await query
            .Select(x => new NotificationRecipient(x.Id, x.NamaPengguna, x.NomorWhatsappInternal!, x.KodePeran))
            .ToListAsync(cancellationToken);
    }

    private async Task<List<UnansweredChatRow>> GetUnansweredChatsAsync(int waitMinutes, int cooldownMinutes, CancellationToken cancellationToken)
    {
        var incomingCutoff = DateTime.UtcNow.AddMinutes(-waitMinutes);
        var cooldownCutoff = DateTime.UtcNow.AddMinutes(-cooldownMinutes);

        var latestIncoming = dbContext.ChatDetails
            .Where(x => x.ArahPesan == "Masuk" && x.DikirimOlehCustomer)
            .GroupBy(x => x.IdChatM)
            .Select(group => new { IdChatM = group.Key, TglPesanTerakhirMasuk = group.Max(x => x.TglPesan) });

        var latestCsReply = dbContext.ChatDetails
            .Where(x => x.ArahPesan == "Keluar" && !x.DihasilkanOlehAi)
            .GroupBy(x => x.IdChatM)
            .Select(group => new { IdChatM = group.Key, TglPesanTerakhirCs = group.Max(x => x.TglPesan) });

        var rows = await (
            from chat in dbContext.ChatMasters.AsNoTracking()
            join incoming in latestIncoming on chat.Id equals incoming.IdChatM
            join csReply in latestCsReply on chat.Id equals csReply.IdChatM into csReplyJoin
            from csReply in csReplyJoin.DefaultIfEmpty()
            join instansi in dbContext.MInstansiSet.AsNoTracking() on chat.IdInstansi equals instansi.Id into instansiJoin
            from instansi in instansiJoin.DefaultIfEmpty()
            join customer in dbContext.MCustomerSet.AsNoTracking() on chat.IdCustomer equals customer.Id into customerJoin
            from customer in customerJoin.DefaultIfEmpty()
            where (csReply == null || csReply.TglPesanTerakhirCs < incoming.TglPesanTerakhirMasuk)
                && incoming.TglPesanTerakhirMasuk <= incomingCutoff
                && (chat.TglNotifikasiBelumTerbalasTerakhir == null || chat.TglNotifikasiBelumTerbalasTerakhir <= cooldownCutoff)
            orderby incoming.TglPesanTerakhirMasuk
            select new UnansweredChatRow(
                chat.Id,
                chat.JenisChat,
                chat.NomorWhatsapp,
                chat.NamaKontak,
                chat.NamaGrupWhatsapp,
                instansi == null ? null : instansi.NamaInstansi,
                customer == null ? null : customer.NamaCustomer,
                incoming.TglPesanTerakhirMasuk,
                null))
            .Take(20)
            .ToListAsync(cancellationToken);

        for (var i = 0; i < rows.Count; i++)
        {
            var row = rows[i];
            var lastMessage = await dbContext.ChatDetails
                .AsNoTracking()
                .Where(x => x.IdChatM == row.Id && x.ArahPesan == "Masuk" && x.TglPesan == row.TglPesanTerakhirMasuk)
                .Select(x => x.IsiPesan)
                .FirstOrDefaultAsync(cancellationToken);

            rows[i] = row with { PesanTerakhir = lastMessage ?? "" };
        }

        return rows;
    }

    private string BuildMessage(string? template, UnansweredChatRow chat, NotificationRecipient recipient, int waitMinutes)
    {
        template = string.IsNullOrWhiteSpace(template)
            ? "Halo {nama_user}, ada chat WhatsApp dari {nama_instansi} yang belum dibalas selama {menit_menunggu} menit. Kontak: {nama_kontak} ({nomor_whatsapp}). Pesan terakhir: {pesan_terakhir}. Silakan cek VPoint Care: {url_admin}"
            : template;

        var adminUrl = $"{(configuration["App:PublicUrl"] ?? "").TrimEnd('/')}/admin/inbox-whatsapp";
        var minutes = Math.Max(waitMinutes, (int)Math.Floor((DateTime.UtcNow - chat.TglPesanTerakhirMasuk).TotalMinutes));
        var replacements = new Dictionary<string, string>
        {
            ["{nama_user}"] = recipient.NamaPengguna,
            ["{nama_instansi}"] = chat.NamaInstansi ?? chat.NamaCustomer ?? "Belum dipetakan",
            ["{jenis_chat}"] = chat.JenisChat,
            ["{nama_kontak}"] = chat.JenisChat == "Grup" ? chat.NamaGrupWhatsapp ?? "Grup WhatsApp" : chat.NamaKontak ?? "Customer",
            ["{nomor_whatsapp}"] = chat.NomorWhatsapp,
            ["{pesan_terakhir}"] = Limit(chat.PesanTerakhir ?? "", 180),
            ["{menit_menunggu}"] = minutes.ToString(),
            ["{url_admin}"] = adminUrl
        };

        foreach (var replacement in replacements)
        {
            template = template.Replace(replacement.Key, replacement.Value, StringComparison.OrdinalIgnoreCase);
        }

        return template;
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

    private static string Limit(string value, int limit) => value.Length <= limit ? value : value[..limit];

    private sealed record NotificationRecipient(Guid Id, string NamaPengguna, string NomorWhatsappInternal, string? KodePeran);

    private sealed record UnansweredChatRow(
        Guid Id,
        string JenisChat,
        string NomorWhatsapp,
        string? NamaKontak,
        string? NamaGrupWhatsapp,
        string? NamaInstansi,
        string? NamaCustomer,
        DateTime TglPesanTerakhirMasuk,
        string? PesanTerakhir);
}

public sealed record UnansweredChatNotificationResult(int ChatDiperiksa, int NotifikasiTerkirim, int NotifikasiGagal, int Penerima, int DilewatiJadwal)
{
    public static UnansweredChatNotificationResult Empty(int skippedSchedule = 0) => new(0, 0, 0, 0, skippedSchedule);
}
