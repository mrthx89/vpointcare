<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'NineRouterApiKeyTerenkripsi') IS NULL
        ALTER TABLE MPengaturanAi ADD NineRouterApiKeyTerenkripsi nvarchar(max) NULL;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'NineRouterApiKeyTerenkripsi') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP COLUMN NineRouterApiKeyTerenkripsi;
END
SQL);
    }
};
