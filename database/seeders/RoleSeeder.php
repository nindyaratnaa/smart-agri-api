<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $desa = Wilayah::where('level', 'desa')->first();
        $kecamatan = Wilayah::where('level', 'kecamatan')->first();
        $kabupaten = Wilayah::where('level', 'kabupaten')->first();
        $provinsi = Wilayah::where('level', 'provinsi')->first();

        $users = [
            [
                'name'       => 'Admin Sistem',
                'email'      => 'admin@smartagriculture.id',
                'password'   => Hash::make('Admin@12345'),
                'role'       => 'admin',
                'wilayah_id' => null,
                'jabatan'    => 'Administrator Sistem',
            ],
            [
                'name'       => 'Kepala Desa Kepanjen',
                'email'      => 'aparatur@smartagriculture.id',
                'password'   => Hash::make('Aparatur@12345'),
                'role'       => 'aparatur_desa',
                'wilayah_id' => $desa?->id,
                'nik'        => '3507032001000001',
                'jabatan'    => 'Kepala Desa',
            ],
            [
                'name'       => 'Bhabinkamtibmas Kepanjen',
                'email'      => 'bhabinkamtibmas@smartagriculture.id',
                'password'   => Hash::make('Bhabink@12345'),
                'role'       => 'bhabinkamtibmas',
                'wilayah_id' => $kecamatan?->id,
                'jabatan'    => 'Bhabinkamtibmas Kecamatan Kepanjen',
            ],
            [
                'name'       => 'Dinas Pertanian Kab. Malang',
                'email'      => 'dinas@smartagriculture.id',
                'password'   => Hash::make('Dinas@12345'),
                'role'       => 'dinas',
                'wilayah_id' => $kabupaten?->id,
                'jabatan'    => 'Kepala Dinas Pertanian Kabupaten Malang',
            ],
            [
                'name'       => 'Kementerian Pertanian RI',
                'email'      => 'pusat@smartagriculture.id',
                'password'   => Hash::make('Pusat@12345'),
                'role'       => 'pusat',
                'wilayah_id' => $provinsi?->id,
                'jabatan'    => 'Direktur Jenderal Tanaman Pangan',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('✅ Demo accounts created:');
        foreach ($users as $u) {
            $this->command->line("  {$u['role']}: {$u['email']} / password: see seeder");
        }
    }
}
