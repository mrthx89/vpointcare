<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('MGrupWhatsapp')) {
            Schema::create('MGrupWhatsapp', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdInstansi');
                $table->string('KodeGrup', 50)->unique();
                $table->string('NamaGrup', 200);
                $table->string('IdGrupWaha', 200)->nullable()->index();
                $table->string('NomorGrupWhatsapp', 100)->nullable();
                $table->string('Deskripsi', 500)->nullable();
                $table->string('SumberData', 50)->nullable();
                $table->string('IdExternal', 100)->nullable();
                $table->boolean('NonAktif')->default(false);
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();
                $table->timestamp('TglEdit')->nullable();
                $table->uuid('DieditOleh')->nullable();

                $table->foreign('IdInstansi')->references('Id')->on('MInstansi');
                $table->index('IdInstansi');
            });
        }

        if (! Schema::hasTable('MAnggotaGrupWhatsapp')) {
            Schema::create('MAnggotaGrupWhatsapp', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdGrupWhatsapp');
                $table->uuid('IdNomorWhatsapp');
                $table->uuid('IdCustomer')->nullable();
                $table->string('PeranAnggota', 100)->nullable();
                $table->boolean('NonAktif')->default(false);
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();
                $table->timestamp('TglEdit')->nullable();
                $table->uuid('DieditOleh')->nullable();

                $table->unique(['IdGrupWhatsapp', 'IdNomorWhatsapp']);
                $table->foreign('IdGrupWhatsapp')->references('Id')->on('MGrupWhatsapp');
                $table->foreign('IdNomorWhatsapp')->references('Id')->on('MNomorWhatsapp');
                $table->foreign('IdCustomer')->references('Id')->on('MCustomer');
                $table->index('IdGrupWhatsapp');
            });
        }

        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (! Schema::hasColumn('TChat', 'IdGrupWhatsapp')) {
                    $table->uuid('IdGrupWhatsapp')->nullable()->index();
                    $table->foreign('IdGrupWhatsapp')->references('Id')->on('MGrupWhatsapp');
                }
                if (! Schema::hasColumn('TChat', 'JenisChat')) {
                    $table->string('JenisChat', 30)->default('Pribadi');
                }
                if (! Schema::hasColumn('TChat', 'NamaGrupWhatsapp')) {
                    $table->string('NamaGrupWhatsapp', 200)->nullable();
                }
            });
        }

        if (Schema::hasTable('TChatD')) {
            Schema::table('TChatD', function (Blueprint $table): void {
                if (! Schema::hasColumn('TChatD', 'PengirimNomorWhatsapp')) {
                    $table->string('PengirimNomorWhatsapp', 30)->nullable();
                }
                if (! Schema::hasColumn('TChatD', 'PengirimNamaKontak')) {
                    $table->string('PengirimNamaKontak', 150)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('TChatD')) {
            Schema::table('TChatD', function (Blueprint $table): void {
                if (Schema::hasColumn('TChatD', 'PengirimNamaKontak')) {
                    $table->dropColumn('PengirimNamaKontak');
                }
                if (Schema::hasColumn('TChatD', 'PengirimNomorWhatsapp')) {
                    $table->dropColumn('PengirimNomorWhatsapp');
                }
            });
        }

        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (Schema::hasColumn('TChat', 'IdGrupWhatsapp')) {
                    $table->dropForeign(['IdGrupWhatsapp']);
                    $table->dropColumn('IdGrupWhatsapp');
                }
                if (Schema::hasColumn('TChat', 'JenisChat')) {
                    $table->dropColumn('JenisChat');
                }
                if (Schema::hasColumn('TChat', 'NamaGrupWhatsapp')) {
                    $table->dropColumn('NamaGrupWhatsapp');
                }
            });
        }

        Schema::dropIfExists('MAnggotaGrupWhatsapp');
        Schema::dropIfExists('MGrupWhatsapp');
    }
};
