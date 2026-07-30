<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The WAHA identity snapshot migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'TChat', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('TChat', 'NamaKontakWaha') IS NULL
    BEGIN
        ALTER TABLE TChat ADD NamaKontakWaha nvarchar(150) NULL;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'NamaKontakWaha';
    END

    IF COL_LENGTH('TChat', 'NamaGrupWaha') IS NULL
    BEGIN
        ALTER TABLE TChat ADD NamaGrupWaha nvarchar(200) NULL;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'NamaGrupWaha';
    END

    IF COL_LENGTH('TChat', 'TglIdentitasWahaDiambil') IS NULL
    BEGIN
        ALTER TABLE TChat ADD TglIdentitasWahaDiambil datetime2 NULL;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'TglIdentitasWahaDiambil';
    END

    IF COL_LENGTH('TChat', 'StatusIdentitasWaha') IS NULL
    BEGIN
        ALTER TABLE TChat ADD StatusIdentitasWaha varchar(30) NULL;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'StatusIdentitasWaha';
    END

    IF COL_LENGTH('TChat', 'PesanErrorIdentitasWaha') IS NULL
    BEGIN
        ALTER TABLE TChat ADD PesanErrorIdentitasWaha nvarchar(500) NULL;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'PesanErrorIdentitasWaha';
    END
END

IF OBJECT_ID(N'TChatD', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('TChatD', 'PengirimIdWaha') IS NULL
    BEGIN
        ALTER TABLE TChatD ADD PengirimIdWaha varchar(200) NULL;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChatD', @level2type = N'COLUMN', @level2name = N'PengirimIdWaha';
    END

    IF COL_LENGTH('TChatD', 'UrlFotoProfilPengirim') IS NULL
    BEGIN
        ALTER TABLE TChatD ADD UrlFotoProfilPengirim nvarchar(1000) NULL;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChatD', @level2type = N'COLUMN', @level2name = N'UrlFotoProfilPengirim';
    END

    IF COL_LENGTH('TChatD', 'TglFotoProfilPengirimDiambil') IS NULL
    BEGIN
        ALTER TABLE TChatD ADD TglFotoProfilPengirimDiambil datetime2 NULL;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChatD', @level2type = N'COLUMN', @level2name = N'TglFotoProfilPengirimDiambil';
    END
END

IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'BatasSesiAutoReplyMenit') IS NULL
    BEGIN
        ALTER TABLE MPengaturanAi ADD BatasSesiAutoReplyMenit int NOT NULL CONSTRAINT DF_MPengaturanAi_BatasSesiAutoReplyMenit DEFAULT 60;
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'MPengaturanAi', @level2type = N'COLUMN', @level2name = N'BatasSesiAutoReplyMenit';
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'MPengaturanAi', @level2type = N'CONSTRAINT', @level2name = N'DF_MPengaturanAi_BatasSesiAutoReplyMenit';
    END

    IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'MPengaturanAi') AND c.name = N'BatasSesiAutoReplyMenit')
        AND NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_MPengaturanAi_BatasSesiAutoReplyMenit' AND parent_object_id = OBJECT_ID('MPengaturanAi'))
    BEGIN
        ALTER TABLE MPengaturanAi ADD CONSTRAINT CK_MPengaturanAi_BatasSesiAutoReplyMenit CHECK (BatasSesiAutoReplyMenit BETWEEN 1 AND 1440);
        EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'MPengaturanAi', @level2type = N'CONSTRAINT', @level2name = N'CK_MPengaturanAi_BatasSesiAutoReplyMenit';
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChat') AND c.name = N'StatusIdentitasWaha')
    AND EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChat') AND c.name = N'TglIdentitasWahaDiambil')
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_StatusIdentitasWaha_TglIdentitasWahaDiambil' AND object_id = OBJECT_ID('TChat'))
BEGIN
    CREATE INDEX IX_TChat_StatusIdentitasWaha_TglIdentitasWahaDiambil ON TChat (StatusIdentitasWaha, TglIdentitasWahaDiambil);
    EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-identity-snapshot', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'INDEX', @level2name = N'IX_TChat_StatusIdentitasWaha_TglIdentitasWahaDiambil';
END

IF OBJECT_ID(N'TChat', 'U') IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_SesiJenisNomorWhatsapp' AND object_id = OBJECT_ID('TChat'))
BEGIN
    CREATE INDEX IX_TChat_SesiJenisNomorWhatsapp ON TChat (IdSesiWhatsapp, JenisChat, NomorWhatsapp);
    EXEC sys.sp_addextendedproperty @name = N'WACS_Migration_20260730_000001', @value = N'waha-group-conversation-lock', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'INDEX', @level2name = N'IX_TChat_SesiJenisNomorWhatsapp';
