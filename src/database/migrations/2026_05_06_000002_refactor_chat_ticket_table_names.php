<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The chat and ticket table refactor requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
SET XACT_ABORT ON;

BEGIN TRANSACTION;

DECLARE @sql nvarchar(max);

DECLARE @dropForeignKeys table (Name sysname, TableName sysname);
INSERT INTO @dropForeignKeys (Name, TableName)
VALUES
    ('FK_TChatD_TChatM', 'TChatD'),
    ('FK_TChatPenugasan_TChatM', 'TChatPenugasan'),
    ('FK_TChatCatatanInternal_TChatM', 'TChatCatatanInternal'),
    ('FK_TTicketM_TChatM', 'TTicketM'),
    ('FK_TTicketM_TChatD', 'TTicketM'),
    ('FK_TTicketD_TTicketM', 'TTicketD'),
    ('FK_TTicketPenugasan_TTicketM', 'TTicketPenugasan'),
    ('FK_TTicketLampiran_TTicketM', 'TTicketLampiran'),
    ('FK_TAiPermintaan_TChatM', 'TAiPermintaan'),
    ('FK_TAiPermintaan_TTicketM', 'TAiPermintaan');

DECLARE @fkName sysname;
DECLARE @fkTable sysname;
DECLARE fk_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT Name, TableName FROM @dropForeignKeys;
OPEN fk_cursor;
FETCH NEXT FROM fk_cursor INTO @fkName, @fkTable;
WHILE @@FETCH_STATUS = 0
BEGIN
    IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = @fkName)
    BEGIN
        SET @sql = N'ALTER TABLE ' + QUOTENAME(@fkTable) + N' DROP CONSTRAINT ' + QUOTENAME(@fkName);
        EXEC sp_executesql @sql;
    END;

    FETCH NEXT FROM fk_cursor INTO @fkName, @fkTable;
END;
CLOSE fk_cursor;
DEALLOCATE fk_cursor;

DECLARE @dropIndexes table (Name sysname, TableName sysname);
INSERT INTO @dropIndexes (Name, TableName)
VALUES
    ('IX_TChatM_NomorWhatsapp', 'TChatM'),
    ('IX_TChatM_IdWahaTerdeteksi', 'TChatM'),
    ('IX_TChatM_NomorWhatsappTerdeteksi', 'TChatM'),
    ('IX_TChatM_IdCustomer', 'TChatM'),
    ('IX_TChatM_IdInstansi', 'TChatM'),
    ('IX_TChatM_IdGrupWhatsapp', 'TChatM'),
    ('IX_TChatM_IdStatusChat', 'TChatM'),
    ('IX_TChatM_DitugaskanKepada', 'TChatM'),
    ('IX_TChatM_TglChatTerakhir', 'TChatM'),
    ('IX_TChatD_IdChatM_TglPesan', 'TChatD'),
    ('IX_TTicketM_IdCustomer', 'TTicketM'),
    ('IX_TTicketM_IdInstansi', 'TTicketM'),
    ('IX_TTicketM_IdStatusTicket', 'TTicketM'),
    ('IX_TTicketM_DitugaskanKepada', 'TTicketM'),
    ('IX_TTicketM_TglTargetSelesai', 'TTicketM'),
    ('IX_TTicketD_IdTicketM_TglAktivitas', 'TTicketD'),
    ('IX_TAiPermintaan_IdChatM', 'TAiPermintaan'),
    ('IX_TAiPermintaan_IdTicketM', 'TAiPermintaan');

DECLARE @idxName sysname;
DECLARE @idxTable sysname;
DECLARE idx_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT Name, TableName FROM @dropIndexes;
OPEN idx_cursor;
FETCH NEXT FROM idx_cursor INTO @idxName, @idxTable;
WHILE @@FETCH_STATUS = 0
BEGIN
    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = @idxName AND object_id = OBJECT_ID(@idxTable))
    BEGIN
        SET @sql = N'DROP INDEX ' + QUOTENAME(@idxName) + N' ON ' + QUOTENAME(@idxTable);
        EXEC sp_executesql @sql;
    END;

    FETCH NEXT FROM idx_cursor INTO @idxName, @idxTable;
END;
CLOSE idx_cursor;
DEALLOCATE idx_cursor;

IF OBJECT_ID(N'TChatM', 'U') IS NOT NULL AND OBJECT_ID(N'TChat', 'U') IS NULL
    EXEC sp_rename 'TChatM', 'TChat';

IF OBJECT_ID(N'TChatCatatanInternal', 'U') IS NOT NULL AND OBJECT_ID(N'TChatDCatatanInternal', 'U') IS NULL
    EXEC sp_rename 'TChatCatatanInternal', 'TChatDCatatanInternal';

IF OBJECT_ID(N'TChatPenugasan', 'U') IS NOT NULL AND OBJECT_ID(N'TChatDPenugasan', 'U') IS NULL
    EXEC sp_rename 'TChatPenugasan', 'TChatDPenugasan';

IF OBJECT_ID(N'TTicketM', 'U') IS NOT NULL AND OBJECT_ID(N'TTicket', 'U') IS NULL
    EXEC sp_rename 'TTicketM', 'TTicket';

IF OBJECT_ID(N'TTicketLampiran', 'U') IS NOT NULL AND OBJECT_ID(N'TTicketDLampiran', 'U') IS NULL
    EXEC sp_rename 'TTicketLampiran', 'TTicketDLampiran';

