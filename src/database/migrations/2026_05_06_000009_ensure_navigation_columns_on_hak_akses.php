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
                if (! Schema::hasColumn('MHakAkses', 'IconString')) {
                    $table->string('IconString', 100)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MHakAkses')) {
            Schema::table('MHakAkses', function (Blueprint $table): void {
                if (Schema::hasColumn('MHakAkses', 'IconString')) {
                    $table->dropColumn('IconString');
                }
            });
        }
    }
};
