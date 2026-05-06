<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The AI auto reply migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NULL
BEGIN
    CREATE TABLE MPengaturanAi (
        Id uniqueidentifier NOT NULL CONSTRAINT DF_MPengaturanAi_Id DEFAULT NEWSEQUENTIALID(),
        KodePengaturan varchar(50) NOT NULL,
        NamaPengaturan varchar(100) NOT NULL,
        AutoReplyAktif bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyAktif DEFAULT 0,
        AutoReplyDiluarJamKerja bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyDiluarJamKerja DEFAULT 1,
        AutoReplyJamKerjaSapaan bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyJamKerjaSapaan DEFAULT 1,
        AutoReplyJamKerjaBerlanjut bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyJamKerjaBerlanjut DEFAULT 0,
        JamKerjaMulai time(0) NOT NULL CONSTRAINT DF_MPengaturanAi_JamKerjaMulai DEFAULT '08:00',
        JamKerjaSelesai time(0) NOT NULL CONSTRAINT DF_MPengaturanAi_JamKerjaSelesai DEFAULT '17:00',
        HariKerja varchar(50) NOT NULL CONSTRAINT DF_MPengaturanAi_HariKerja DEFAULT '1,2,3,4,5',
        ZonaWaktu varchar(100) NOT NULL CONSTRAINT DF_MPengaturanAi_ZonaWaktu DEFAULT 'Asia/Jakarta',
        ProviderAi varchar(50) NOT NULL CONSTRAINT DF_MPengaturanAi_ProviderAi DEFAULT 'OpenAI',
        ModelAi varchar(100) NULL,
        BaseUrl varchar(255) NULL,
        ApiKeyTerenkripsi nvarchar(max) NULL,
        PromptSistem nvarchar(max) NULL,
        TemplateDiluarJamKerja nvarchar(max) NULL,
        TemplateJamKerjaSapaan nvarchar(max) NULL,
        TemplateFallback nvarchar(max) NULL,
        BatasRiwayatPesan int NOT NULL CONSTRAINT DF_MPengaturanAi_BatasRiwayatPesan DEFAULT 8,
        KirimKeWaha bit NOT NULL CONSTRAINT DF_MPengaturanAi_KirimKeWaha DEFAULT 0,
        ModeKirim varchar(50) NOT NULL CONSTRAINT DF_MPengaturanAi_ModeKirim DEFAULT 'DraftLokal',
        NonAktif bit NOT NULL CONSTRAINT DF_MPengaturanAi_NonAktif DEFAULT 0,
        TglBuat datetime2 NOT NULL CONSTRAINT DF_MPengaturanAi_TglBuat DEFAULT SYSDATETIME(),
        DibuatOleh uniqueidentifier NULL,
        TglEdit datetime2 NULL,
        DieditOleh uniqueidentifier NULL,
        CONSTRAINT PK_MPengaturanAi PRIMARY KEY (Id),
        CONSTRAINT UQ_MPengaturanAi_KodePengaturan UNIQUE (KodePengaturan)
    );
END

IF COL_LENGTH('TChat', 'AutoReplyAiAktif') IS NULL
    ALTER TABLE TChat ADD AutoReplyAiAktif bit NOT NULL CONSTRAINT DF_TChat_AutoReplyAiAktif DEFAULT 0;

IF COL_LENGTH('TChat', 'AiSudahMenyapa') IS NULL
    ALTER TABLE TChat ADD AiSudahMenyapa bit NOT NULL CONSTRAINT DF_TChat_AiSudahMenyapa DEFAULT 0;

IF COL_LENGTH('TChat', 'ModeAutoReplyAi') IS NULL
    ALTER TABLE TChat ADD ModeAutoReplyAi varchar(50) NOT NULL CONSTRAINT DF_TChat_ModeAutoReplyAi DEFAULT 'Default';

IF COL_LENGTH('TChat', 'TglAutoReplyAiTerakhir') IS NULL
    ALTER TABLE TChat ADD TglAutoReplyAiTerakhir datetime2 NULL;

IF COL_LENGTH('TChatD', 'DihasilkanOlehAi') IS NULL
    ALTER TABLE TChatD ADD DihasilkanOlehAi bit NOT NULL CONSTRAINT DF_TChatD_DihasilkanOlehAi DEFAULT 0;

IF COL_LENGTH('TChatD', 'IdAiRespon') IS NULL
    ALTER TABLE TChatD ADD IdAiRespon uniqueidentifier NULL;

IF OBJECT_ID(N'TAiRespon', 'U') IS NOT NULL
    AND COL_LENGTH('TChatD', 'IdAiRespon') IS NOT NULL
    AND OBJECT_ID(N'FK_TChatD_TAiRespon', 'F') IS NULL