IF OBJECT_ID(N'TTicketPenugasan', 'U') IS NOT NULL AND OBJECT_ID(N'TTicketDPenugasan', 'U') IS NULL
    EXEC sp_rename 'TTicketPenugasan', 'TTicketDPenugasan';

IF COL_LENGTH('TChatD', 'IdChatM') IS NOT NULL AND COL_LENGTH('TChatD', 'IdChat') IS NULL
    EXEC sp_rename 'TChatD.IdChatM', 'IdChat', 'COLUMN';

IF COL_LENGTH('TChatDCatatanInternal', 'IdChatM') IS NOT NULL AND COL_LENGTH('TChatDCatatanInternal', 'IdChat') IS NULL
    EXEC sp_rename 'TChatDCatatanInternal.IdChatM', 'IdChat', 'COLUMN';

IF COL_LENGTH('TChatDPenugasan', 'IdChatM') IS NOT NULL AND COL_LENGTH('TChatDPenugasan', 'IdChat') IS NULL
    EXEC sp_rename 'TChatDPenugasan.IdChatM', 'IdChat', 'COLUMN';

IF COL_LENGTH('TTicket', 'IdChatM') IS NOT NULL AND COL_LENGTH('TTicket', 'IdChat') IS NULL
    EXEC sp_rename 'TTicket.IdChatM', 'IdChat', 'COLUMN';

IF COL_LENGTH('TTicketD', 'IdTicketM') IS NOT NULL AND COL_LENGTH('TTicketD', 'IdTicket') IS NULL
    EXEC sp_rename 'TTicketD.IdTicketM', 'IdTicket', 'COLUMN';

IF COL_LENGTH('TTicketDLampiran', 'IdTicketM') IS NOT NULL AND COL_LENGTH('TTicketDLampiran', 'IdTicket') IS NULL
    EXEC sp_rename 'TTicketDLampiran.IdTicketM', 'IdTicket', 'COLUMN';

IF COL_LENGTH('TTicketDPenugasan', 'IdTicketM') IS NOT NULL AND COL_LENGTH('TTicketDPenugasan', 'IdTicket') IS NULL
    EXEC sp_rename 'TTicketDPenugasan.IdTicketM', 'IdTicket', 'COLUMN';

IF COL_LENGTH('TAiPermintaan', 'IdChatM') IS NOT NULL AND COL_LENGTH('TAiPermintaan', 'IdChat') IS NULL
    EXEC sp_rename 'TAiPermintaan.IdChatM', 'IdChat', 'COLUMN';

IF COL_LENGTH('TAiPermintaan', 'IdTicketM') IS NOT NULL AND COL_LENGTH('TAiPermintaan', 'IdTicket') IS NULL
    EXEC sp_rename 'TAiPermintaan.IdTicketM', 'IdTicket', 'COLUMN';

DECLARE @renameObjects table (OldName sysname, NewName sysname);
INSERT INTO @renameObjects (OldName, NewName)
VALUES
    ('PK_TChatM', 'PK_TChat'),
    ('DF_TChatM_Id', 'DF_TChat_Id'),
    ('DF_TChatM_JenisChat', 'DF_TChat_JenisChat'),
    ('DF_TChatM_Prioritas', 'DF_TChat_Prioritas'),
    ('DF_TChatM_JumlahPesanBelumDibaca', 'DF_TChat_JumlahPesanBelumDibaca'),
    ('DF_TChatM_AutoReplyAiAktif', 'DF_TChat_AutoReplyAiAktif'),
    ('DF_TChatM_AiSudahMenyapa', 'DF_TChat_AiSudahMenyapa'),
    ('DF_TChatM_ModeAutoReplyAi', 'DF_TChat_ModeAutoReplyAi'),
    ('DF_TChatM_JumlahNotifikasiBelumTerbalas', 'DF_TChat_JumlahNotifikasiBelumTerbalas'),
    ('DF_TChatM_TglBuat', 'DF_TChat_TglBuat'),
    ('FK_TChatM_MSesiWhatsapp', 'FK_TChat_MSesiWhatsapp'),
    ('FK_TChatM_MStatusChat', 'FK_TChat_MStatusChat'),
    ('FK_TChatM_MCustomer', 'FK_TChat_MCustomer'),
    ('FK_TChatM_MInstansi', 'FK_TChat_MInstansi'),
    ('FK_TChatM_MNomorWhatsapp', 'FK_TChat_MNomorWhatsapp'),
    ('FK_TChatM_MGrupWhatsapp', 'FK_TChat_MGrupWhatsapp'),
    ('PK_TChatPenugasan', 'PK_TChatDPenugasan'),
    ('DF_TChatPenugasan_Id', 'DF_TChatDPenugasan_Id'),
    ('DF_TChatPenugasan_TglPenugasan', 'DF_TChatDPenugasan_TglPenugasan'),
    ('DF_TChatPenugasan_TglBuat', 'DF_TChatDPenugasan_TglBuat'),
    ('PK_TChatCatatanInternal', 'PK_TChatDCatatanInternal'),
    ('DF_TChatCatatanInternal_Id', 'DF_TChatDCatatanInternal_Id'),
    ('DF_TChatCatatanInternal_TglBuat', 'DF_TChatDCatatanInternal_TglBuat'),
    ('PK_TTicketM', 'PK_TTicket'),
    ('DF_TTicketM_Id', 'DF_TTicket_Id'),
    ('DF_TTicketM_TglBuat', 'DF_TTicket_TglBuat'),
    ('UQ_TTicketM_NomorTicket', 'UQ_TTicket_NomorTicket'),
    ('FK_TTicketM_MCustomer', 'FK_TTicket_MCustomer'),
    ('FK_TTicketM_MInstansi', 'FK_TTicket_MInstansi'),
    ('FK_TTicketM_MKategoriTicket', 'FK_TTicket_MKategoriTicket'),
    ('FK_TTicketM_MPrioritasTicket', 'FK_TTicket_MPrioritasTicket'),
    ('FK_TTicketM_MStatusTicket', 'FK_TTicket_MStatusTicket'),
    ('PK_TTicketPenugasan', 'PK_TTicketDPenugasan'),
    ('DF_TTicketPenugasan_Id', 'DF_TTicketDPenugasan_Id'),
    ('DF_TTicketPenugasan_TglPenugasan', 'DF_TTicketDPenugasan_TglPenugasan'),
    ('DF_TTicketPenugasan_TglBuat', 'DF_TTicketDPenugasan_TglBuat'),
    ('PK_TTicketLampiran', 'PK_TTicketDLampiran'),
    ('DF_TTicketLampiran_Id', 'DF_TTicketDLampiran_Id'),
    ('DF_TTicketLampiran_TglBuat', 'DF_TTicketDLampiran_TglBuat');

