<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (! Schema::hasColumn('TChat', 'FotoProfilUrl')) {
                    $table->string('FotoProfilUrl', 500)->nullable();
                }
                if (! Schema::hasColumn('TChat', 'NamaProfilWhatsapp')) {
                    $table->string('NamaProfilWhatsapp', 150)->nullable();
                }
            });
        }

        if (Schema::hasTable('TChatD')) {
            Schema::table('TChatD', function (Blueprint $table): void {
                if (! Schema::hasColumn('TChatD', 'FotoProfilUrl')) {
                    $table->string('FotoProfilUrl', 500)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('TChatD')) {
            Schema::table('TChatD', function (Blueprint $table): void {
                if (Schema::hasColumn('TChatD', 'FotoProfilUrl')) {
                    $table->dropColumn('FotoProfilUrl');
                }
            });
        }

        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (Schema::hasColumn('TChat', 'NamaProfilWhatsapp')) {
                    $table->dropColumn('NamaProfilWhatsapp');
                }
                if (Schema::hasColumn('TChat', 'FotoProfilUrl')) {
                    $table->dropColumn('FotoProfilUrl');
                }
            });
        }
    }
};
