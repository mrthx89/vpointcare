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
IF COL_LENGTH('TChatM', 'IdWahaTerdeteksi') IS NULL
    ALTER TABLE TChatM ADD IdWahaTerdeteksi varchar(200) NULL;

IF COL_LENGTH('TChatM', 'NomorWhatsappTerdeteksi') IS NULL
    ALTER TABLE TChatM ADD NomorWhatsappTerdeteksi varchar(30) NULL;

IF COL_LENGTH('TChatM', 'UrlFotoProfil') IS NULL
    ALTER TABLE TChatM ADD UrlFotoProfil nvarchar(1000) NULL;

IF COL_LENGTH('TChatM', 'TglFotoProfilDiambil') IS NULL
    ALTER TABLE TChatM ADD TglFotoProfilDiambil datetime2 NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_IdWahaTerdeteksi' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_IdWahaTerdeteksi ON TChatM (IdWahaTerdeteksi);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_NomorWhatsappTerdeteksi' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_NomorWhatsappTerdeteksi ON TChatM (NomorWhatsappTerdeteksi);
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_NomorWhatsappTerdeteksi' AND object_id = OBJECT_ID('TChatM'))
    DROP INDEX IX_TChatM_NomorWhatsappTerdeteksi ON TChatM;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_IdWahaTerdeteksi' AND object_id = OBJECT_ID('TChatM'))
    DROP INDEX IX_TChatM_IdWahaTerdeteksi ON TChatM;

IF COL_LENGTH('TChatM', 'TglFotoProfilDiambil') IS NOT NULL
    ALTER TABLE TChatM DROP COLUMN TglFotoProfilDiambil;

IF COL_LENGTH('TChatM', 'UrlFotoProfil') IS NOT NULL
    ALTER TABLE TChatM DROP COLUMN UrlFotoProfil;

IF COL_LENGTH('TChatM', 'NomorWhatsappTerdeteksi') IS NOT NULL
    ALTER TABLE TChatM DROP COLUMN NomorWhatsappTerdeteksi;

IF COL_LENGTH('TChatM', 'IdWahaTerdeteksi') IS NOT NULL
    ALTER TABLE TChatM DROP COLUMN IdWahaTerdeteksi;
SQL);
    }
};
