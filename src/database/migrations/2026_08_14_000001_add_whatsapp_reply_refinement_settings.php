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
                if (! Schema::hasColumn('MPengaturanAi', 'PerhalusJawabanWhatsappDefault')) {
                    $table->boolean('PerhalusJawabanWhatsappDefault')->default(false);
                }
            });
        }

        if (Schema::hasTable('MPengguna')) {
            Schema::table('MPengguna', function (Blueprint $table): void {
                if (! Schema::hasColumn('MPengguna', 'PerhalusJawabanWhatsapp')) {
                    $table->boolean('PerhalusJawabanWhatsapp')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MPengguna')) {
            Schema::table('MPengguna', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengguna', 'PerhalusJawabanWhatsapp')) {
                    $table->dropColumn('PerhalusJawabanWhatsapp');
                }
            });
        }

        if (Schema::hasTable('MPengaturanAi')) {
            Schema::table('MPengaturanAi', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengaturanAi', 'PerhalusJawabanWhatsappDefault')) {
                    $table->dropColumn('PerhalusJawabanWhatsappDefault');
                }
            });
        }
    }
};
