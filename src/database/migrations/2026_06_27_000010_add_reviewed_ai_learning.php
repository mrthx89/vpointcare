<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The reviewed AI learning migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'TAiDraftPengetahuan', 'U') IS NULL
BEGIN
    CREATE TABLE TAiDraftPengetahuan (
        Id uniqueidentifier NOT NULL CONSTRAINT DF_TAiDraftPengetahuan_Id DEFAULT NEWSEQUENTIALID(),
        IdChat uniqueidentifier NULL,
        IdCustomer uniqueidentifier NULL,
        IdInstansi uniqueidentifier NULL,
        IdPengetahuan uniqueidentifier NULL,
        JudulDraft nvarchar(255) NOT NULL,
        IsiDraft nvarchar(max) NOT NULL,
        TagDraft nvarchar(500) NULL,
        KategoriDraft nvarchar(100) NULL,
        RingkasanSumber nvarchar(max) NULL,
        CuplikanSumberDisanitasi nvarchar(max) NULL,
        ConfidenceScore decimal(5,2) NULL,
        StatusReview nvarchar(30) NOT NULL CONSTRAINT DF_TAiDraftPengetahuan_StatusReview DEFAULT N'Draft',
        CatatanReviewer nvarchar(max) NULL,
        AlasanTidakLayak nvarchar(max) NULL,
        HashKonten nvarchar(64) NULL,
        ProviderAi nvarchar(50) NULL,
        ModelAi nvarchar(100) NULL,
        PromptRingkas nvarchar(max) NULL,
        ResponseJson nvarchar(max) NULL,
        DibuatOlehAi bit NOT NULL CONSTRAINT DF_TAiDraftPengetahuan_DibuatOlehAi DEFAULT 1,
        DibuatOleh uniqueidentifier NULL,
        DireviewOleh uniqueidentifier NULL,
        TglReview datetime2 NULL,
        TglBuat datetime2 NOT NULL CONSTRAINT DF_TAiDraftPengetahuan_TglBuat DEFAULT SYSDATETIME(),
        TglEdit datetime2 NULL,
        CONSTRAINT PK_TAiDraftPengetahuan PRIMARY KEY (Id)
    );
END

IF COL_LENGTH('MPengetahuan', 'SearchKeywords') IS NULL
    ALTER TABLE MPengetahuan ADD SearchKeywords nvarchar(1000) NULL;

IF COL_LENGTH('MPengetahuan', 'PrioritasAi') IS NULL
    ALTER TABLE MPengetahuan ADD PrioritasAi int NOT NULL CONSTRAINT DF_MPengetahuan_PrioritasAi DEFAULT 0;

IF COL_LENGTH('MPengetahuan', 'TerakhirDipakaiAi') IS NULL
    ALTER TABLE MPengetahuan ADD TerakhirDipakaiAi datetime2 NULL;

IF COL_LENGTH('MPengetahuan', 'JumlahDipakaiAi') IS NULL
    ALTER TABLE MPengetahuan ADD JumlahDipakaiAi int NOT NULL CONSTRAINT DF_MPengetahuan_JumlahDipakaiAi DEFAULT 0;

IF COL_LENGTH('TChat', 'ModeKnowledgeAi') IS NULL
    ALTER TABLE TChat ADD ModeKnowledgeAi varchar(30) NOT NULL CONSTRAINT DF_TChat_ModeKnowledgeAi DEFAULT 'Ringan';

IF COL_LENGTH('TChat', 'BatasKnowledgeAi') IS NULL
    ALTER TABLE TChat ADD BatasKnowledgeAi int NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TAiDraftPengetahuan_StatusReview_TglBuat' AND object_id = OBJECT_ID('TAiDraftPengetahuan'))
    CREATE INDEX IX_TAiDraftPengetahuan_StatusReview_TglBuat ON TAiDraftPengetahuan (StatusReview, TglBuat DESC);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TAiDraftPengetahuan_IdChat' AND object_id = OBJECT_ID('TAiDraftPengetahuan'))
    CREATE INDEX IX_TAiDraftPengetahuan_IdChat ON TAiDraftPengetahuan (IdChat);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TAiDraftPengetahuan_IdPengetahuan' AND object_id = OBJECT_ID('TAiDraftPengetahuan'))
    CREATE INDEX IX_TAiDraftPengetahuan_IdPengetahuan ON TAiDraftPengetahuan (IdPengetahuan);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TAiDraftPengetahuan_HashKonten' AND object_id = OBJECT_ID('TAiDraftPengetahuan'))
    CREATE INDEX IX_TAiDraftPengetahuan_HashKonten ON TAiDraftPengetahuan (HashKonten);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MPengetahuan_NonAktif_PrioritasAi' AND object_id = OBJECT_ID('MPengetahuan'))
    CREATE INDEX IX_MPengetahuan_NonAktif_PrioritasAi ON MPengetahuan (NonAktif, PrioritasAi DESC);
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'TAiDraftPengetahuan', 'U') IS NOT NULL
    DROP TABLE TAiDraftPengetahuan;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MPengetahuan_NonAktif_PrioritasAi' AND object_id = OBJECT_ID('MPengetahuan'))
    DROP INDEX IX_MPengetahuan_NonAktif_PrioritasAi ON MPengetahuan;

IF OBJECT_ID(N'DF_TChat_ModeKnowledgeAi', 'D') IS NOT NULL
    ALTER TABLE TChat DROP CONSTRAINT DF_TChat_ModeKnowledgeAi;

IF COL_LENGTH('TChat', 'BatasKnowledgeAi') IS NOT NULL
    ALTER TABLE TChat DROP COLUMN BatasKnowledgeAi;

IF COL_LENGTH('TChat', 'ModeKnowledgeAi') IS NOT NULL
    ALTER TABLE TChat DROP COLUMN ModeKnowledgeAi;

IF OBJECT_ID(N'DF_MPengetahuan_PrioritasAi', 'D') IS NOT NULL
    ALTER TABLE MPengetahuan DROP CONSTRAINT DF_MPengetahuan_PrioritasAi;

IF OBJECT_ID(N'DF_MPengetahuan_JumlahDipakaiAi', 'D') IS NOT NULL
    ALTER TABLE MPengetahuan DROP CONSTRAINT DF_MPengetahuan_JumlahDipakaiAi;

IF COL_LENGTH('MPengetahuan', 'JumlahDipakaiAi') IS NOT NULL
    ALTER TABLE MPengetahuan DROP COLUMN JumlahDipakaiAi;

IF COL_LENGTH('MPengetahuan', 'TerakhirDipakaiAi') IS NOT NULL
    ALTER TABLE MPengetahuan DROP COLUMN TerakhirDipakaiAi;

IF COL_LENGTH('MPengetahuan', 'PrioritasAi') IS NOT NULL
    ALTER TABLE MPengetahuan DROP COLUMN PrioritasAi;

IF COL_LENGTH('MPengetahuan', 'SearchKeywords') IS NOT NULL
    ALTER TABLE MPengetahuan DROP COLUMN SearchKeywords;
SQL);
    }
};
