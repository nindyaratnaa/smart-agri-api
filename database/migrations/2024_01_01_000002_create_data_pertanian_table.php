<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_pertanian', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->foreignUuid('wilayah_id')->constrained('wilayah');
            $table->foreignUuid('input_oleh')->constrained('users');
            $table->string('komoditas', 100);
            $table->string('varietas', 100)->nullable();
            $table->decimal('luas_lahan_ha', 10, 2);
            $table->decimal('estimasi_panen_ton', 10, 2);
            $table->enum('musim_tanam', ['MH', 'MK', 'MT']);
            $table->enum('status_validasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('foto_url')->nullable();
            $table->date('periode');
            $table->decimal('koordinat_lat', 10, 8)->nullable();
            $table->decimal('koordinat_lng', 11, 8)->nullable();
            $table->text('catatan_input')->nullable();
            $table->boolean('is_draft')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_pertanian');
    }
};
