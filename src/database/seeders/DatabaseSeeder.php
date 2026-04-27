<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()
            ->where('email', 'admin@vpointcare.local')
            ->delete();

        User::query()->updateOrCreate([
            'email' => 'mrthx.89@gmail.com',
        ], [
            'name' => 'Admin VPoint Care',
            'password' => Hash::make('Ell1t3s3rv'),
        ]);

        if (! Schema::hasTable('MPeran') || ! Schema::hasTable('MPengguna')) {
            return;
        }

        DB::table('MPeran')->updateOrInsert([
            'KodePeran' => 'ADMIN',
        ], [
            'NamaPeran' => 'Admin',
            'Keterangan' => 'Akses penuh aplikasi',
            'NonAktif' => false,
            'TglEdit' => now(),
        ]);

        $peranAdmin = DB::table('MPeran')->where('KodePeran', 'ADMIN')->first();

        DB::table('MPengguna')->updateOrInsert([
            'Email' => 'mrthx.89@gmail.com',
        ], [
            'IdPeran' => $peranAdmin->Id,
            'NamaPengguna' => 'Admin VPoint Care',
            'Password' => Hash::make('Ell1t3s3rv'),
            'NonAktif' => false,
            'TglEdit' => now(),
        ]);
    }
}
