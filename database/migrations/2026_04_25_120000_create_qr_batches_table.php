<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('product_name');
            $table->string('prefix', 24)->default('EST');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('total_generated')->default(0);
            $table->string('status', 24)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_batches');
    }
};
