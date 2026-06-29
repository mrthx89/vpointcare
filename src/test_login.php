
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\Master\Pengguna;

echo "=== Testing Login ===\n\n";

$email = 'admin@mail.com';
$password = 'password123';

echo "1. Mencari user dengan email: $email...\n";
$user = Pengguna::where('Email', $email)->first();
if (!$user) {
    echo "ERROR: User tidak ditemukan!\n";
    exit(1);
}
echo "   SUCCESS: User ditemukan!\n";
echo "   ID: $user->Id\n";
echo "   Nama: $user->NamaPengguna\n";
echo "   NonAktif: " . ($user->NonAktif ? 'Ya' : 'Tidak') . "\n";
echo "   Status Registrasi: $user->StatusRegistrasi\n";
echo "   Email Terverifikasi: " . ($user->EmailTerverifikasiPada ? 'Ya ('.$user->EmailTerverifikasiPada.')' : 'Tidak') . "\n\n";

echo "2. Coba autentikasi dengan password: $password...\n";
if (Auth::attempt(['Email' => $email, 'password' => $password])) {
    echo "   SUCCESS: Autentikasi berhasil!\n";
    echo "   User yang login: " . Auth::user()->NamaPengguna . "\n";
    echo "   Role Code: " . Auth::user()->roleCode() . "\n";
    echo "   Izin (Permissions):\n";
    foreach (Auth::user()->permissionCodes() as $perm) {
        echo "      - $perm\n";
    }
} else {
    echo "   ERROR: Autentikasi gagal!\n";
    echo "   Password mungkin salah atau tidak ter-hash dengan benar.\n";
}

echo "\n=== Test Selesai ===\n";
