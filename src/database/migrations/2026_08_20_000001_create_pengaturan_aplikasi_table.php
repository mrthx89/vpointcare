<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('MPengaturanAplikasi')) {
            return;
        }

        Schema::create('MPengaturanAplikasi', function (Blueprint $table) {
            $table->uuid('Id')->primary();
            $table->string('KodePengaturan', 50)->default('DEFAULT')->unique();
            $table->string('NamaAplikasi', 100)->nullable();
            $table->string('Tagline', 255)->nullable();
            $table->string('NamaPerusahaan', 200)->nullable();
            $table->string('LogoUtamaPath', 500)->nullable();
            $table->string('LogoSekunderPath', 500)->nullable();
            $table->string('FaviconPath', 500)->nullable();
            $table->text('TeksFooter')->nullable();
            $table->string('BahasaDefault', 10)->default('id');
            $table->string('ZonaWaktu', 100)->default('Asia/Jakarta');
            $table->string('FormatTanggal', 50)->default('d/m/Y');
            $table->string('EmailSupport', 150)->nullable();
            $table->string('NomorTeleponSupport', 50)->nullable();
            $table->string('AlamatKantor', 500)->nullable();
            $table->string('MailMailer', 50)->default('smtp');
            $table->string('MailHost', 255)->nullable();
            $table->integer('MailPort')->default(587);
            $table->string('MailUsername', 255)->nullable();
            $table->text('MailPasswordTerenkripsi')->nullable();
            $table->string('MailEncryption', 20)->default('tls');
            $table->string('MailFromAddress', 255)->nullable();
            $table->string('MailFromName', 255)->nullable();
            $table->boolean('SetupSelesai')->default(false);
            $table->text('LangkahOnboardingJson')->nullable();
            $table->boolean('NonAktif')->default(false);
            $table->timestamp('TglBuat')->useCurrent();
            $table->uuid('DibuatOleh')->nullable();
            $table->timestamp('TglEdit')->nullable();
            $table->uuid('DieditOleh')->nullable();
        });

        // Insert initial default configuration row
        DB::table('MPengaturanAplikasi')->insert([
            'Id' => (string) Str::uuid(),
            'KodePengaturan' => 'DEFAULT',
            'NamaAplikasi' => config('app.name', 'CareDesk'),
            'Tagline' => 'Integrated WhatsApp & AI Helpdesk System',
            'NamaPerusahaan' => 'CareDesk SaaS',
            'TeksFooter' => 'Care Desk System. All rights reserved.',
            'BahasaDefault' => 'id',
            'ZonaWaktu' => 'Asia/Jakarta',
            'FormatTanggal' => 'd/m/Y',
            'SetupSelesai' => false,
            'NonAktif' => false,
            'TglBuat' => now(),
            'TglEdit' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('MPengaturanAplikasi');
    }
};
