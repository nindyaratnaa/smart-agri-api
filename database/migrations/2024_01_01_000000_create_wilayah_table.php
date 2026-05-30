<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->string('kode', 20)->unique();
            $table->string('nama', 100);
            $table->enum('level', ['desa', 'kecamatan', 'kabupaten', 'provinsi']);
            $table->uuid('parent_id')->nullable();
            $table->decimal('koordinat_lat', 10, 8)->nullable();
            $table->decimal('koordinat_lng', 11, 8)->nullable();
            $table->timestamps();
        });

        Schema::table('wilayah', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('wilayah')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
