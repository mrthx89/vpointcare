<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The WAHA profile chat migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('TChat', 'IdWahaTerdeteksi') IS NULL
    ALTER TABLE TChat ADD IdWahaTerdeteksi varchar(200) NULL;

IF COL_LENGTH('TChat', 'NomorWhatsappTerdeteksi') IS NULL
    ALTER TABLE TChat ADD NomorWhatsappTerdeteksi varchar(30) NULL;

IF COL_LENGTH('TChat', 'UrlFotoProfil') IS NULL
    ALTER TABLE TChat ADD UrlFotoProfil nvarchar(1000) NULL;

IF COL_LENGTH('TChat', 'TglFotoProfilDiambil') IS NULL
    ALTER TABLE TChat ADD TglFotoProfilDiambil datetime2 NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_IdWahaTerdeteksi' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_IdWahaTerdeteksi ON TChat (IdWahaTerdeteksi);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_NomorWhatsappTerdeteksi' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_NomorWhatsappTerdeteksi ON TChat (NomorWhatsappTerdeteksi);
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_NomorWhatsappTerdeteksi' AND object_id = OBJECT_ID('TChat'))
    DROP INDEX IX_TChat_NomorWhatsappTerdeteksi ON TChat;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_IdWahaTerdeteksi' AND object_id = OBJECT_ID('TChat'))
    DROP INDEX IX_TChat_IdWahaTerdeteksi ON TChat;

IF COL_LENGTH('TChat', 'TglFotoProfilDiambil') IS NOT NULL
    ALTER TABLE TChat DROP COLUMN TglFotoProfilDiambil;

IF COL_LENGTH('TChat', 'UrlFotoProfil') IS NOT NULL
    ALTER TABLE TChat DROP COLUMN UrlFotoProfil;

IF COL_LENGTH('TChat', 'NomorWhatsappTerdeteksi') IS NOT NULL
    ALTER TABLE TChat DROP COLUMN NomorWhatsappTerdeteksi;

IF COL_LENGTH('TChat', 'IdWahaTerdeteksi') IS NOT NULL
    ALTER TABLE TChat DROP COLUMN IdWahaTerdeteksi;
SQL);
    }
};
