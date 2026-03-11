<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'service_years')) {
                $table->integer('service_years')->default(0)->after('hire_date');
            }
            if (!Schema::hasColumn('employees', 'service_band')) {
                $table->string('service_band', 20)->nullable()->after('service_years');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'service_band')) {
                $table->dropColumn('service_band');
            }
            if (Schema::hasColumn('employees', 'service_years')) {
                $table->dropColumn('service_years');
            }
        });
    }
};