DECLARE @oldObject sysname;
DECLARE @newObject sysname;
DECLARE @qualifiedOldObject nvarchar(517);
DECLARE obj_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT OldName, NewName FROM @renameObjects;
OPEN obj_cursor;
FETCH NEXT FROM obj_cursor INTO @oldObject, @newObject;
WHILE @@FETCH_STATUS = 0
BEGIN
    SET @qualifiedOldObject = NULL;

    SELECT TOP 1 @qualifiedOldObject = QUOTENAME(SCHEMA_NAME(schema_id)) + N'.' + QUOTENAME(name)
    FROM sys.objects
    WHERE name = @oldObject;

    IF @qualifiedOldObject IS NOT NULL
        AND NOT EXISTS (SELECT 1 FROM sys.objects WHERE name = @newObject)
    BEGIN
        EXEC sp_rename @qualifiedOldObject, @newObject, 'OBJECT';
    END;

    FETCH NEXT FROM obj_cursor INTO @oldObject, @newObject;
END;
CLOSE obj_cursor;
DEALLOCATE obj_cursor;

IF OBJECT_ID(N'FK_TChat_MSesiWhatsapp', 'F') IS NULL AND OBJECT_ID(N'TChat', 'U') IS NOT NULL
    ALTER TABLE TChat ADD CONSTRAINT FK_TChat_MSesiWhatsapp FOREIGN KEY (IdSesiWhatsapp) REFERENCES MSesiWhatsapp(Id);
IF OBJECT_ID(N'FK_TChat_MStatusChat', 'F') IS NULL AND OBJECT_ID(N'TChat', 'U') IS NOT NULL
    ALTER TABLE TChat ADD CONSTRAINT FK_TChat_MStatusChat FOREIGN KEY (IdStatusChat) REFERENCES MStatusChat(Id);
IF OBJECT_ID(N'FK_TChat_MCustomer', 'F') IS NULL AND OBJECT_ID(N'TChat', 'U') IS NOT NULL
    ALTER TABLE TChat ADD CONSTRAINT FK_TChat_MCustomer FOREIGN KEY (IdCustomer) REFERENCES MCustomer(Id);
IF OBJECT_ID(N'FK_TChat_MInstansi', 'F') IS NULL AND OBJECT_ID(N'TChat', 'U') IS NOT NULL
    ALTER TABLE TChat ADD CONSTRAINT FK_TChat_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id);
IF OBJECT_ID(N'FK_TChat_MNomorWhatsapp', 'F') IS NULL AND OBJECT_ID(N'TChat', 'U') IS NOT NULL
    ALTER TABLE TChat ADD CONSTRAINT FK_TChat_MNomorWhatsapp FOREIGN KEY (IdNomorWhatsapp) REFERENCES MNomorWhatsapp(Id);
IF OBJECT_ID(N'FK_TChat_MGrupWhatsapp', 'F') IS NULL AND OBJECT_ID(N'TChat', 'U') IS NOT NULL AND COL_LENGTH('TChat', 'IdGrupWhatsapp') IS NOT NULL
    ALTER TABLE TChat ADD CONSTRAINT FK_TChat_MGrupWhatsapp FOREIGN KEY (IdGrupWhatsapp) REFERENCES MGrupWhatsapp(Id);

IF OBJECT_ID(N'FK_TChatD_TChat', 'F') IS NULL AND COL_LENGTH('TChatD', 'IdChat') IS NOT NULL
    ALTER TABLE TChatD ADD CONSTRAINT FK_TChatD_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id);
IF OBJECT_ID(N'FK_TChatDPenugasan_TChat', 'F') IS NULL AND OBJECT_ID(N'TChatDPenugasan', 'U') IS NOT NULL
    ALTER TABLE TChatDPenugasan ADD CONSTRAINT FK_TChatDPenugasan_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id);
IF OBJECT_ID(N'FK_TChatDCatatanInternal_TChat', 'F') IS NULL AND OBJECT_ID(N'TChatDCatatanInternal', 'U') IS NOT NULL
    ALTER TABLE TChatDCatatanInternal ADD CONSTRAINT FK_TChatDCatatanInternal_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id);

