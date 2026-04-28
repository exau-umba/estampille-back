<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE qr_codes DROP CONSTRAINT IF EXISTS qr_codes_code_unique');

        DB::statement(
            "DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'qr_codes_batch_id_code_unique'
                ) THEN
                    ALTER TABLE qr_codes ADD CONSTRAINT qr_codes_batch_id_code_unique UNIQUE (batch_id, code);
                END IF;
            END
            $$;"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE qr_codes DROP CONSTRAINT IF EXISTS qr_codes_batch_id_code_unique');

        DB::statement(
            "DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'qr_codes_code_unique'
                ) THEN
                    ALTER TABLE qr_codes ADD CONSTRAINT qr_codes_code_unique UNIQUE (code);
                END IF;
            END
            $$;"
        );
    }
};
