<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->uuid('qr_code_id')->nullable();
            $table->char('token_hash', 64)->nullable()->index();
            $table->string('result', 24);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->foreign('qr_code_id')->references('id')->on('qr_codes')->nullOnDelete();
            $table->index(['qr_code_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_events');
    }
};