IF OBJECT_ID(N'FK_TTicket_TChat', 'F') IS NULL AND OBJECT_ID(N'TTicket', 'U') IS NOT NULL
    ALTER TABLE TTicket ADD CONSTRAINT FK_TTicket_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id);
IF OBJECT_ID(N'FK_TTicket_MCustomer', 'F') IS NULL AND OBJECT_ID(N'TTicket', 'U') IS NOT NULL
    ALTER TABLE TTicket ADD CONSTRAINT FK_TTicket_MCustomer FOREIGN KEY (IdCustomer) REFERENCES MCustomer(Id);
IF OBJECT_ID(N'FK_TTicket_MInstansi', 'F') IS NULL AND OBJECT_ID(N'TTicket', 'U') IS NOT NULL
    ALTER TABLE TTicket ADD CONSTRAINT FK_TTicket_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id);
IF OBJECT_ID(N'FK_TTicket_MKategoriTicket', 'F') IS NULL AND OBJECT_ID(N'TTicket', 'U') IS NOT NULL
    ALTER TABLE TTicket ADD CONSTRAINT FK_TTicket_MKategoriTicket FOREIGN KEY (IdKategoriTicket) REFERENCES MKategoriTicket(Id);
IF OBJECT_ID(N'FK_TTicket_MPrioritasTicket', 'F') IS NULL AND OBJECT_ID(N'TTicket', 'U') IS NOT NULL
    ALTER TABLE TTicket ADD CONSTRAINT FK_TTicket_MPrioritasTicket FOREIGN KEY (IdPrioritasTicket) REFERENCES MPrioritasTicket(Id);
IF OBJECT_ID(N'FK_TTicket_MStatusTicket', 'F') IS NULL AND OBJECT_ID(N'TTicket', 'U') IS NOT NULL
    ALTER TABLE TTicket ADD CONSTRAINT FK_TTicket_MStatusTicket FOREIGN KEY (IdStatusTicket) REFERENCES MStatusTicket(Id);
IF OBJECT_ID(N'FK_TTicket_TChatD', 'F') IS NULL AND OBJECT_ID(N'TTicket', 'U') IS NOT NULL
    ALTER TABLE TTicket ADD CONSTRAINT FK_TTicket_TChatD FOREIGN KEY (DibuatDariPesanId) REFERENCES TChatD(Id);
IF OBJECT_ID(N'FK_TTicketD_TTicket', 'F') IS NULL AND COL_LENGTH('TTicketD', 'IdTicket') IS NOT NULL
    ALTER TABLE TTicketD ADD CONSTRAINT FK_TTicketD_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id);
IF OBJECT_ID(N'FK_TTicketDPenugasan_TTicket', 'F') IS NULL AND OBJECT_ID(N'TTicketDPenugasan', 'U') IS NOT NULL
    ALTER TABLE TTicketDPenugasan ADD CONSTRAINT FK_TTicketDPenugasan_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id);
IF OBJECT_ID(N'FK_TTicketDLampiran_TTicket', 'F') IS NULL AND OBJECT_ID(N'TTicketDLampiran', 'U') IS NOT NULL
    ALTER TABLE TTicketDLampiran ADD CONSTRAINT FK_TTicketDLampiran_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id);

IF OBJECT_ID(N'FK_TAiPermintaan_TChat', 'F') IS NULL AND COL_LENGTH('TAiPermintaan', 'IdChat') IS NOT NULL
    ALTER TABLE TAiPermintaan ADD CONSTRAINT FK_TAiPermintaan_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id);
IF OBJECT_ID(N'FK_TAiPermintaan_TTicket', 'F') IS NULL AND COL_LENGTH('TAiPermintaan', 'IdTicket') IS NOT NULL
    ALTER TABLE TAiPermintaan ADD CONSTRAINT FK_TAiPermintaan_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_NomorWhatsapp' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_NomorWhatsapp ON TChat (NomorWhatsapp);
IF COL_LENGTH('TChat', 'IdWahaTerdeteksi') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_IdWahaTerdeteksi' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_IdWahaTerdeteksi ON TChat (IdWahaTerdeteksi);
IF COL_LENGTH('TChat', 'NomorWhatsappTerdeteksi') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_NomorWhatsappTerdeteksi' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_NomorWhatsappTerdeteksi ON TChat (NomorWhatsappTerdeteksi);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_IdCustomer' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_IdCustomer ON TChat (IdCustomer);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_IdInstansi' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_IdInstansi ON TChat (IdInstansi);
IF COL_LENGTH('TChat', 'IdGrupWhatsapp') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_IdGrupWhatsapp' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_IdGrupWhatsapp ON TChat (IdGrupWhatsapp);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_IdStatusChat' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_IdStatusChat ON TChat (IdStatusChat);
IF COL_LENGTH('TChat', 'DitugaskanKepada') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_DitugaskanKepada' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_DitugaskanKepada ON TChat (DitugaskanKepada);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_TglChatTerakhir' AND object_id = OBJECT_ID('TChat'))
    CREATE INDEX IX_TChat_TglChatTerakhir ON TChat (TglChatTerakhir);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdChat_TglPesan' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_IdChat_TglPesan ON TChatD (IdChat, TglPesan);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicket_IdCustomer' AND object_id = OBJECT_ID('TTicket'))
    CREATE INDEX IX_TTicket_IdCustomer ON TTicket (IdCustomer);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicket_IdInstansi' AND object_id = OBJECT_ID('TTicket'))
    CREATE INDEX IX_TTicket_IdInstansi ON TTicket (IdInstansi);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicket_IdStatusTicket' AND object_id = OBJECT_ID('TTicket'))
    CREATE INDEX IX_TTicket_IdStatusTicket ON TTicket (IdStatusTicket);
