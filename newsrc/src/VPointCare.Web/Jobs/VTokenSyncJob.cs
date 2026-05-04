using System.Text.Json;
using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;
using VPointCare.Web.Data.Entities;

namespace VPointCare.Web.Jobs;

public class VTokenSyncJob(HttpClient httpClient, IConfiguration configuration, VPointCareDbContext dbContext, ILogger<VTokenSyncJob> logger)
{
    public async Task<VTokenSyncResult> ExecuteAsync(CancellationToken cancellationToken = default)
    {
        var url = configuration["VToken:OpenCustomersUrl"];
        if (string.IsNullOrWhiteSpace(url))
        {
            throw new InvalidOperationException("Konfigurasi VToken:OpenCustomersUrl belum diisi.");
        }

        var now = DateTime.UtcNow;
        var log = new TLogIntegrasi
        {
            Id = Guid.NewGuid(),
            KodeIntegrasi = "VTOKEN_CUSTOMERS_TO_MINSTANSI",
            UrlEndpoint = url,
            MetodeHttp = "GET",
            TglRequest = now,
            TglBuat = now
        };

        dbContext.LogIntegrasis.Add(log);
        await dbContext.SaveChangesAsync(cancellationToken);

        try
        {
            using var response = await GetWithRetryAsync(url, cancellationToken);
            var body = await response.Content.ReadAsStringAsync(cancellationToken);
            log.StatusHttp = (int)response.StatusCode;
            log.ResponseJson = body;
            log.Berhasil = response.IsSuccessStatusCode;
            log.PesanError = response.IsSuccessStatusCode ? null : body;
            log.TglResponse = DateTime.UtcNow;
            log.TglEdit = DateTime.UtcNow;
            await dbContext.SaveChangesAsync(cancellationToken);

            if (!response.IsSuccessStatusCode)
            {
                throw new InvalidOperationException($"Gagal mengambil data customer VToken. HTTP {(int)response.StatusCode}");
            }

            var result = await ImportRowsAsync(body, cancellationToken);
            logger.LogInformation("Import customer VToken ke MInstansi selesai. Created={Created}, Updated={Updated}, Skipped={Skipped}", result.Created, result.Updated, result.Skipped);
            return result;
        }
        catch (Exception exception)
        {
            log.Berhasil = false;
            log.PesanError = exception.Message;
            log.TglResponse ??= DateTime.UtcNow;
            log.TglEdit = DateTime.UtcNow;
            await dbContext.SaveChangesAsync(cancellationToken);
            throw;
        }
    }

    private async Task<HttpResponseMessage> GetWithRetryAsync(string url, CancellationToken cancellationToken)
    {
        Exception? lastException = null;
        for (var attempt = 1; attempt <= 3; attempt++)
        {
            try
            {
                using var request = new HttpRequestMessage(HttpMethod.Get, url);
                request.Headers.Accept.ParseAdd("application/json");
                var response = await httpClient.SendAsync(request, cancellationToken);
                if ((int)response.StatusCode < 500 || attempt == 3)
                {
                    return response;
                }

                response.Dispose();
            }
            catch (Exception exception) when (attempt < 3)
            {
                lastException = exception;
            }

            await Task.Delay(1000, cancellationToken);
        }

        throw lastException ?? new InvalidOperationException("Gagal menghubungi endpoint VToken.");
    }

    private async Task<VTokenSyncResult> ImportRowsAsync(string json, CancellationToken cancellationToken)
    {
        using var document = JsonDocument.Parse(json);
        var root = document.RootElement;
        if (!root.TryGetProperty("jsonResult", out var resultElement)
            || resultElement.ValueKind != JsonValueKind.True
            || !root.TryGetProperty("jsonValue", out var rowsElement)
            || rowsElement.ValueKind != JsonValueKind.Array)
        {
            throw new InvalidOperationException("Format response customer VToken tidak sesuai.");
        }

        var created = 0;
        var updated = 0;
        var skipped = 0;
        var now = DateTime.UtcNow;

        foreach (var row in rowsElement.EnumerateArray())
        {
            if (row.ValueKind != JsonValueKind.Object)
            {
                skipped++;
                continue;
            }

            var kode = Limit(ReadString(row, "kode"), 50);
            if (string.IsNullOrWhiteSpace(kode))
            {
                skipped++;
                continue;
            }

            var instansi = await dbContext.MInstansiSet.FirstOrDefaultAsync(x => x.KodeInstansi == kode, cancellationToken);
            if (instansi is null)
            {
                instansi = new MInstansi
                {
                    Id = Guid.NewGuid(),
                    KodeInstansi = kode,
                    NonAktif = false,
                    TglBuat = now
                };
                dbContext.MInstansiSet.Add(instansi);
                created++;
            }
            else
            {
                instansi.TglEdit = now;
                updated++;
            }

            instansi.NamaInstansi = NamaInstansi(row, kode);
            instansi.Alamat = NullableString(ReadString(row, "alamat"), 500);
            instansi.Kota = NullableString(ReadString(row, "kota"), 100);
            instansi.SumberData = "vtoken";
            instansi.IdExternal = NullableString(ReadString(row, "noID"), 100);
            instansi.TglSinkronTerakhir = now;
        }

        await dbContext.SaveChangesAsync(cancellationToken);
        return new VTokenSyncResult(created, updated, skipped);
    }

    private static string NamaInstansi(JsonElement row, string kode)
    {
        var nama = ReadString(row, "namaPerusahaan");
        if (string.IsNullOrWhiteSpace(nama))
        {
            nama = ReadString(row, "appName");
        }

        return Limit(string.IsNullOrWhiteSpace(nama) ? kode : nama, 200);
    }

    private static string? ReadString(JsonElement element, string propertyName)
    {
        if (!element.TryGetProperty(propertyName, out var value))
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

    private static string? NullableString(string? value, int limit)
    {
        value = value?.Trim();
        return string.IsNullOrWhiteSpace(value) ? null : Limit(value, limit);
    }

    private static string Limit(string? value, int limit)
    {
        value = value?.Trim() ?? "";
        return value.Length <= limit ? value : value[..limit];
    }
}

public sealed record VTokenSyncResult(int Created, int Updated, int Skipped);
