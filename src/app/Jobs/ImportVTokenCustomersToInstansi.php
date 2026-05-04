<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ImportVTokenCustomersToInstansi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private readonly ?string $url = null,
    ) {}

    public function handle(): void
    {
        $url = $this->url ?: (string) config('services.vtoken.open_customers_url');

        if (trim($url) === '') {
            throw new \RuntimeException('URL import customer VToken belum dikonfigurasi.');
        }

        $logId = $this->createIntegrationLog($url);

        try {
            $response = Http::acceptJson()
                ->timeout(60)
                ->retry(2, 1000)
                ->get($url);

            $this->updateIntegrationLog($logId, $response);

            if (! $response->successful()) {
                throw new \RuntimeException("Gagal mengambil data customer VToken. HTTP {$response->status()}");
            }

            $payload = $response->json();

            if (! is_array($payload) || ($payload['jsonResult'] ?? false) !== true || ! is_array($payload['jsonValue'] ?? null)) {
                throw new \RuntimeException('Format response customer VToken tidak sesuai.');
            }

            $result = $this->importRows($payload['jsonValue']);

            Log::info('Import customer VToken ke MInstansi selesai.', $result);
        } catch (Throwable $exception) {
            $this->failIntegrationLog($logId, $exception);

            throw $exception;
        }
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array{created: int, updated: int, skipped: int}
     */
    private function importRows(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $skipped++;

                continue;
            }

            $kode = $this->limit((string) ($row['kode'] ?? ''), 50);

            if ($kode === '') {
                $skipped++;

                continue;
            }

            $existingId = DB::table('MInstansi')
                ->where('KodeInstansi', $kode)
                ->value('Id');

            $values = [
                'NamaInstansi' => $this->namaInstansi($row, $kode),
                'Alamat' => $this->nullableString($row['alamat'] ?? null, 500),
                'Kota' => $this->nullableString($row['kota'] ?? null, 100),
                'SumberData' => 'vtoken',
                'IdExternal' => $this->nullableString($row['noID'] ?? null, 100),
                'TglSinkronTerakhir' => now(),
            ];

            if ($existingId) {
                DB::table('MInstansi')
                    ->where('Id', $existingId)
                    ->update($values + [
                        'TglEdit' => now(),
                    ]);

                $updated++;

                continue;
            }

            DB::table('MInstansi')->insert($values + [
                'KodeInstansi' => $kode,
                'NonAktif' => false,
                'TglBuat' => now(),
            ]);

            $created++;
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function namaInstansi(array $row, string $kode): string
    {
        $nama = trim((string) ($row['namaPerusahaan'] ?? ''));

        if ($nama === '') {
            $nama = trim((string) ($row['appName'] ?? ''));
        }

        return $this->limit($nama !== '' ? $nama : $kode, 200);
    }

    private function nullableString(mixed $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $this->limit($value, $limit);
    }

    private function limit(string $value, int $limit): string
    {
        return Str::limit(trim($value), $limit, '');
    }

    private function createIntegrationLog(string $url): string
    {
        $id = (string) Str::orderedUuid();

        DB::table('TLogIntegrasi')->insert([
            'Id' => $id,
            'KodeIntegrasi' => 'VTOKEN_CUSTOMERS_TO_MINSTANSI',
            'UrlEndpoint' => $url,
            'MetodeHttp' => 'GET',
            'TglRequest' => now(),
            'TglBuat' => now(),
        ]);

        return $id;
    }

    private function updateIntegrationLog(string $id, Response $response): void
    {
        DB::table('TLogIntegrasi')->where('Id', $id)->update([
            'ResponseJson' => $response->body(),
            'StatusHttp' => $response->status(),
            'Berhasil' => $response->successful(),
            'PesanError' => $response->successful() ? null : $response->body(),
            'TglResponse' => now(),
            'TglEdit' => now(),
        ]);
    }

    private function failIntegrationLog(string $id, Throwable $exception): void
    {
        DB::table('TLogIntegrasi')->where('Id', $id)->update([
            'Berhasil' => false,
            'PesanError' => $exception->getMessage(),
            'TglResponse' => now(),
            'TglEdit' => now(),
        ]);
    }
}
