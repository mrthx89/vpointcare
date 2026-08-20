<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('MStatusTask')) {
            Schema::create('MStatusTask', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->string('KodeStatusTask', 50)->unique();
                $table->string('NamaStatusTask', 100);
                $table->integer('Urutan')->default(0);
                $table->boolean('StatusFinal')->default(false);
                $table->string('Warna', 30)->nullable();
                $table->boolean('NonAktif')->default(false);
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();
                $table->timestamp('TglEdit')->nullable();
                $table->uuid('DieditOleh')->nullable();
            });
        }

        if (! Schema::hasTable('TTask')) {
            Schema::create('TTask', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->string('NomorTask', 50)->unique();
                $table->uuid('IdTicket')->nullable()->index();
                $table->uuid('IdChat')->nullable()->index();
                $table->uuid('IdCustomer')->nullable()->index();
                $table->uuid('IdInstansi')->nullable();
                $table->uuid('IdTugasInduk')->nullable()->index();
                $table->uuid('IdKategoriTicket')->nullable();
                $table->uuid('IdPrioritasTicket')->nullable();
                $table->uuid('IdStatusTask')->index();
                $table->string('JudulTask', 255);
                $table->text('DeskripsiTask')->nullable();
                $table->uuid('DitugaskanKepada')->nullable()->index();
                $table->timestamp('TglDitugaskan')->nullable();
                $table->timestamp('TglTargetSelesai')->nullable()->index();
                $table->timestamp('TglSelesai')->nullable();
                $table->timestamp('TglDitutup')->nullable();
                $table->uuid('DitutupOleh')->nullable();
                $table->integer('EstimasiMenit')->nullable();
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();
                $table->timestamp('TglEdit')->nullable();
                $table->uuid('DieditOleh')->nullable();

                $table->foreign('IdTicket')->references('Id')->on('TTicket')->nullOnDelete();
                $table->foreign('IdChat')->references('Id')->on('TChat')->nullOnDelete();
                $table->foreign('IdCustomer')->references('Id')->on('MCustomer')->nullOnDelete();
                $table->foreign('IdInstansi')->references('Id')->on('MInstansi')->nullOnDelete();
                $table->foreign('IdTugasInduk')->references('Id')->on('TTask')->nullOnDelete();
                $table->foreign('IdKategoriTicket')->references('Id')->on('MKategoriTicket')->nullOnDelete();
                $table->foreign('IdPrioritasTicket')->references('Id')->on('MPrioritasTicket')->nullOnDelete();
                $table->foreign('IdStatusTask')->references('Id')->on('MStatusTask');
                $table->foreign('DitugaskanKepada')->references('Id')->on('MPengguna')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('TTaskDPenugasan')) {
            Schema::create('TTaskDPenugasan', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdTask')->index();
                $table->uuid('DitugaskanKepada')->nullable();
                $table->uuid('DitugaskanOleh')->nullable();
                $table->text('Catatan')->nullable();
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();

                $table->foreign('IdTask')->references('Id')->on('TTask')->cascadeOnDelete();
                $table->foreign('DitugaskanKepada')->references('Id')->on('MPengguna')->nullOnDelete();
                $table->foreign('DitugaskanOleh')->references('Id')->on('MPengguna')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('TTaskDChecklist')) {
            Schema::create('TTaskDChecklist', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdTask')->index();
                $table->string('JudulItem', 255);
                $table->boolean('SudahSelesai')->default(false);
                $table->timestamp('TglSelesai')->nullable();
                $table->uuid('DiselesaikanOleh')->nullable();
                $table->integer('Urutan')->default(0);
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();
                $table->timestamp('TglEdit')->nullable();
                $table->uuid('DieditOleh')->nullable();

                $table->foreign('IdTask')->references('Id')->on('TTask')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('TTaskDKomentar')) {
            Schema::create('TTaskDKomentar', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdTask')->index();
                $table->uuid('IdPengguna')->nullable();
                $table->text('Komentar');
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();

                $table->foreign('IdTask')->references('Id')->on('TTask')->cascadeOnDelete();
                $table->foreign('IdPengguna')->references('Id')->on('MPengguna')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('TTaskDLampiran')) {
            Schema::create('TTaskDLampiran', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdTask')->index();
                $table->string('NamaFile', 255);
                $table->string('PathFile', 500);
                $table->string('MimeType', 100)->nullable();
                $table->bigInteger('UkuranBytes')->nullable();
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();

                $table->foreign('IdTask')->references('Id')->on('TTask')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('TTaskDLampiran');
        Schema::dropIfExists('TTaskDKomentar');
        Schema::dropIfExists('TTaskDChecklist');
        Schema::dropIfExists('TTaskDPenugasan');
        Schema::dropIfExists('TTask');
        Schema::dropIfExists('MStatusTask');
    }
};
