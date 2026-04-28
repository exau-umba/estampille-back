<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_batches', function (Blueprint $table): void {
            $table->foreignUlid('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->foreignUlid('product_id')->nullable()->after('company_id')->constrained('products')->nullOnDelete();
            $table->foreignUlid('certificate_id')->nullable()->after('product_id')->constrained('certificates')->nullOnDelete();
        });

        Schema::table('qr_codes', function (Blueprint $table): void {
            $table->timestamp('revoked_at')->nullable()->after('expires_at');
            $table->string('revocation_reason')->nullable()->after('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table): void {
            $table->dropColumn(['revoked_at', 'revocation_reason']);
        });

        Schema::table('qr_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('certificate_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