END
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.indexes AS i ON i.object_id = p.major_id AND i.index_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND i.object_id = OBJECT_ID(N'TChat') AND i.name = N'IX_TChat_SesiJenisNomorWhatsapp')
BEGIN
    EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'INDEX', @level2name = N'IX_TChat_SesiJenisNomorWhatsapp';
    DROP INDEX IX_TChat_SesiJenisNomorWhatsapp ON TChat;
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.indexes AS i ON i.object_id = p.major_id AND i.index_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND i.object_id = OBJECT_ID(N'TChat') AND i.name = N'IX_TChat_StatusIdentitasWaha_TglIdentitasWahaDiambil')
BEGIN
    EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'INDEX', @level2name = N'IX_TChat_StatusIdentitasWaha_TglIdentitasWahaDiambil';
    DROP INDEX IX_TChat_StatusIdentitasWaha_TglIdentitasWahaDiambil ON TChat;
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'MPengaturanAi') AND c.name = N'BatasSesiAutoReplyMenit')
BEGIN
    IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.objects AS o ON o.object_id = p.major_id WHERE p.name = N'WACS_Migration_20260730_000001' AND o.name = N'CK_MPengaturanAi_BatasSesiAutoReplyMenit' AND o.parent_object_id = OBJECT_ID(N'MPengaturanAi'))
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'MPengaturanAi', @level2type = N'CONSTRAINT', @level2name = N'CK_MPengaturanAi_BatasSesiAutoReplyMenit';
        ALTER TABLE MPengaturanAi DROP CONSTRAINT CK_MPengaturanAi_BatasSesiAutoReplyMenit;
    END

    IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.objects AS o ON o.object_id = p.major_id WHERE p.name = N'WACS_Migration_20260730_000001' AND o.name = N'DF_MPengaturanAi_BatasSesiAutoReplyMenit' AND o.parent_object_id = OBJECT_ID(N'MPengaturanAi'))
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'MPengaturanAi', @level2type = N'CONSTRAINT', @level2name = N'DF_MPengaturanAi_BatasSesiAutoReplyMenit';
        ALTER TABLE MPengaturanAi DROP CONSTRAINT DF_MPengaturanAi_BatasSesiAutoReplyMenit;
    END

    IF COL_LENGTH('MPengaturanAi', 'BatasSesiAutoReplyMenit') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'MPengaturanAi', @level2type = N'COLUMN', @level2name = N'BatasSesiAutoReplyMenit';
        ALTER TABLE MPengaturanAi DROP COLUMN BatasSesiAutoReplyMenit;
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChatD') AND c.name = N'TglFotoProfilPengirimDiambil')
BEGIN
    IF COL_LENGTH('TChatD', 'TglFotoProfilPengirimDiambil') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChatD', @level2type = N'COLUMN', @level2name = N'TglFotoProfilPengirimDiambil';
        ALTER TABLE TChatD DROP COLUMN TglFotoProfilPengirimDiambil;
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChatD') AND c.name = N'UrlFotoProfilPengirim')
BEGIN
    IF COL_LENGTH('TChatD', 'UrlFotoProfilPengirim') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChatD', @level2type = N'COLUMN', @level2name = N'UrlFotoProfilPengirim';
        ALTER TABLE TChatD DROP COLUMN UrlFotoProfilPengirim;
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChatD') AND c.name = N'PengirimIdWaha')
BEGIN
    IF COL_LENGTH('TChatD', 'PengirimIdWaha') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChatD', @level2type = N'COLUMN', @level2name = N'PengirimIdWaha';
        ALTER TABLE TChatD DROP COLUMN PengirimIdWaha;
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChat') AND c.name = N'PesanErrorIdentitasWaha')
BEGIN
    IF COL_LENGTH('TChat', 'PesanErrorIdentitasWaha') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'PesanErrorIdentitasWaha';
        ALTER TABLE TChat DROP COLUMN PesanErrorIdentitasWaha;
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChat') AND c.name = N'StatusIdentitasWaha')
BEGIN
    IF COL_LENGTH('TChat', 'StatusIdentitasWaha') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'StatusIdentitasWaha';
        ALTER TABLE TChat DROP COLUMN StatusIdentitasWaha;
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChat') AND c.name = N'TglIdentitasWahaDiambil')
BEGIN
    IF COL_LENGTH('TChat', 'TglIdentitasWahaDiambil') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'TglIdentitasWahaDiambil';
        ALTER TABLE TChat DROP COLUMN TglIdentitasWahaDiambil;
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChat') AND c.name = N'NamaGrupWaha')
BEGIN
    IF COL_LENGTH('TChat', 'NamaGrupWaha') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'NamaGrupWaha';
        ALTER TABLE TChat DROP COLUMN NamaGrupWaha;
    END
END

IF EXISTS (SELECT 1 FROM sys.extended_properties AS p INNER JOIN sys.columns AS c ON c.object_id = p.major_id AND c.column_id = p.minor_id WHERE p.name = N'WACS_Migration_20260730_000001' AND c.object_id = OBJECT_ID(N'TChat') AND c.name = N'NamaKontakWaha')
BEGIN
    IF COL_LENGTH('TChat', 'NamaKontakWaha') IS NOT NULL
    BEGIN
        EXEC sys.sp_dropextendedproperty @name = N'WACS_Migration_20260730_000001', @level0type = N'SCHEMA', @level0name = N'dbo', @level1type = N'TABLE', @level1name = N'TChat', @level2type = N'COLUMN', @level2name = N'NamaKontakWaha';
        ALTER TABLE TChat DROP COLUMN NamaKontakWaha;
    END
END
SQL);
    }
};
