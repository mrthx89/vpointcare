<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
IF COL_LENGTH('MPengaturanAi', 'OpenAiApiKeyTerenkripsi') IS NULL
    ALTER TABLE MPengaturanAi ADD OpenAiApiKeyTerenkripsi nvarchar(max) NULL;

IF COL_LENGTH('MPengaturanAi', 'DeepSeekApiKeyTerenkripsi') IS NULL
    ALTER TABLE MPengaturanAi ADD DeepSeekApiKeyTerenkripsi nvarchar(max) NULL;

IF COL_LENGTH('MPengaturanAi', 'OpenRouterApiKeyTerenkripsi') IS NULL
    ALTER TABLE MPengaturanAi ADD OpenRouterApiKeyTerenkripsi nvarchar(max) NULL;
");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("
IF COL_LENGTH('MPengaturanAi', 'OpenRouterApiKeyTerenkripsi') IS NOT NULL
    ALTER TABLE MPengaturanAi DROP COLUMN OpenRouterApiKeyTerenkripsi;

IF COL_LENGTH('MPengaturanAi', 'DeepSeekApiKeyTerenkripsi') IS NOT NULL
    ALTER TABLE MPengaturanAi DROP COLUMN DeepSeekApiKeyTerenkripsi;

IF COL_LENGTH('MPengaturanAi', 'OpenAiApiKeyTerenkripsi') IS NOT NULL
    ALTER TABLE MPengaturanAi DROP COLUMN OpenAiApiKeyTerenkripsi;
");
    }
};
