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

    UPDATE MHakAkses
    SET IdHakAkses = @OperasionalId,
        NamaHakAkses = 'Histori Chat',
        NamaHakAksesId = 'Histori Chat',
        NamaHakAksesEn = 'Chat History',
        Modul = 'Operasional',
        ModulId = 'Operasional',
        ModulEn = 'Operational',
        Keterangan = 'Melihat daftar histori sesi chat dan membuka detail percakapan.',
        KeteranganId = 'Melihat daftar histori sesi chat dan membuka detail percakapan.',
        KeteranganEn = 'View chat session history list and open conversation details.',
        SortOrder = 11,
        IconString = 'heroicon-o-clock',
        NonAktif = 0,
        TglEdit = SYSDATETIME()
    WHERE KodeHakAkses = 'chat_history.view';
END
SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable('MHakAkses')) {
            return;
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MHakAkses', 'IdHakAkses') IS NOT NULL
BEGIN
    UPDATE MHakAkses
    SET IdHakAkses = NULL,
        NamaHakAkses = 'Lihat History Chat',
        NamaHakAksesId = 'Lihat History Chat',
        NamaHakAksesEn = 'View Chat History',
        Modul = 'History Chat',
        ModulId = 'History Chat',
        ModulEn = 'Chat History',
        Keterangan = 'Membuka detail riwayat sesi chat.',
        KeteranganId = 'Membuka detail riwayat sesi chat.',
        KeteranganEn = 'Open chat session history details.',
        SortOrder = NULL,
        IconString = NULL,
        TglEdit = SYSDATETIME()
    WHERE KodeHakAkses = 'chat_history.view';
END
SQL);
    }
};
