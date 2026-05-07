<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The pengguna address migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MPengguna', 'Alamat') IS NULL
    ALTER TABLE MPengguna ADD Alamat varchar(500) NULL;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MPengguna', 'Alamat') IS NOT NULL
    ALTER TABLE MPengguna DROP COLUMN Alamat;
SQL);
    }
};
