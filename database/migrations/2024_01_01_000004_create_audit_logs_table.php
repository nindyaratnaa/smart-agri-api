<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 100);
            $table->string('tabel_target', 50)->nullable();
            $table->uuid('record_id')->nullable();
            $table->jsonb('data_lama')->nullable();
            $table->jsonb('data_baru')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Immutable: no updated_at, no softDeletes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
