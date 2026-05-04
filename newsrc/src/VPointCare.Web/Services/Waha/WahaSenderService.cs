using System.Net.Http.Json;
using System.Text.Json;
using VPointCare.Web.Data;
using VPointCare.Web.Data.Entities;

namespace VPointCare.Web.Services.Waha;

public class WahaSenderService(HttpClient httpClient, IConfiguration configuration, VPointCareDbContext dbContext)
{
    public Task<WahaSendResult> SendTextAsync(string session, string chatIdOrNumber, string text, string integrationCode = "WAHA_SEND_TEXT", CancellationToken cancellationToken = default)
    {
        var payload = new
        {
            session,
            chatId = NormalizeChatId(chatIdOrNumber),
            text
        };

        return PostJsonAsync(configuration["Waha:SendTextPath"] ?? "/api/sendText", payload, integrationCode, cancellationToken);
    }

    private async Task<WahaSendResult> PostJsonAsync(string path, object payload, string integrationCode, CancellationToken cancellationToken)
    {
        var baseUrl = (configuration["Waha:BaseUrl"] ?? "").TrimEnd('/');
        if (string.IsNullOrWhiteSpace(baseUrl))
        {
            return new WahaSendResult(false, null, null, "Waha:BaseUrl belum dikonfigurasi.");
        }

        var url = $"{baseUrl}/{path.TrimStart('/')}";
        var requestJson = JsonSerializer.Serialize(payload);
        var now = DateTime.UtcNow;
        var log = new TLogIntegrasi
        {
            Id = Guid.NewGuid(),
            KodeIntegrasi = integrationCode,
            UrlEndpoint = url,
            MetodeHttp = "POST",
            RequestJson = requestJson,
            TglRequest = now,
            TglBuat = now
        };

        dbContext.LogIntegrasis.Add(log);
        await dbContext.SaveChangesAsync(cancellationToken);

        try
        {
            using var request = new HttpRequestMessage(HttpMethod.Post, url)
            {
                Content = JsonContent.Create(payload)
            };
            request.Headers.Accept.ParseAdd("application/json");

            var apiKey = configuration["Waha:ApiKey"];
            if (!string.IsNullOrWhiteSpace(apiKey))
            {
                request.Headers.TryAddWithoutValidation("X-Api-Key", apiKey);
            }

            using var response = await httpClient.SendAsync(request, cancellationToken);
            var body = await response.Content.ReadAsStringAsync(cancellationToken);

            log.ResponseJson = body;
            log.StatusHttp = (int)response.StatusCode;
            log.Berhasil = response.IsSuccessStatusCode;
            log.PesanError = response.IsSuccessStatusCode ? null : body;
            log.TglResponse = DateTime.UtcNow;
            log.TglEdit = DateTime.UtcNow;
            await dbContext.SaveChangesAsync(cancellationToken);

            return new WahaSendResult(response.IsSuccessStatusCode, (int)response.StatusCode, body, response.IsSuccessStatusCode ? null : body);
        }
        catch (Exception exception)
        {
            log.Berhasil = false;
            log.PesanError = exception.Message;
            log.TglResponse = DateTime.UtcNow;
            log.TglEdit = DateTime.UtcNow;
            await dbContext.SaveChangesAsync(cancellationToken);
            return new WahaSendResult(false, null, null, exception.Message);
        }
    }

    private static string NormalizeChatId(string chatIdOrNumber)
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
}

public sealed record WahaSendResult(bool Ok, int? Status, string? Body, string? Error);
