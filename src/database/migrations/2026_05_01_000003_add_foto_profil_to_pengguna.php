<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('MPengguna')) {
            Schema::table('MPengguna', function (Blueprint $table): void {
                if (! Schema::hasColumn('MPengguna', 'FotoProfil')) {
                    $table->string('FotoProfil', 255)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MPengguna')) {
            Schema::table('MPengguna', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengguna', 'FotoProfil')) {
                    $table->dropColumn('FotoProfil');
                }
            });
        }
    }
};
