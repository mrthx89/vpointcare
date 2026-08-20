<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('MHakAkses')) {
            Schema::table('MHakAkses', function (Blueprint $table): void {
                if (! Schema::hasColumn('MHakAkses', 'NamaHakAksesId')) {
                    $table->string('NamaHakAksesId', 150)->nullable();
                }
                if (! Schema::hasColumn('MHakAkses', 'NamaHakAksesEn')) {
                    $table->string('NamaHakAksesEn', 150)->nullable();
                }
                if (! Schema::hasColumn('MHakAkses', 'ModulId')) {
                    $table->string('ModulId', 100)->nullable();
                }
                if (! Schema::hasColumn('MHakAkses', 'ModulEn')) {
                    $table->string('ModulEn', 100)->nullable();
                }
                if (! Schema::hasColumn('MHakAkses', 'KeteranganId')) {
                    $table->string('KeteranganId', 255)->nullable();
                }
                if (! Schema::hasColumn('MHakAkses', 'KeteranganEn')) {
                    $table->string('KeteranganEn', 255)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MHakAkses')) {
            Schema::table('MHakAkses', function (Blueprint $table): void {
                foreach (['KeteranganEn', 'KeteranganId', 'ModulEn', 'ModulId', 'NamaHakAksesEn', 'NamaHakAksesId'] as $col) {
                    if (Schema::hasColumn('MHakAkses', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
