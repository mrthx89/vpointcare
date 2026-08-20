<?php

namespace App\Console\Commands;

use App\Models\Master\Pengguna;
use App\Support\AppSettings;
use App\Support\NavigationHelper;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CareSetupCommand extends Command
{
    protected $signature = 'care:setup
                            {--admin-name= : Nama lengkap administrator}
                            {--admin-email= : Email administrator}
                            {--admin-password= : Password administrator}
                            {--app-name= : Nama aplikasi / brand}
                            {--company-name= : Nama instansi atau perusahaan}
                            {--timezone=Asia/Jakarta : Zona waktu operasional}
                            {--mail-host= : Host SMTP email}
                            {--mail-port=587 : Port SMTP email}
                            {--mail-username= : Username SMTP email}
                            {--mail-password= : Password SMTP email}
                            {--mail-encryption=tls : Enkripsi SMTP (tls/ssl/none)}
                            {--mail-from-address= : Alamat email pengirim}
                            {--mail-from-name= : Nama pengirim email}
                            {--force : Jalankan tanpa konfirmasi ulang}';

    protected $description = 'Setup awal konfigurasi sistem, email sender SMTP, dan pembuatan akun administrator (Zero-Hardcode)';

    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('   CareDesk / VPoint Care - Setup Awal Sistem       ');
        $this->info('====================================================');

        if (! Schema::hasTable('MPeran') || ! Schema::hasTable('MPengguna')) {
            $this->warn('Tabel database belum ditemukan. Menjalankan migrasi database...');
            $this->call('migrate', ['--force' => true]);
        }

        // Run master seeder for roles, permissions, master data
        $this->info('Menyiapkan master role, hak akses, dan kamus sistem...');
        $this->call(DatabaseSeeder::class);

        $adminName = $this->option('admin-name');
        $adminEmail = $this->option('admin-email');
        $adminPassword = $this->option('admin-password');
        $appName = $this->option('app-name');
        $companyName = $this->option('company-name');
        $timezone = $this->option('timezone') ?: 'Asia/Jakarta';

        $mailHost = $this->option('mail-host');
        $mailPort = (int) ($this->option('mail-port') ?: 587);
        $mailUsername = $this->option('mail-username');
        $mailPassword = $this->option('mail-password');
        $mailEncryption = $this->option('mail-encryption') ?: 'tls';
        $mailFromAddress = $this->option('mail-from-address');
        $mailFromName = $this->option('mail-from-name');

        $isInteractive = ! $adminEmail || ! $adminPassword;

        if ($isInteractive) {
            $this->newLine();
            $this->info('--- 1. Akun Super Administrator ---');

            $adminName = $this->ask('Nama Lengkap Administrator', $adminName ?: 'Administrator');

            while (empty($adminEmail) || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $adminEmail = $this->ask('Email Administrator (untuk login)');
                if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->error('Format email tidak valid. Silakan coba lagi.');
                }
            }

            while (empty($adminPassword) || strlen($adminPassword) < 8) {
                $adminPassword = $this->secret('Password Administrator (minimal 8 karakter)');
                if (strlen($adminPassword) < 8) {
                    $this->error('Password minimal 8 karakter.');
                    continue;
                }
                $confirmPassword = $this->secret('Konfirmasi Password Administrator');
                if ($adminPassword !== $confirmPassword) {
                    $this->error('Konfirmasi password tidak cocok. Silakan ulangi.');
                    $adminPassword = null;
                }
            }

            $this->newLine();
            $this->info('--- 2. Identitas Brand & Perusahaan ---');
            $appName = $this->ask('Nama Brand / Aplikasi', $appName ?: config('app.name', 'CareDesk'));
            $companyName = $this->ask('Nama Perusahaan / Organisasi', $companyName ?: 'CareDesk SaaS');
            $timezone = $this->choice('Pilih Zona Waktu', [
                'Asia/Jakarta' => 'Asia/Jakarta (WIB)',
                'Asia/Makassar' => 'Asia/Makassar (WITA)',
                'Asia/Jayapura' => 'Asia/Jayapura (WIT)',
                'UTC' => 'UTC',
            ], $timezone);

            $this->newLine();
            $this->info('--- 3. Konfigurasi Email Pengirim (SMTP) ---');
            $setupMail = $this->confirm('Apakah Anda ingin mengonfigurasi Email Pengirim / SMTP sekarang?', false);

            if ($setupMail) {
                $mailHost = $this->ask('SMTP Host', 'smtp.gmail.com');
                $mailPort = (int) $this->ask('SMTP Port', '587');
                $mailUsername = $this->ask('SMTP Username / Email');
                $mailPassword = $this->secret('SMTP Password / App Password');
                $mailEncryption = $this->choice('SMTP Encryption', ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'], 'tls');
                $mailFromAddress = $this->ask('Email Pengirim (From Address)', $mailUsername ?: 'noreply@example.com');
                $mailFromName = $this->ask('Nama Pengirim (From Name)', $appName);
            }
        } else {
            $adminName = $adminName ?: 'Administrator';
            $appName = $appName ?: config('app.name', 'CareDesk');
            $companyName = $companyName ?: 'CareDesk SaaS';
        }

        // Get or verify ADMIN role
        $adminRole = DB::table('MPeran')->where('KodePeran', 'ADMIN')->first();

        if (! $adminRole) {
            $this->error('Peran ADMIN tidak ditemukan di database.');
            return self::FAILURE;
        }

        // Create or update Administrator in MPengguna
        $adminUser = Pengguna::updateOrCreate(
            ['Email' => $adminEmail],
            [
                'IdPeran' => $adminRole->Id,
                'NamaPengguna' => $adminName,
                'Password' => Hash::make($adminPassword),
                'NonAktif' => false,
                'EmailTerverifikasiPada' => now(),
                'TglEdit' => now(),
            ]
        );

        // Update MPengaturanAplikasi
        if (Schema::hasTable('MPengaturanAplikasi')) {
            $settingRow = DB::table('MPengaturanAplikasi')->where('KodePengaturan', 'DEFAULT')->first();

            $settingData = [
                'NamaAplikasi' => $appName,
                'NamaPerusahaan' => $companyName,
                'ZonaWaktu' => $timezone,
                'SetupSelesai' => true,
                'TeksFooter' => 'Care Desk System. All rights reserved.',
                'NonAktif' => false,
                'TglEdit' => now(),
            ];

            if (! empty($mailHost)) {
                $settingData['MailMailer'] = 'smtp';
                $settingData['MailHost'] = $mailHost;
                $settingData['MailPort'] = $mailPort;
                $settingData['MailUsername'] = $mailUsername;
                if (! empty($mailPassword)) {
                    $settingData['MailPasswordTerenkripsi'] = Crypt::encryptString($mailPassword);
                }
                $settingData['MailEncryption'] = $mailEncryption;
                $settingData['MailFromAddress'] = $mailFromAddress;
                $settingData['MailFromName'] = $mailFromName ?: $appName;
            }

            if ($settingRow) {
                DB::table('MPengaturanAplikasi')->where('KodePengaturan', 'DEFAULT')->update($settingData);
            } else {
                DB::table('MPengaturanAplikasi')->insert(array_merge($settingData, [
                    'Id' => (string) Str::uuid(),
                    'KodePengaturan' => 'DEFAULT',
                    'BahasaDefault' => 'id',
                    'FormatTanggal' => 'd/m/Y',
                    'TglBuat' => now(),
                ]));
            }
        }

        // Flush caches and apply dynamic config
        AppSettings::flush();
        AppSettings::applyMailConfig();
        NavigationHelper::flush();

        $this->newLine();
        $this->info('====================================================');
        $this->info('   Setup Berhasil Selesai!                           ');
        $this->info('====================================================');
        $this->table(
            ['Konfigurasi', 'Nilai'],
            [
                ['Nama Brand / Aplikasi', $appName],
                ['Nama Perusahaan', $companyName],
                ['Zona Waktu', $timezone],
                ['Nama Administrator', $adminName],
                ['Email Administrator', $adminEmail],
                ['SMTP Email Sender', $mailHost ? "{$mailHost}:{$mailPort} ({$mailFromAddress})" : 'Belum diatur (bisa diatur via panel /admin)'],
                ['Status Setup', 'Selesai (SetupSelesai = true)'],
                ['URL Panel Admin', url('/admin')],
            ]
        );

        $this->info('Silakan buka ' . url('/admin') . ' di browser dan masuk dengan email administrator di atas.');

        return self::SUCCESS;
    }
}
