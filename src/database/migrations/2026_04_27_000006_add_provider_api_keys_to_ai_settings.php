<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('MPengaturanAi')) {
            Schema::table('MPengaturanAi', function (Blueprint $table): void {
                if (! Schema::hasColumn('MPengaturanAi', 'OpenAiApiKeyTerenkripsi')) {
                    $table->text('OpenAiApiKeyTerenkripsi')->nullable();
                }
                if (! Schema::hasColumn('MPengaturanAi', 'DeepSeekApiKeyTerenkripsi')) {
                    $table->text('DeepSeekApiKeyTerenkripsi')->nullable();
                }
                if (! Schema::hasColumn('MPengaturanAi', 'OpenRouterApiKeyTerenkripsi')) {
                    $table->text('OpenRouterApiKeyTerenkripsi')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MPengaturanAi')) {
            Schema::table('MPengaturanAi', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengaturanAi', 'OpenRouterApiKeyTerenkripsi')) {
                    $table->dropColumn('OpenRouterApiKeyTerenkripsi');
                }
                if (Schema::hasColumn('MPengaturanAi', 'DeepSeekApiKeyTerenkripsi')) {
                    $table->dropColumn('DeepSeekApiKeyTerenkripsi');
                }
                if (Schema::hasColumn('MPengaturanAi', 'OpenAiApiKeyTerenkripsi')) {
                    $table->dropColumn('OpenAiApiKeyTerenkripsi');
                }
            });
        }
    }
};
