<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        // Provinsi
        $jatim = Wilayah::create([
            'kode' => '35',
            'nama' => 'Jawa Timur',
            'level' => 'provinsi',
            'koordinat_lat' => -7.5360638,
            'koordinat_lng' => 112.2384017,
        ]);

        // Kabupaten
        $malang = Wilayah::create([
            'kode' => '3507',
            'nama' => 'Kabupaten Malang',
            'level' => 'kabupaten',
            'parent_id' => $jatim->id,
            'koordinat_lat' => -8.1845,
            'koordinat_lng' => 112.6288,
        ]);

        // Kecamatan
        $kepanjen = Wilayah::create([
            'kode' => '350703',
            'nama' => 'Kepanjen',
            'level' => 'kecamatan',
            'parent_id' => $malang->id,
            'koordinat_lat' => -8.1302,
            'koordinat_lng' => 112.5702,
        ]);

        // Desa
        Wilayah::create([
            'kode' => '3507032001',
            'nama' => 'Desa Kepanjen',
            'level' => 'desa',
            'parent_id' => $kepanjen->id,
            'koordinat_lat' => -8.1312,
            'koordinat_lng' => 112.5710,
        ]);

        Wilayah::create([
            'kode' => '3507032002',
            'nama' => 'Desa Panggungrejo',
            'level' => 'desa',
            'parent_id' => $kepanjen->id,
            'koordinat_lat' => -8.1400,
            'koordinat_lng' => 112.5800,
        ]);
    }
}