IF COL_LENGTH('TTicket', 'DitugaskanKepada') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicket_DitugaskanKepada' AND object_id = OBJECT_ID('TTicket'))
    CREATE INDEX IX_TTicket_DitugaskanKepada ON TTicket (DitugaskanKepada);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicket_TglTargetSelesai' AND object_id = OBJECT_ID('TTicket'))
    CREATE INDEX IX_TTicket_TglTargetSelesai ON TTicket (TglTargetSelesai);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicketD_IdTicket_TglAktivitas' AND object_id = OBJECT_ID('TTicketD'))
    CREATE INDEX IX_TTicketD_IdTicket_TglAktivitas ON TTicketD (IdTicket, TglAktivitas);
IF COL_LENGTH('TAiPermintaan', 'IdChat') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TAiPermintaan_IdChat' AND object_id = OBJECT_ID('TAiPermintaan'))
    CREATE INDEX IX_TAiPermintaan_IdChat ON TAiPermintaan (IdChat);
IF COL_LENGTH('TAiPermintaan', 'IdTicket') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TAiPermintaan_IdTicket' AND object_id = OBJECT_ID('TAiPermintaan'))
    CREATE INDEX IX_TAiPermintaan_IdTicket ON TAiPermintaan (IdTicket);

COMMIT TRANSACTION;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
SET XACT_ABORT ON;

BEGIN TRANSACTION;

DECLARE @sql nvarchar(max);

DECLARE @dropForeignKeys table (Name sysname, TableName sysname);
INSERT INTO @dropForeignKeys (Name, TableName)
VALUES
    ('FK_TChatD_TChat', 'TChatD'),
    ('FK_TChatDPenugasan_TChat', 'TChatDPenugasan'),
    ('FK_TChatDCatatanInternal_TChat', 'TChatDCatatanInternal'),
    ('FK_TTicket_TChat', 'TTicket'),
    ('FK_TTicket_TChatD', 'TTicket'),
    ('FK_TChat_MSesiWhatsapp', 'TChat'),
    ('FK_TChat_MStatusChat', 'TChat'),
    ('FK_TChat_MCustomer', 'TChat'),
    ('FK_TChat_MInstansi', 'TChat'),
    ('FK_TChat_MNomorWhatsapp', 'TChat'),
    ('FK_TChat_MGrupWhatsapp', 'TChat'),
    ('FK_TTicket_MCustomer', 'TTicket'),
    ('FK_TTicket_MInstansi', 'TTicket'),
    ('FK_TTicket_MKategoriTicket', 'TTicket'),
    ('FK_TTicket_MPrioritasTicket', 'TTicket'),
    ('FK_TTicket_MStatusTicket', 'TTicket'),
    ('FK_TTicketD_TTicket', 'TTicketD'),
    ('FK_TTicketDPenugasan_TTicket', 'TTicketDPenugasan'),
    ('FK_TTicketDLampiran_TTicket', 'TTicketDLampiran'),
    ('FK_TAiPermintaan_TChat', 'TAiPermintaan'),
    ('FK_TAiPermintaan_TTicket', 'TAiPermintaan');

DECLARE @fkName sysname;
DECLARE @fkTable sysname;
DECLARE fk_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT Name, TableName FROM @dropForeignKeys;
OPEN fk_cursor;
FETCH NEXT FROM fk_cursor INTO @fkName, @fkTable;
WHILE @@FETCH_STATUS = 0
BEGIN
    IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = @fkName)
    BEGIN
        SET @sql = N'ALTER TABLE ' + QUOTENAME(@fkTable) + N' DROP CONSTRAINT ' + QUOTENAME(@fkName);
        EXEC sp_executesql @sql;
    END;

    FETCH NEXT FROM fk_cursor INTO @fkName, @fkTable;
END;
CLOSE fk_cursor;
DEALLOCATE fk_cursor;

DECLARE @dropIndexes table (Name sysname, TableName sysname);
INSERT INTO @dropIndexes (Name, TableName)
VALUES
    ('IX_TChat_NomorWhatsapp', 'TChat'),
    ('IX_TChat_IdWahaTerdeteksi', 'TChat'),
    ('IX_TChat_NomorWhatsappTerdeteksi', 'TChat'),
    ('IX_TChat_IdCustomer', 'TChat'),
    ('IX_TChat_IdInstansi', 'TChat'),
    ('IX_TChat_IdGrupWhatsapp', 'TChat'),
    ('IX_TChat_IdStatusChat', 'TChat'),
    ('IX_TChat_DitugaskanKepada', 'TChat'),
    ('IX_TChat_TglChatTerakhir', 'TChat'),
    ('IX_TChatD_IdChat_TglPesan', 'TChatD'),
    ('IX_TTicket_IdCustomer', 'TTicket'),
    ('IX_TTicket_IdInstansi', 'TTicket'),
    ('IX_TTicket_IdStatusTicket', 'TTicket'),
    ('IX_TTicket_DitugaskanKepada', 'TTicket'),
    ('IX_TTicket_TglTargetSelesai', 'TTicket'),
    ('IX_TTicketD_IdTicket_TglAktivitas', 'TTicketD'),
    ('IX_TAiPermintaan_IdChat', 'TAiPermintaan'),
    ('IX_TAiPermintaan_IdTicket', 'TAiPermintaan');

