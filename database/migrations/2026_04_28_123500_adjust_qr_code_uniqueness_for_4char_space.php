<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('qr_codes')) {
            return;
        }

        Schema::table('qr_codes', function (Blueprint $table): void {
            try {
                $table->dropUnique('qr_codes_code_unique');
            } catch (\Throwable) {
                // constraint may already be removed
            }

            try {
                $table->unique(['batch_id', 'code'], 'qr_codes_batch_id_code_unique');
            } catch (\Throwable) {
                // constraint may already exist
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('qr_codes')) {
            return;
        }

        Schema::table('qr_codes', function (Blueprint $table): void {
            try {
                $table->dropUnique('qr_codes_batch_id_code_unique');
            } catch (\Throwable) {
                // constraint may already be removed
            }

            try {
                $table->unique('code', 'qr_codes_code_unique');
            } catch (\Throwable) {
                // constraint may already exist
            }
        });
    }
};
