using System.Text.Json;
using Microsoft.AspNetCore.SignalR;
using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;
using VPointCare.Web.Data.Entities;
using VPointCare.Web.Hubs;
using VPointCare.Web.Services.Realtime;

namespace VPointCare.Web.Services.Waha;

public class WahaWebhookProcessor(
    VPointCareDbContext dbContext,
    IHubContext<WahaInboxHub> hubContext)
{
    public async Task<WahaWebhookResult> ProcessAsync(JsonElement payload, CancellationToken cancellationToken = default)
    {
        var now = DateTime.UtcNow;
        var message = MessagePayload(payload);
        var sessionCode = ReadString(payload, "session") ?? ReadString(message, "session") ?? "default";
        var session = await FindOrCreateSessionAsync(sessionCode, cancellationToken);

        var log = new TLogWebhookWaha
        {
            Id = Guid.NewGuid(),
            IdSesiWhatsapp = session.Id,
            JenisEvent = ReadString(payload, "event") ?? ReadString(payload, "type") ?? "message",
            PayloadJson = payload.GetRawText(),
            TglDiterima = now,
            SudahDiproses = false,
            TglBuat = now
        };

        await using var transaction = await dbContext.Database.BeginTransactionAsync(cancellationToken);
        dbContext.LogWebhookWahas.Add(log);
        await dbContext.SaveChangesAsync(cancellationToken);

        try
        {
            var parsed = ParseMessage(payload, message, now);

            if (parsed.IsStatusBroadcast)
            {
                log.SudahDiproses = true;
                log.TglDiproses = now;
                log.TglEdit = now;
                await dbContext.SaveChangesAsync(cancellationToken);
                await transaction.CommitAsync(cancellationToken);
                return new WahaWebhookResult(true, log.Id, null, true, false, "Status", "WhatsApp status broadcast ignored.");
            }

            if (!string.IsNullOrWhiteSpace(parsed.MessageId))
            {
                var duplicate = await dbContext.ChatDetails
                    .AsNoTracking()
                    .FirstOrDefaultAsync(x => x.IdPesanWaha == parsed.MessageId, cancellationToken);

                if (duplicate is not null)
                {
                    log.SudahDiproses = true;
                    log.TglDiproses = now;
                    log.TglEdit = now;
                    await dbContext.SaveChangesAsync(cancellationToken);
                    await transaction.CommitAsync(cancellationToken);
                    return new WahaWebhookResult(true, log.Id, duplicate.IdChat, false, true, parsed.ChatType, "Duplicate WAHA message event ignored.");
                }
            }

            var chat = await FindOrCreateChatAsync(session.Id, parsed, now, cancellationToken);
            var chatMessage = new TChatD
            {
                Id = Guid.NewGuid(),
                IdChat = chat.Id,
                IdLogWebhookWaha = log.Id,
                IdPesanWaha = parsed.MessageId,
                ArahPesan = parsed.FromMe ? "Keluar" : "Masuk",
                JenisPesan = parsed.MessageType,
                IsiPesan = parsed.Body,
                UrlMedia = parsed.MediaUrl,
                NamaFileMedia = parsed.MediaFileName,
                TipeMime = parsed.MimeType,
                PayloadJson = message.ValueKind == JsonValueKind.Undefined ? payload.GetRawText() : message.GetRawText(),
                PengirimNomorWhatsapp = parsed.SenderPhone,
                PengirimNamaKontak = parsed.SenderName,
                DikirimOlehCustomer = !parsed.FromMe,
                TglPesan = parsed.MessageAt,
                StatusKirim = parsed.FromMe ? "Terkirim" : null,
                TglBuat = now
            };

            dbContext.ChatDetails.Add(chatMessage);
            chat.TglChatTerakhir = parsed.MessageAt;
            chat.TglEdit = now;
            if (!parsed.FromMe)
            {
                chat.JumlahPesanBelumDibaca += 1;
            }

            log.SudahDiproses = true;
            log.TglDiproses = now;
            log.TglEdit = now;

            await dbContext.SaveChangesAsync(cancellationToken);
            await transaction.CommitAsync(cancellationToken);

            var inboxPayload = new InboxUpdatedPayload(chat.Id, chat.NomorWhatsapp, chat.NamaKontak, parsed.Body, parsed.MessageAt);
            var notificationPayload = new NewMessageNotificationPayload(
                chat.Id,
                string.IsNullOrWhiteSpace(chat.NamaKontak) ? chat.NomorWhatsapp : chat.NamaKontak!,
                parsed.Body ?? parsed.MessageType,
                parsed.MessageAt);

            await hubContext.Clients.Group(WahaInboxHub.InboxViewerGroup).SendAsync("InboxUpdated", inboxPayload, cancellationToken);
            if (!parsed.FromMe)
            {
                await hubContext.Clients.Group(WahaInboxHub.InboxViewerGroup).SendAsync("NewMessageNotification", notificationPayload, cancellationToken);
            }

            return new WahaWebhookResult(true, log.Id, chat.Id, false, false, parsed.ChatType, null);
        }
        catch (Exception exception)
        {
            log.PesanError = exception.Message;
            log.TglEdit = now;
            await dbContext.SaveChangesAsync(cancellationToken);
            await transaction.CommitAsync(cancellationToken);
            throw;
        }
    }

    private async Task<MSesiWhatsapp> FindOrCreateSessionAsync(string code, CancellationToken cancellationToken)
    {
        var session = await dbContext.SesiWhatsapps.FirstOrDefaultAsync(x => x.KodeSesi == code, cancellationToken);
        if (session is not null)
        {
            return session;
        }

        session = new MSesiWhatsapp
        {
            Id = Guid.NewGuid(),
            KodeSesi = code,
            NamaSesi = code,
            BaseUrlWaha = "",
            StatusSesi = "Aktif",
            NonAktif = false,
            TglBuat = DateTime.UtcNow
        };
        dbContext.SesiWhatsapps.Add(session);
        await dbContext.SaveChangesAsync(cancellationToken);
        return session;
    }

    private async Task<TChat> FindOrCreateChatAsync(Guid sessionId, ParsedWahaMessage parsed, DateTime now, CancellationToken cancellationToken)
    {
        var query = dbContext.ChatMasters.Where(x => x.IdSesiWhatsapp == sessionId && x.JenisChat == parsed.ChatType);
        query = parsed.ChatType == "Grup"
            ? query.Where(x => x.IdWahaTerdeteksi == parsed.ChatJid)
            : query.Where(x => x.NomorWhatsapp == parsed.SenderPhone);

        var chat = await query.FirstOrDefaultAsync(cancellationToken);
        if (chat is not null)
        {
            if (!string.IsNullOrWhiteSpace(parsed.SenderName) && string.IsNullOrWhiteSpace(chat.NamaKontak))
            {
                chat.NamaKontak = parsed.SenderName;
            }
            return chat;
        }

        chat = new TChat
        {
            Id = Guid.NewGuid(),
            IdSesiWhatsapp = sessionId,
            JenisChat = parsed.ChatType,
            NomorWhatsapp = parsed.SenderPhone,
            NamaKontak = parsed.SenderName,
            NamaGrupWhatsapp = parsed.ChatType == "Grup" ? parsed.ChatName : null,
            IdWahaTerdeteksi = parsed.ChatJid,
            NomorWhatsappTerdeteksi = parsed.SenderPhone,
            Prioritas = "Normal",
            TglChatTerakhir = parsed.MessageAt,
            TglBuat = now
        };
        dbContext.ChatMasters.Add(chat);
        await dbContext.SaveChangesAsync(cancellationToken);
        return chat;
    }

    private static ParsedWahaMessage ParseMessage(JsonElement payload, JsonElement message, DateTime fallbackDate)
    {
        var chatJid = ReadString(message, "chatId")
            ?? ReadString(message, "from")
            ?? ReadString(message, "remoteJid")
            ?? ReadString(payload, "chatId")
            ?? "";
        var from = ReadString(message, "from") ?? chatJid;
        var senderJid = ReadString(message, "participant")
            ?? ReadString(message, "author")
            ?? ReadString(message, "sender")
            ?? from;
        var body = ReadString(message, "body")
            ?? ReadString(message, "text")
            ?? ReadString(message, "caption")
            ?? ReadString(message, "message.text")
            ?? ReadString(payload, "body");
        var messageId = ReadString(message, "id")
            ?? ReadString(message, "_data.id.id")
            ?? ReadString(message, "_data.key.id");
        var fromMe = ReadBool(message, "fromMe")
            ?? ReadBool(message, "_data.id.fromMe")
            ?? ReadBool(message, "_data.key.fromMe")
            ?? false;
        var messageAt = ReadDate(message, "timestamp") ?? ReadDate(payload, "timestamp") ?? fallbackDate;
        var chatType = chatJid.Contains("@g.us", StringComparison.OrdinalIgnoreCase) ? "Grup" : "Pribadi";
        var senderPhone = NormalizePhone(chatType == "Grup" ? senderJid : from);

        return new ParsedWahaMessage(
            messageId,
            chatJid,
            chatType,
            ReadString(message, "chatName") ?? ReadString(message, "_data.notifyName"),
            senderPhone,
            ReadString(message, "pushName") ?? ReadString(message, "notifyName") ?? ReadString(message, "_data.notifyName"),
            fromMe,
            body,
            MessageType(message, body),
            ReadString(message, "media.url") ?? ReadString(message, "mediaUrl"),
            ReadString(message, "media.filename") ?? ReadString(message, "filename"),
            ReadString(message, "media.mimetype") ?? ReadString(message, "mimetype"),
            messageAt,
            chatJid.Contains("status@broadcast", StringComparison.OrdinalIgnoreCase) || from.Contains("status@broadcast", StringComparison.OrdinalIgnoreCase));
    }

    private static JsonElement MessagePayload(JsonElement payload)
    {
        if (TryGetProperty(payload, "payload", out var nested) || TryGetProperty(payload, "data", out nested) || TryGetProperty(payload, "message", out nested))
        {
            return nested;
        }

        return payload;
    }

    private static string MessageType(JsonElement message, string? body)
    {
        var type = ReadString(message, "type") ?? ReadString(message, "messageType");
        if (string.IsNullOrWhiteSpace(type))
        {
            return string.IsNullOrWhiteSpace(body) ? "Media" : "Teks";
        }

        return type.Contains("image", StringComparison.OrdinalIgnoreCase) ? "Gambar"
            : type.Contains("video", StringComparison.OrdinalIgnoreCase) ? "Video"
            : type.Contains("audio", StringComparison.OrdinalIgnoreCase) ? "Audio"
            : type.Contains("document", StringComparison.OrdinalIgnoreCase) ? "Dokumen"
            : "Teks";
    }

    private static string NormalizePhone(string? jid)
    {
        if (string.IsNullOrWhiteSpace(jid))
        {
            return "";
        }

        var raw = jid.Split('@')[0].Split(':')[0];
        return new string(raw.Where(char.IsDigit).ToArray());
    }

    private static string? ReadString(JsonElement element, string path)
    {
        if (!TryGetByPath(element, path, out var value))
        {
            return null;
        }

        return value.ValueKind switch
        {
            JsonValueKind.String => value.GetString(),
            JsonValueKind.Number => value.GetRawText(),
            JsonValueKind.True => "true",
            JsonValueKind.False => "false",
            _ => null
        };
    }

    private static bool? ReadBool(JsonElement element, string path)
    {
        if (!TryGetByPath(element, path, out var value))
        {
            return null;
        }

        return value.ValueKind switch
        {
            JsonValueKind.True => true,
            JsonValueKind.False => false,
            JsonValueKind.String when bool.TryParse(value.GetString(), out var parsed) => parsed,
            _ => null
        };
    }

    private static DateTime? ReadDate(JsonElement element, string path)
    {
        var value = ReadString(element, path);
        if (string.IsNullOrWhiteSpace(value))
        {
            return null;
        }

        if (long.TryParse(value, out var unix))
        {
            return DateTimeOffset.FromUnixTimeSeconds(unix > 10_000_000_000 ? unix / 1000 : unix).UtcDateTime;
        }

        return DateTime.TryParse(value, out var parsed) ? parsed.ToUniversalTime() : null;
    }

    private static bool TryGetByPath(JsonElement element, string path, out JsonElement value)
    {
        value = element;
        foreach (var part in path.Split('.'))
        {
            if (!TryGetProperty(value, part, out value))
            {
                return false;
            }
        }

        return true;
    }

    private static bool TryGetProperty(JsonElement element, string propertyName, out JsonElement value)
    {
        if (element.ValueKind == JsonValueKind.Object && element.TryGetProperty(propertyName, out value))
        {
            return true;
        }

        value = default;
        return false;
    }

    private sealed record ParsedWahaMessage(
        string? MessageId,
        string ChatJid,
        string ChatType,
        string? ChatName,
        string SenderPhone,
        string? SenderName,
        bool FromMe,
        string? Body,
        string MessageType,
        string? MediaUrl,
        string? MediaFileName,
        string? MimeType,
        DateTime MessageAt,
        bool IsStatusBroadcast);
}

public sealed record WahaWebhookResult(bool Ok, Guid WebhookId, Guid? ChatId, bool Ignored, bool Duplicate, string ChatType, string? Message);
