<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (!Schema::hasColumn('companies', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('companies', 'country')) {
                $table->string('country')->nullable()->after('website');
            }
            if (!Schema::hasColumn('companies', 'province')) {
                $table->string('province')->nullable()->after('country');
            }
            if (!Schema::hasColumn('companies', 'province_code')) {
                $table->string('province_code', 16)->nullable()->after('province');
            }
            if (!Schema::hasColumn('companies', 'address')) {
                $table->string('address')->nullable()->after('province_code');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('companies', function (Blueprint $table): void {
            $columns = ['registration_number', 'phone', 'country', 'province', 'province_code', 'address'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
