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
                if (! Schema::hasColumn('MPengaturanAi', 'ProviderAi')) {
                    $table->string('ProviderAi', 50)->default('OpenAI');
                }
                if (! Schema::hasColumn('MPengaturanAi', 'ModelAi')) {
                    $table->string('ModelAi', 100)->default('gpt-5');
                }
                if (! Schema::hasColumn('MPengaturanAi', 'BaseUrl')) {
                    $table->string('BaseUrl', 255)->default('https://api.openai.com/v1/responses');
                }
                if (! Schema::hasColumn('MPengaturanAi', 'PromptSistem')) {
                    $table->text('PromptSistem')->nullable();
                }
                if (! Schema::hasColumn('MPengaturanAi', 'TemplateDiluarJamKerja')) {
                    $table->text('TemplateDiluarJamKerja')->nullable();
                }
                if (! Schema::hasColumn('MPengaturanAi', 'TemplateHariLibur')) {
                    $table->text('TemplateHariLibur')->nullable();
                }
                if (! Schema::hasColumn('MPengaturanAi', 'TemplateJamKerjaSapaan')) {
                    $table->text('TemplateJamKerjaSapaan')->nullable();
                }
                if (! Schema::hasColumn('MPengaturanAi', 'TemplateFallback')) {
                    $table->text('TemplateFallback')->nullable();
                }
                if (! Schema::hasColumn('MPengaturanAi', 'AiAutoReplyAktif')) {
                    $table->boolean('AiAutoReplyAktif')->default(true);
                }
                if (! Schema::hasColumn('MPengaturanAi', 'ModeAutoReply')) {
                    $table->string('ModeAutoReply', 30)->default('Always');
                }
                if (! Schema::hasColumn('MPengaturanAi', 'JamMulaiKerja')) {
                    $table->string('JamMulaiKerja', 10)->default('08:00');
                }
                if (! Schema::hasColumn('MPengaturanAi', 'JamSelesaiKerja')) {
                    $table->string('JamSelesaiKerja', 10)->default('17:00');
                }
                if (! Schema::hasColumn('MPengaturanAi', 'KirimKeWaha')) {
                    $table->boolean('KirimKeWaha')->default(true);
                }
            });
        }

        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (! Schema::hasColumn('TChat', 'SesiGreetingAktifSampai')) {
                    $table->timestamp('SesiGreetingAktifSampai')->nullable();
                }
                if (! Schema::hasColumn('TChat', 'TerakhirDisapaAiPada')) {
                    $table->timestamp('TerakhirDisapaAiPada')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (Schema::hasColumn('TChat', 'TerakhirDisapaAiPada')) {
                    $table->dropColumn('TerakhirDisapaAiPada');
                }
                if (Schema::hasColumn('TChat', 'SesiGreetingAktifSampai')) {
                    $table->dropColumn('SesiGreetingAktifSampai');
                }
            });
        }
    }
};
