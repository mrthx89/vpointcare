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
    DECLARE @ChatbotId uniqueidentifier;

    SELECT TOP 1 @OperasionalId = parent.Id
    FROM MHakAkses child
    INNER JOIN MHakAkses parent ON parent.Id = child.IdHakAkses
    WHERE child.KodeHakAkses = 'inbox.view';

    IF @OperasionalId IS NULL
    BEGIN
        SELECT TOP 1 @OperasionalId = Id
        FROM MHakAkses
        WHERE KodeHakAkses IS NULL
            AND IdHakAkses IS NULL
            AND (NamaHakAksesId = 'Operasional' OR NamaHakAkses = 'Operasional' OR NamaHakAksesEn = 'Operational')
        ORDER BY SortOrder;
    END

    SELECT @ChatbotId = Id
    FROM MHakAkses
    WHERE KodeHakAkses = 'chatbot.access';

    IF @ChatbotId IS NULL
    BEGIN
        SET @ChatbotId = NEWID();

        INSERT INTO MHakAkses (
            Id, IdHakAkses, KodeHakAkses, NamaHakAkses, NamaHakAksesId, NamaHakAksesEn,
            Modul, ModulId, ModulEn, Keterangan, KeteranganId, KeteranganEn,
            SortOrder, IconString, NonAktif, TglBuat, TglEdit
        ) VALUES (
            @ChatbotId, @OperasionalId, 'chatbot.access', 'VPoint Assistant', 'VPoint Assistant', 'VPoint Assistant',
            'Operasional', 'Operasional', 'Operational',
            'Mengakses chatbot internal untuk bantuan operasional VPoint Care.',
            'Mengakses chatbot internal untuk bantuan operasional VPoint Care.',
            'Access the internal chatbot for VPoint Care operational assistance.',
            15, 'heroicon-o-chat-bubble-bottom-center-text', 0, SYSDATETIME(), SYSDATETIME()
        );
    END
    ELSE
    BEGIN
        UPDATE MHakAkses
        SET IdHakAkses = @OperasionalId,
            NamaHakAkses = 'VPoint Assistant',
            NamaHakAksesId = 'VPoint Assistant',
            NamaHakAksesEn = 'VPoint Assistant',
            Modul = 'Operasional',
            ModulId = 'Operasional',
            ModulEn = 'Operational',
            Keterangan = 'Mengakses chatbot internal untuk bantuan operasional VPoint Care.',
            KeteranganId = 'Mengakses chatbot internal untuk bantuan operasional VPoint Care.',
            KeteranganEn = 'Access the internal chatbot for VPoint Care operational assistance.',
            SortOrder = 15,
            IconString = 'heroicon-o-chat-bubble-bottom-center-text',
            NonAktif = 0,
            TglEdit = SYSDATETIME()
        WHERE Id = @ChatbotId;
    END

    IF OBJECT_ID('MPeranHakAkses') IS NOT NULL
    BEGIN
        INSERT INTO MPeranHakAkses (IdPeran, IdHakAkses, NonAktif, TglBuat, TglEdit)
        SELECT p.Id, @ChatbotId, 0, SYSDATETIME(), SYSDATETIME()
        FROM MPeran p
        WHERE p.KodePeran IN ('ADMIN', 'SUPERVISOR_CS', 'CS', 'DEVELOPER')
            AND NOT EXISTS (
                SELECT 1
                FROM MPeranHakAkses pha
                WHERE pha.IdPeran = p.Id
                    AND pha.IdHakAkses = @ChatbotId
            );

        UPDATE pha
        SET NonAktif = 0,
            TglEdit = SYSDATETIME()
        FROM MPeranHakAkses pha
        INNER JOIN MPeran p ON p.Id = pha.IdPeran
        WHERE pha.IdHakAkses = @ChatbotId
            AND p.KodePeran IN ('ADMIN', 'SUPERVISOR_CS', 'CS', 'DEVELOPER');
    END
END
SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable('MHakAkses')) {
            return;
        }

        DB::unprepared(<<<'SQL'
DECLARE @ChatbotId uniqueidentifier;

SELECT @ChatbotId = Id
FROM MHakAkses
WHERE KodeHakAkses = 'chatbot.access';

IF @ChatbotId IS NOT NULL
BEGIN
    IF OBJECT_ID('MPeranHakAkses') IS NOT NULL
    BEGIN
        DELETE FROM MPeranHakAkses
        WHERE IdHakAkses = @ChatbotId;
    END

    DELETE FROM MHakAkses
    WHERE Id = @ChatbotId;
END
SQL);
    }
};