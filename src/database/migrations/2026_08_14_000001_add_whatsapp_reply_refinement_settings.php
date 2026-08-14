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
    IF COL_LENGTH('MPengaturanAi', 'PerhalusJawabanWhatsappDefault') IS NULL
        ALTER TABLE MPengaturanAi ADD PerhalusJawabanWhatsappDefault bit NOT NULL CONSTRAINT DF_MPengaturanAi_PerhalusJawabanWhatsappDefault DEFAULT 0;
END

IF OBJECT_ID(N'MPengguna', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengguna', 'PerhalusJawabanWhatsapp') IS NULL
        ALTER TABLE MPengguna ADD PerhalusJawabanWhatsapp bit NULL;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'PerhalusJawabanWhatsappDefault') IS NOT NULL
    BEGIN
        IF OBJECT_ID(N'DF_MPengaturanAi_PerhalusJawabanWhatsappDefault', 'D') IS NOT NULL
            ALTER TABLE MPengaturanAi DROP CONSTRAINT DF_MPengaturanAi_PerhalusJawabanWhatsappDefault;
        ALTER TABLE MPengaturanAi DROP COLUMN PerhalusJawabanWhatsappDefault;
    END
END

IF OBJECT_ID(N'MPengguna', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengguna', 'PerhalusJawabanWhatsapp') IS NOT NULL
        ALTER TABLE MPengguna DROP COLUMN PerhalusJawabanWhatsapp;
END
SQL);
    }
};
