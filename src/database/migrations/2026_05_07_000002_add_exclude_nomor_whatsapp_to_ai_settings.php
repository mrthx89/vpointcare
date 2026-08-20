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
                if (! Schema::hasColumn('MPengaturanAi', 'ExcludeNomorWhatsapp')) {
                    $table->text('ExcludeNomorWhatsapp')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MPengaturanAi')) {
            Schema::table('MPengaturanAi', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengaturanAi', 'ExcludeNomorWhatsapp')) {
                    $table->dropColumn('ExcludeNomorWhatsapp');
                }
            });
        }
    }
};