DECLARE @idxName sysname;
DECLARE @idxTable sysname;
DECLARE idx_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT Name, TableName FROM @dropIndexes;
OPEN idx_cursor;
FETCH NEXT FROM idx_cursor INTO @idxName, @idxTable;
WHILE @@FETCH_STATUS = 0
BEGIN
    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = @idxName AND object_id = OBJECT_ID(@idxTable))
    BEGIN
        SET @sql = N'DROP INDEX ' + QUOTENAME(@idxName) + N' ON ' + QUOTENAME(@idxTable);
        EXEC sp_executesql @sql;
    END;

    FETCH NEXT FROM idx_cursor INTO @idxName, @idxTable;
END;
CLOSE idx_cursor;
DEALLOCATE idx_cursor;

DECLARE @renameObjects table (OldName sysname, NewName sysname);
INSERT INTO @renameObjects (OldName, NewName)
VALUES
    ('PK_TChat', 'PK_TChatM'),
    ('DF_TChat_Id', 'DF_TChatM_Id'),
    ('DF_TChat_JenisChat', 'DF_TChatM_JenisChat'),
    ('DF_TChat_Prioritas', 'DF_TChatM_Prioritas'),
    ('DF_TChat_JumlahPesanBelumDibaca', 'DF_TChatM_JumlahPesanBelumDibaca'),
    ('DF_TChat_AutoReplyAiAktif', 'DF_TChatM_AutoReplyAiAktif'),
    ('DF_TChat_AiSudahMenyapa', 'DF_TChatM_AiSudahMenyapa'),
    ('DF_TChat_ModeAutoReplyAi', 'DF_TChatM_ModeAutoReplyAi'),
    ('DF_TChat_JumlahNotifikasiBelumTerbalas', 'DF_TChatM_JumlahNotifikasiBelumTerbalas'),
    ('DF_TChat_TglBuat', 'DF_TChatM_TglBuat'),
    ('PK_TChatDPenugasan', 'PK_TChatPenugasan'),
    ('DF_TChatDPenugasan_Id', 'DF_TChatPenugasan_Id'),
    ('DF_TChatDPenugasan_TglPenugasan', 'DF_TChatPenugasan_TglPenugasan'),
    ('DF_TChatDPenugasan_TglBuat', 'DF_TChatPenugasan_TglBuat'),
    ('PK_TChatDCatatanInternal', 'PK_TChatCatatanInternal'),
    ('DF_TChatDCatatanInternal_Id', 'DF_TChatCatatanInternal_Id'),
    ('DF_TChatDCatatanInternal_TglBuat', 'DF_TChatCatatanInternal_TglBuat'),
    ('PK_TTicket', 'PK_TTicketM'),
    ('DF_TTicket_Id', 'DF_TTicketM_Id'),
    ('DF_TTicket_TglBuat', 'DF_TTicketM_TglBuat'),
    ('UQ_TTicket_NomorTicket', 'UQ_TTicketM_NomorTicket'),
    ('PK_TTicketDPenugasan', 'PK_TTicketPenugasan'),
    ('DF_TTicketDPenugasan_Id', 'DF_TTicketPenugasan_Id'),
    ('DF_TTicketDPenugasan_TglPenugasan', 'DF_TTicketPenugasan_TglPenugasan'),
    ('DF_TTicketDPenugasan_TglBuat', 'DF_TTicketPenugasan_TglBuat'),
    ('PK_TTicketDLampiran', 'PK_TTicketLampiran'),
    ('DF_TTicketDLampiran_Id', 'DF_TTicketLampiran_Id'),
    ('DF_TTicketDLampiran_TglBuat', 'DF_TTicketLampiran_TglBuat');

DECLARE @oldObject sysname;
DECLARE @newObject sysname;
DECLARE @qualifiedOldObject nvarchar(517);
DECLARE obj_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT OldName, NewName FROM @renameObjects;
OPEN obj_cursor;
FETCH NEXT FROM obj_cursor INTO @oldObject, @newObject;
WHILE @@FETCH_STATUS = 0
BEGIN
    SET @qualifiedOldObject = NULL;

    SELECT TOP 1 @qualifiedOldObject = QUOTENAME(SCHEMA_NAME(schema_id)) + N'.' + QUOTENAME(name)
    FROM sys.objects
    WHERE name = @oldObject;

    IF @qualifiedOldObject IS NOT NULL
        AND NOT EXISTS (SELECT 1 FROM sys.objects WHERE name = @newObject)
    BEGIN
        EXEC sp_rename @qualifiedOldObject, @newObject, 'OBJECT';
    END;

    FETCH NEXT FROM obj_cursor INTO @oldObject, @newObject;
END;
CLOSE obj_cursor;
DEALLOCATE obj_cursor;

IF COL_LENGTH('TAiPermintaan', 'IdTicket') IS NOT NULL AND COL_LENGTH('TAiPermintaan', 'IdTicketM') IS NULL
    EXEC sp_rename 'TAiPermintaan.IdTicket', 'IdTicketM', 'COLUMN';
