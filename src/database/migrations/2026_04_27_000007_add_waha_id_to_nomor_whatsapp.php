<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MNomorWhatsapp', 'IdWaha') IS NULL
    ALTER TABLE MNomorWhatsapp ADD IdWaha varchar(200) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MNomorWhatsapp_IdWaha' AND object_id = OBJECT_ID('MNomorWhatsapp'))
    CREATE INDEX IX_MNomorWhatsapp_IdWaha ON MNomorWhatsapp (IdWaha);
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MNomorWhatsapp_IdWaha' AND object_id = OBJECT_ID('MNomorWhatsapp'))
    DROP INDEX IX_MNomorWhatsapp_IdWaha ON MNomorWhatsapp;

IF COL_LENGTH('MNomorWhatsapp', 'IdWaha') IS NOT NULL
    ALTER TABLE MNomorWhatsapp DROP COLUMN IdWaha;
SQL);
    }
};
