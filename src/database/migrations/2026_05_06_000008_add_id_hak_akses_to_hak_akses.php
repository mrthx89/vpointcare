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
                if (Schema::hasColumn('MHakAkses', 'GroupSortOrder')) {
                    $table->dropColumn('GroupSortOrder');
                }
                if (! Schema::hasColumn('MHakAkses', 'IdHakAkses')) {
                    $table->uuid('IdHakAkses')->nullable()->index();
                    $table->foreign('IdHakAkses')->references('Id')->on('MHakAkses');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MHakAkses')) {
            Schema::table('MHakAkses', function (Blueprint $table): void {
                if (Schema::hasColumn('MHakAkses', 'IdHakAkses')) {
                    $table->dropForeign(['IdHakAkses']);
                    $table->dropColumn('IdHakAkses');
                }
            });
        }
    }
};