IF COL_LENGTH('TAiPermintaan', 'IdChat') IS NOT NULL AND COL_LENGTH('TAiPermintaan', 'IdChatM') IS NULL
    EXEC sp_rename 'TAiPermintaan.IdChat', 'IdChatM', 'COLUMN';
IF COL_LENGTH('TTicketDPenugasan', 'IdTicket') IS NOT NULL AND COL_LENGTH('TTicketDPenugasan', 'IdTicketM') IS NULL
    EXEC sp_rename 'TTicketDPenugasan.IdTicket', 'IdTicketM', 'COLUMN';
IF COL_LENGTH('TTicketDLampiran', 'IdTicket') IS NOT NULL AND COL_LENGTH('TTicketDLampiran', 'IdTicketM') IS NULL
    EXEC sp_rename 'TTicketDLampiran.IdTicket', 'IdTicketM', 'COLUMN';
IF COL_LENGTH('TTicketD', 'IdTicket') IS NOT NULL AND COL_LENGTH('TTicketD', 'IdTicketM') IS NULL
    EXEC sp_rename 'TTicketD.IdTicket', 'IdTicketM', 'COLUMN';
IF COL_LENGTH('TTicket', 'IdChat') IS NOT NULL AND COL_LENGTH('TTicket', 'IdChatM') IS NULL
    EXEC sp_rename 'TTicket.IdChat', 'IdChatM', 'COLUMN';
IF COL_LENGTH('TChatDPenugasan', 'IdChat') IS NOT NULL AND COL_LENGTH('TChatDPenugasan', 'IdChatM') IS NULL
    EXEC sp_rename 'TChatDPenugasan.IdChat', 'IdChatM', 'COLUMN';
IF COL_LENGTH('TChatDCatatanInternal', 'IdChat') IS NOT NULL AND COL_LENGTH('TChatDCatatanInternal', 'IdChatM') IS NULL
    EXEC sp_rename 'TChatDCatatanInternal.IdChat', 'IdChatM', 'COLUMN';
IF COL_LENGTH('TChatD', 'IdChat') IS NOT NULL AND COL_LENGTH('TChatD', 'IdChatM') IS NULL
    EXEC sp_rename 'TChatD.IdChat', 'IdChatM', 'COLUMN';

IF OBJECT_ID(N'TTicketDPenugasan', 'U') IS NOT NULL AND OBJECT_ID(N'TTicketPenugasan', 'U') IS NULL
    EXEC sp_rename 'TTicketDPenugasan', 'TTicketPenugasan';
IF OBJECT_ID(N'TTicketDLampiran', 'U') IS NOT NULL AND OBJECT_ID(N'TTicketLampiran', 'U') IS NULL
    EXEC sp_rename 'TTicketDLampiran', 'TTicketLampiran';
IF OBJECT_ID(N'TTicket', 'U') IS NOT NULL AND OBJECT_ID(N'TTicketM', 'U') IS NULL
    EXEC sp_rename 'TTicket', 'TTicketM';
IF OBJECT_ID(N'TChatDPenugasan', 'U') IS NOT NULL AND OBJECT_ID(N'TChatPenugasan', 'U') IS NULL
    EXEC sp_rename 'TChatDPenugasan', 'TChatPenugasan';
IF OBJECT_ID(N'TChatDCatatanInternal', 'U') IS NOT NULL AND OBJECT_ID(N'TChatCatatanInternal', 'U') IS NULL
    EXEC sp_rename 'TChatDCatatanInternal', 'TChatCatatanInternal';
IF OBJECT_ID(N'TChat', 'U') IS NOT NULL AND OBJECT_ID(N'TChatM', 'U') IS NULL
    EXEC sp_rename 'TChat', 'TChatM';

IF OBJECT_ID(N'FK_TChatD_TChatM', 'F') IS NULL AND COL_LENGTH('TChatD', 'IdChatM') IS NOT NULL
    ALTER TABLE TChatD ADD CONSTRAINT FK_TChatD_TChatM FOREIGN KEY (IdChatM) REFERENCES TChatM(Id);
IF OBJECT_ID(N'FK_TChatPenugasan_TChatM', 'F') IS NULL AND OBJECT_ID(N'TChatPenugasan', 'U') IS NOT NULL
    ALTER TABLE TChatPenugasan ADD CONSTRAINT FK_TChatPenugasan_TChatM FOREIGN KEY (IdChatM) REFERENCES TChatM(Id);
IF OBJECT_ID(N'FK_TChatCatatanInternal_TChatM', 'F') IS NULL AND OBJECT_ID(N'TChatCatatanInternal', 'U') IS NOT NULL
    ALTER TABLE TChatCatatanInternal ADD CONSTRAINT FK_TChatCatatanInternal_TChatM FOREIGN KEY (IdChatM) REFERENCES TChatM(Id);
IF OBJECT_ID(N'FK_TTicketM_TChatM', 'F') IS NULL AND OBJECT_ID(N'TTicketM', 'U') IS NOT NULL
    ALTER TABLE TTicketM ADD CONSTRAINT FK_TTicketM_TChatM FOREIGN KEY (IdChatM) REFERENCES TChatM(Id);
IF OBJECT_ID(N'FK_TTicketM_TChatD', 'F') IS NULL AND OBJECT_ID(N'TTicketM', 'U') IS NOT NULL
    ALTER TABLE TTicketM ADD CONSTRAINT FK_TTicketM_TChatD FOREIGN KEY (DibuatDariPesanId) REFERENCES TChatD(Id);