BEGIN
    ALTER TABLE TChatD
    ADD CONSTRAINT FK_TChatD_TAiRespon FOREIGN KEY (IdAiRespon) REFERENCES TAiRespon(Id);
END

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdAiRespon' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_IdAiRespon ON TChatD (IdAiRespon);

IF NOT EXISTS (SELECT 1 FROM MPengaturanAi WHERE KodePengaturan = 'DEFAULT')
BEGIN
    INSERT INTO MPengaturanAi (
        KodePengaturan,
        NamaPengaturan,
        AutoReplyAktif,
        AutoReplyDiluarJamKerja,
        AutoReplyJamKerjaSapaan,
        AutoReplyJamKerjaBerlanjut,
        JamKerjaMulai,
        JamKerjaSelesai,
        HariKerja,
        ZonaWaktu,
        ProviderAi,
        ModelAi,
        BaseUrl,
        PromptSistem,
        TemplateDiluarJamKerja,
        TemplateJamKerjaSapaan,
        TemplateFallback,
        BatasRiwayatPesan,
        KirimKeWaha,
        ModeKirim
    )
    VALUES (
        'DEFAULT',
        'Pengaturan Default AI Agent',
        0,
        1,
        1,
        0,
        '08:00',
        '17:00',
        '1,2,3,4,5',
        'Asia/Jakarta',
        'OpenAI',
        'gpt-5',
        'https://api.openai.com/v1/responses',
        N'Anda adalah AI Agent customer service VPoint Care. Jawab dalam Bahasa Indonesia yang sopan, singkat, jelas, dan jangan membuat janji teknis yang belum dipastikan. Jika masalah perlu ditangani manusia, arahkan bahwa tim customer service akan menindaklanjuti.',
        N'Terima kasih sudah menghubungi VPoint Care. Saat ini kami berada di luar jam operasional. Pesan Bapak/Ibu sudah kami terima dan akan kami tindak lanjuti pada jam kerja berikutnya.',
        N'Halo, terima kasih sudah menghubungi VPoint Care. Saya bantu catat terlebih dahulu ya. Silakan jelaskan kendala yang sedang dialami, nanti tim customer service kami akan melanjutkan penanganannya.',
        N'Terima kasih informasinya. Pesan sudah kami terima dan akan kami teruskan ke tim terkait untuk ditindaklanjuti.',
        8,
        0,
        'DraftLokal'
    );
END
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'FK_TChatD_TAiRespon', 'F') IS NOT NULL
    ALTER TABLE TChatD DROP CONSTRAINT FK_TChatD_TAiRespon;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdAiRespon' AND object_id = OBJECT_ID('TChatD'))
    DROP INDEX IX_TChatD_IdAiRespon ON TChatD;

IF COL_LENGTH('TChatD', 'IdAiRespon') IS NOT NULL
    ALTER TABLE TChatD DROP COLUMN IdAiRespon;

IF COL_LENGTH('TChatD', 'DihasilkanOlehAi') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_TChatD_DihasilkanOlehAi', 'D') IS NOT NULL
        ALTER TABLE TChatD DROP CONSTRAINT DF_TChatD_DihasilkanOlehAi;
    ALTER TABLE TChatD DROP COLUMN DihasilkanOlehAi;
END

IF COL_LENGTH('TChat', 'TglAutoReplyAiTerakhir') IS NOT NULL
    ALTER TABLE TChat DROP COLUMN TglAutoReplyAiTerakhir;

IF COL_LENGTH('TChat', 'ModeAutoReplyAi') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_TChat_ModeAutoReplyAi', 'D') IS NOT NULL
        ALTER TABLE TChat DROP CONSTRAINT DF_TChat_ModeAutoReplyAi;
    ALTER TABLE TChat DROP COLUMN ModeAutoReplyAi;
END

IF COL_LENGTH('TChat', 'AiSudahMenyapa') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_TChat_AiSudahMenyapa', 'D') IS NOT NULL
        ALTER TABLE TChat DROP CONSTRAINT DF_TChat_AiSudahMenyapa;
    ALTER TABLE TChat DROP COLUMN AiSudahMenyapa;
END

IF COL_LENGTH('TChat', 'AutoReplyAiAktif') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_TChat_AutoReplyAiAktif', 'D') IS NOT NULL
        ALTER TABLE TChat DROP CONSTRAINT DF_TChat_AutoReplyAiAktif;
    ALTER TABLE TChat DROP COLUMN AutoReplyAiAktif;
END

IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
    DROP TABLE MPengaturanAi;
SQL);
    }
};
