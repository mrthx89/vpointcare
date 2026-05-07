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
    IF COL_LENGTH('MPengaturanAi', 'ExcludeNomorWhatsapp') IS NULL
        ALTER TABLE MPengaturanAi ADD ExcludeNomorWhatsapp nvarchar(max) NULL;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'ExcludeNomorWhatsapp') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP COLUMN ExcludeNomorWhatsapp;
END
SQL);
    }
};
