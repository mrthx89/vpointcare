<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The hari libur auto reply migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MHariLibur', 'U') IS NULL
BEGIN
    CREATE TABLE MHariLibur (
        Id uniqueidentifier NOT NULL CONSTRAINT DF_MHariLibur_Id DEFAULT NEWSEQUENTIALID(),
        TanggalLibur date NOT NULL,
        NamaHariLibur varchar(200) NOT NULL,
        Keterangan varchar(1000) NULL,
        BerlakuTahunan bit NOT NULL CONSTRAINT DF_MHariLibur_BerlakuTahunan DEFAULT 0,
        NonAktif bit NOT NULL CONSTRAINT DF_MHariLibur_NonAktif DEFAULT 0,
        TglBuat datetime2 NOT NULL CONSTRAINT DF_MHariLibur_TglBuat DEFAULT SYSDATETIME(),
        DibuatOleh uniqueidentifier NULL,
        TglEdit datetime2 NULL,
        DieditOleh uniqueidentifier NULL,
        CONSTRAINT PK_MHariLibur PRIMARY KEY (Id)
    );
END

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MHariLibur_TanggalLibur' AND object_id = OBJECT_ID('MHariLibur'))
    CREATE INDEX IX_MHariLibur_TanggalLibur ON MHariLibur (TanggalLibur, NonAktif);
SQL);

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MPengaturanAi', 'AutoReplyHariLibur') IS NULL
    ALTER TABLE MPengaturanAi ADD AutoReplyHariLibur bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyHariLibur DEFAULT 1;

IF COL_LENGTH('MPengaturanAi', 'TemplateHariLibur') IS NULL
    ALTER TABLE MPengaturanAi ADD TemplateHariLibur nvarchar(max) NULL;
SQL);

        DB::unprepared(<<<'SQL'
UPDATE MPengaturanAi
SET TemplateHariLibur = N'Terima kasih sudah menghubungi VPoint Care. Hari ini kami sedang libur ({nama_hari_libur}). Pesan Bapak/Ibu tetap kami terima dan akan kami teruskan ke tim customer service. Silakan sampaikan detail kendalanya agar tim kami bisa menindaklanjuti pada hari kerja berikutnya, {tanggal_masuk_kerja}. Mohon maaf atas ketidaknyamanannya.'
WHERE KodePengaturan = 'DEFAULT'
  AND TemplateHariLibur IS NULL;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MPengaturanAi', 'TemplateHariLibur') IS NOT NULL
    ALTER TABLE MPengaturanAi DROP COLUMN TemplateHariLibur;

IF COL_LENGTH('MPengaturanAi', 'AutoReplyHariLibur') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_MPengaturanAi_AutoReplyHariLibur', 'D') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP CONSTRAINT DF_MPengaturanAi_AutoReplyHariLibur;
    ALTER TABLE MPengaturanAi DROP COLUMN AutoReplyHariLibur;
END

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MHariLibur_TanggalLibur' AND object_id = OBJECT_ID('MHariLibur'))
    DROP INDEX IX_MHariLibur_TanggalLibur ON MHariLibur;

IF OBJECT_ID(N'MHariLibur', 'U') IS NOT NULL
    DROP TABLE MHariLibur;
SQL);
    }
};
