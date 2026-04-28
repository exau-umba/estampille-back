<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('qr_batches')->cascadeOnDelete();
            $table->unsignedInteger('serial');
            $table->string('code', 64)->unique();
            $table->char('token_hash', 64)->unique();
            $table->string('status', 24)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['batch_id', 'serial']);
            $table->index(['batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
