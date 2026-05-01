<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The pengguna profile photo migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MPengguna', 'FotoProfilPath') IS NULL
    ALTER TABLE MPengguna ADD FotoProfilPath nvarchar(500) NULL;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MPengguna', 'FotoProfilPath') IS NOT NULL
    ALTER TABLE MPengguna DROP COLUMN FotoProfilPath;
SQL);
    }
};
