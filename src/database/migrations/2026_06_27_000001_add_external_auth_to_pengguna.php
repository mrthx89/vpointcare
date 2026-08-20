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
                if (! Schema::hasColumn('MPengguna', 'StatusRegistrasi')) {
                    $table->string('StatusRegistrasi', 30)->default('Active');
                }
                if (! Schema::hasColumn('MPengguna', 'RegistrasiExternalProvider')) {
                    $table->string('RegistrasiExternalProvider', 50)->nullable();
                }
                if (! Schema::hasColumn('MPengguna', 'RegistrasiExternalPada')) {
                    $table->timestamp('RegistrasiExternalPada')->nullable();
                }
            });
        }

        if (! Schema::hasTable('MPenggunaExternalIdentity')) {
            Schema::create('MPenggunaExternalIdentity', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdPengguna');
                $table->string('Provider', 50);
                $table->string('ExternalId', 191);
                $table->string('Email', 150)->nullable();
                $table->string('AvatarUrl', 500)->nullable();
                $table->timestamp('LastLoginAt')->nullable();
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();
                $table->timestamp('TglEdit')->nullable();
                $table->uuid('DieditOleh')->nullable();

                $table->unique(['Provider', 'ExternalId']);
                $table->foreign('IdPengguna')->references('Id')->on('MPengguna')->cascadeOnDelete();
                $table->index(['Provider', 'Email']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('MPenggunaExternalIdentity');

        if (Schema::hasTable('MPengguna')) {
            Schema::table('MPengguna', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengguna', 'RegistrasiExternalPada')) {
                    $table->dropColumn('RegistrasiExternalPada');
                }
                if (Schema::hasColumn('MPengguna', 'RegistrasiExternalProvider')) {
                    $table->dropColumn('RegistrasiExternalProvider');
                }
                if (Schema::hasColumn('MPengguna', 'StatusRegistrasi')) {
                    $table->dropColumn('StatusRegistrasi');
                }
            });
        }
    }
};
