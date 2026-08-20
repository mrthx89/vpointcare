<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('MNomorWhatsapp')) {
            Schema::table('MNomorWhatsapp', function (Blueprint $table): void {
                if (! Schema::hasColumn('MNomorWhatsapp', 'IdWaha')) {
                    $table->string('IdWaha', 200)->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MNomorWhatsapp')) {
            Schema::table('MNomorWhatsapp', function (Blueprint $table): void {
                if (Schema::hasColumn('MNomorWhatsapp', 'IdWaha')) {
                    $table->dropColumn('IdWaha');
                }
            });
        }
    }
};
