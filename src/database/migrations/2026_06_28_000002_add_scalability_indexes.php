<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The scalability indexes migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdPesanWaha_Partial' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_IdPesanWaha_Partial ON TChatD (IdPesanWaha) WHERE IdPesanWaha IS NOT NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_Arah_Dikirim_Tgl' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_Arah_Dikirim_Tgl ON TChatD (ArahPesan, DikirimOlehCustomer, TglPesan DESC) INCLUDE (IdChat, IsiPesan);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdChat_Arah_Ai_Tgl' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_IdChat_Arah_Ai_Tgl ON TChatD (IdChat, ArahPesan, DihasilkanOlehAi, TglPesan DESC);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_TglPesan_Arah_Status' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_TglPesan_Arah_Status ON TChatD (TglPesan) INCLUDE (IdChat, ArahPesan, DihasilkanOlehAi, StatusKirim);
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_TglPesan_Arah_Status' AND object_id = OBJECT_ID('TChatD'))
    DROP INDEX IX_TChatD_TglPesan_Arah_Status ON TChatD;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdChat_Arah_Ai_Tgl' AND object_id = OBJECT_ID('TChatD'))
    DROP INDEX IX_TChatD_IdChat_Arah_Ai_Tgl ON TChatD;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_Arah_Dikirim_Tgl' AND object_id = OBJECT_ID('TChatD'))
    DROP INDEX IX_TChatD_Arah_Dikirim_Tgl ON TChatD;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdPesanWaha_Partial' AND object_id = OBJECT_ID('TChatD'))
    DROP INDEX IX_TChatD_IdPesanWaha_Partial ON TChatD;
SQL);
    }
};
