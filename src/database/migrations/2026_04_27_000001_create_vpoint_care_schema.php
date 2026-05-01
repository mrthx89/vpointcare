<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The VPoint Care schema migration requires the sqlsrv database connection.');
        }

        $schemaPath = base_path('DATABASE_SCHEMA_WACS.sql');

        if (! file_exists($schemaPath)) {
            throw new RuntimeException("Schema file not found: {$schemaPath}");
        }

        $sql = file_get_contents($schemaPath);

        foreach ($this->splitSqlServerBatches($sql) as $batch) {
            DB::unprepared($batch);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        foreach ($this->tablesInDropOrder() as $table) {
            DB::unprepared("
                IF OBJECT_ID(N'{$table}', 'U') IS NOT NULL
                    DROP TABLE {$table}
            ");
        }
    }

    /**
     * SQL Server Management Studio uses GO as a batch separator, but GO is not
     * valid T-SQL. Split it before sending the statements through PDO.
     *
     * @return array<int, string>
     */
    private function splitSqlServerBatches(string $sql): array
    {
        $batches = preg_split('/^\s*GO\s*$/mi', $sql) ?: [];

        return array_values(array_filter(array_map('trim', $batches), fn (string $batch): bool => $batch !== ''));
    }

    /**
     * @return array<int, string>
     */
    private function tablesInDropOrder(): array
    {
        return [
            'TTicketLampiran',
            'TTicketPenugasan',
            'TTicketD',
            'TTicketM',
            'TChatCatatanInternal',
            'TChatPenugasan',
            'TChatD',
            'TAiRespon',
            'TAiPermintaan',
            'TChatM',
            'TLogWebhookWaha',
            'TLogIntegrasi',
            'TLogError',
            'TLogAktivitas',
            'MPengetahuan',
            'MPengaturanAi',
            'MHariLibur',
            'MAiProvider',
            'MEndpointIntegrasi',
            'MSesiWhatsapp',
            'MStatusTicket',
            'MPrioritasTicket',
            'MKategoriTicket',
            'MStatusChat',
            'MProdukCustomer',
            'MNomorWhatsapp',
            'MCustomer',
            'MInstansi',
            'MPengguna',
            'MPeranHakAkses',
            'MHakAkses',
            'MPeran',
        ];
    }
};
