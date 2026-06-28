<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('MHakAkses')) {
            return;
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MHakAkses', 'IdHakAkses') IS NOT NULL
BEGIN
    DECLARE @OperasionalId uniqueidentifier;
    DECLARE @InboxId uniqueidentifier;

    SELECT TOP 1 @OperasionalId = Id
    FROM MHakAkses
    WHERE KodeHakAkses IS NULL
        AND IdHakAkses IS NULL
        AND (NamaHakAksesId = 'Operasional' OR NamaHakAkses = 'Operasional' OR NamaHakAksesEn = 'Operational')
    ORDER BY SortOrder;

    IF @OperasionalId IS NULL
    BEGIN
        SET @OperasionalId = NEWID();

        INSERT INTO MHakAkses (
            Id, IdHakAkses, KodeHakAkses, NamaHakAkses, NamaHakAksesId, NamaHakAksesEn,
            Modul, ModulId, ModulEn, Keterangan, KeteranganId, KeteranganEn,
            SortOrder, IconString, NonAktif, TglBuat, TglEdit
        ) VALUES (
            @OperasionalId, NULL, NULL, 'Operasional', 'Operasional', 'Operational',
            'Operasional', 'Operasional', 'Operational',
            'Group menu untuk operasional customer service.',
            'Group menu untuk operasional customer service.',
            'Menu group for customer service operations.',
            10, 'heroicon-o-chat-bubble-left-right', 0, SYSDATETIME(), SYSDATETIME()
        );
    END
    ELSE
    BEGIN
        UPDATE MHakAkses
        SET NonAktif = 0,
            SortOrder = COALESCE(SortOrder, 10),
            IconString = COALESCE(IconString, 'heroicon-o-chat-bubble-left-right'),
            TglEdit = SYSDATETIME()
        WHERE Id = @OperasionalId;
    END

    SELECT @InboxId = Id
    FROM MHakAkses
    WHERE KodeHakAkses = 'inbox.view';

    IF @InboxId IS NULL
    BEGIN
        SET @InboxId = NEWID();

        INSERT INTO MHakAkses (
            Id, IdHakAkses, KodeHakAkses, NamaHakAkses, NamaHakAksesId, NamaHakAksesEn,
            Modul, ModulId, ModulEn, Keterangan, KeteranganId, KeteranganEn,
            SortOrder, IconString, NonAktif, TglBuat, TglEdit
        ) VALUES (
            @InboxId, @OperasionalId, 'inbox.view', 'Inbox WhatsApp', 'Inbox WhatsApp', 'WhatsApp Inbox',
            'Operasional', 'Operasional', 'Operational',
            'Melihat daftar dan detail percakapan WhatsApp.',
            'Melihat daftar dan detail percakapan WhatsApp.',
            'View WhatsApp conversation list and details.',
            10, 'heroicon-o-chat-bubble-left-right', 0, SYSDATETIME(), SYSDATETIME()
        );
    END
    ELSE
    BEGIN
        UPDATE MHakAkses
        SET IdHakAkses = @OperasionalId,
            NamaHakAkses = 'Inbox WhatsApp',
            NamaHakAksesId = 'Inbox WhatsApp',
            NamaHakAksesEn = 'WhatsApp Inbox',
            Modul = 'Operasional',
            ModulId = 'Operasional',
            ModulEn = 'Operational',
            Keterangan = 'Melihat daftar dan detail percakapan WhatsApp.',
            KeteranganId = 'Melihat daftar dan detail percakapan WhatsApp.',
            KeteranganEn = 'View WhatsApp conversation list and details.',
            SortOrder = 10,
            IconString = 'heroicon-o-chat-bubble-left-right',
            NonAktif = 0,
            TglEdit = SYSDATETIME()
        WHERE Id = @InboxId;
    END

    IF OBJECT_ID('MPeranHakAkses') IS NOT NULL
    BEGIN
        INSERT INTO MPeranHakAkses (IdPeran, IdHakAkses, NonAktif, TglBuat, TglEdit)
        SELECT p.Id, @InboxId, 0, SYSDATETIME(), SYSDATETIME()
        FROM MPeran p
        WHERE p.KodePeran IN ('ADMIN', 'SUPERVISOR_CS', 'CS')
            AND NOT EXISTS (
                SELECT 1
                FROM MPeranHakAkses pha
                WHERE pha.IdPeran = p.Id
                    AND pha.IdHakAkses = @InboxId
            );

        UPDATE pha
        SET NonAktif = 0,
            TglEdit = SYSDATETIME()
        FROM MPeranHakAkses pha
        INNER JOIN MPeran p ON p.Id = pha.IdPeran
        WHERE pha.IdHakAkses = @InboxId
            AND p.KodePeran IN ('ADMIN', 'SUPERVISOR_CS', 'CS');
    END
END
SQL);
    }

    public function down(): void
    {
        // Tidak menonaktifkan inbox saat rollback agar akses chat customer tidak hilang.
    }
};