IF OBJECT_ID(N'FK_TTicketD_TTicketM', 'F') IS NULL AND COL_LENGTH('TTicketD', 'IdTicketM') IS NOT NULL
    ALTER TABLE TTicketD ADD CONSTRAINT FK_TTicketD_TTicketM FOREIGN KEY (IdTicketM) REFERENCES TTicketM(Id);
IF OBJECT_ID(N'FK_TTicketPenugasan_TTicketM', 'F') IS NULL AND OBJECT_ID(N'TTicketPenugasan', 'U') IS NOT NULL
    ALTER TABLE TTicketPenugasan ADD CONSTRAINT FK_TTicketPenugasan_TTicketM FOREIGN KEY (IdTicketM) REFERENCES TTicketM(Id);
IF OBJECT_ID(N'FK_TTicketLampiran_TTicketM', 'F') IS NULL AND OBJECT_ID(N'TTicketLampiran', 'U') IS NOT NULL
    ALTER TABLE TTicketLampiran ADD CONSTRAINT FK_TTicketLampiran_TTicketM FOREIGN KEY (IdTicketM) REFERENCES TTicketM(Id);
IF OBJECT_ID(N'FK_TAiPermintaan_TChatM', 'F') IS NULL AND COL_LENGTH('TAiPermintaan', 'IdChatM') IS NOT NULL
    ALTER TABLE TAiPermintaan ADD CONSTRAINT FK_TAiPermintaan_TChatM FOREIGN KEY (IdChatM) REFERENCES TChatM(Id);
IF OBJECT_ID(N'FK_TAiPermintaan_TTicketM', 'F') IS NULL AND COL_LENGTH('TAiPermintaan', 'IdTicketM') IS NOT NULL
    ALTER TABLE TAiPermintaan ADD CONSTRAINT FK_TAiPermintaan_TTicketM FOREIGN KEY (IdTicketM) REFERENCES TTicketM(Id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_NomorWhatsapp' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_NomorWhatsapp ON TChatM (NomorWhatsapp);
IF COL_LENGTH('TChatM', 'IdWahaTerdeteksi') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_IdWahaTerdeteksi' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_IdWahaTerdeteksi ON TChatM (IdWahaTerdeteksi);
IF COL_LENGTH('TChatM', 'NomorWhatsappTerdeteksi') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_NomorWhatsappTerdeteksi' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_NomorWhatsappTerdeteksi ON TChatM (NomorWhatsappTerdeteksi);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_IdCustomer' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_IdCustomer ON TChatM (IdCustomer);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_IdInstansi' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_IdInstansi ON TChatM (IdInstansi);
IF COL_LENGTH('TChatM', 'IdGrupWhatsapp') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_IdGrupWhatsapp' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_IdGrupWhatsapp ON TChatM (IdGrupWhatsapp);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_IdStatusChat' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_IdStatusChat ON TChatM (IdStatusChat);
IF COL_LENGTH('TChatM', 'DitugaskanKepada') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_DitugaskanKepada' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_DitugaskanKepada ON TChatM (DitugaskanKepada);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatM_TglChatTerakhir' AND object_id = OBJECT_ID('TChatM'))
    CREATE INDEX IX_TChatM_TglChatTerakhir ON TChatM (TglChatTerakhir);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdChatM_TglPesan' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_IdChatM_TglPesan ON TChatD (IdChatM, TglPesan);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicketM_IdCustomer' AND object_id = OBJECT_ID('TTicketM'))
    CREATE INDEX IX_TTicketM_IdCustomer ON TTicketM (IdCustomer);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicketM_IdInstansi' AND object_id = OBJECT_ID('TTicketM'))
    CREATE INDEX IX_TTicketM_IdInstansi ON TTicketM (IdInstansi);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicketM_IdStatusTicket' AND object_id = OBJECT_ID('TTicketM'))
    CREATE INDEX IX_TTicketM_IdStatusTicket ON TTicketM (IdStatusTicket);
IF COL_LENGTH('TTicketM', 'DitugaskanKepada') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicketM_DitugaskanKepada' AND object_id = OBJECT_ID('TTicketM'))
    CREATE INDEX IX_TTicketM_DitugaskanKepada ON TTicketM (DitugaskanKepada);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicketM_TglTargetSelesai' AND object_id = OBJECT_ID('TTicketM'))
    CREATE INDEX IX_TTicketM_TglTargetSelesai ON TTicketM (TglTargetSelesai);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TTicketD_IdTicketM_TglAktivitas' AND object_id = OBJECT_ID('TTicketD'))
    CREATE INDEX IX_TTicketD_IdTicketM_TglAktivitas ON TTicketD (IdTicketM, TglAktivitas);
IF COL_LENGTH('TAiPermintaan', 'IdChatM') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TAiPermintaan_IdChatM' AND object_id = OBJECT_ID('TAiPermintaan'))
    CREATE INDEX IX_TAiPermintaan_IdChatM ON TAiPermintaan (IdChatM);
IF COL_LENGTH('TAiPermintaan', 'IdTicketM') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TAiPermintaan_IdTicketM' AND object_id = OBJECT_ID('TAiPermintaan'))
    CREATE INDEX IX_TAiPermintaan_IdTicketM ON TAiPermintaan (IdTicketM);

COMMIT TRANSACTION;
SQL);
    }
};
