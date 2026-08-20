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
                if (! Schema::hasColumn('MPengaturanAi', 'NotifikasiChatBelumTerbalasAktif')) {
                    $table->boolean('NotifikasiChatBelumTerbalasAktif')->default(true);
                }
                if (! Schema::hasColumn('MPengaturanAi', 'MenitTungguNotifikasi')) {
                    $table->integer('MenitTungguNotifikasi')->default(10);
                }
                if (! Schema::hasColumn('MPengaturanAi', 'TemplateNotifikasiChatBelumTerbalas')) {
                    $table->text('TemplateNotifikasiChatBelumTerbalas')->nullable();
                }
            });
        }

        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (! Schema::hasColumn('TChat', 'NotifikasiBelumTerbalasTerkirimPada')) {
                    $table->timestamp('NotifikasiBelumTerbalasTerkirimPada')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (Schema::hasColumn('TChat', 'NotifikasiBelumTerbalasTerkirimPada')) {
                    $table->dropColumn('NotifikasiBelumTerbalasTerkirimPada');
                }
            });
        }

        if (Schema::hasTable('MPengaturanAi')) {
            Schema::table('MPengaturanAi', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengaturanAi', 'TemplateNotifikasiChatBelumTerbalas')) {
                    $table->dropColumn('TemplateNotifikasiChatBelumTerbalas');
                }
                if (Schema::hasColumn('MPengaturanAi', 'MenitTungguNotifikasi')) {
                    $table->dropColumn('MenitTungguNotifikasi');
                }
                if (Schema::hasColumn('MPengaturanAi', 'NotifikasiChatBelumTerbalasAktif')) {
                    $table->dropColumn('NotifikasiChatBelumTerbalasAktif');
                }
            });
        }
    }
};
