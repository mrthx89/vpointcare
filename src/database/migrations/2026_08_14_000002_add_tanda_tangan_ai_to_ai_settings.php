<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'TandaTanganAi') IS NULL
        ALTER TABLE MPengaturanAi ADD TandaTanganAi nvarchar(255) NULL;
END
SQL
        );
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'TandaTanganAi') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP COLUMN TandaTanganAi;
END
SQL
        );
    }
};
