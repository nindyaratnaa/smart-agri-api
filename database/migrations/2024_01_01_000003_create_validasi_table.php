<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validasi', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->foreignUuid('data_pertanian_id')->constrained('data_pertanian');
            $table->foreignUuid('validator_id')->constrained('users');
            $table->enum('keputusan', ['disetujui', 'ditolak']);
            $table->text('catatan')->nullable();
            $table->timestamp('validated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validasi');
    }
};
