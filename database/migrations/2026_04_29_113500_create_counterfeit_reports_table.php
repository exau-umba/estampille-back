<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counterfeit_reports', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('location', 255);
            $table->text('description');
            $table->string('contact', 255)->nullable();
            $table->string('image_path', 255);
            $table->string('image_url', 255);
            $table->string('status', 24)->default('pending');
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->index(['status', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterfeit_reports');
    }
};
