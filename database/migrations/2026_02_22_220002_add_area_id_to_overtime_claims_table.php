<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_claims', 'area_id')) {
                $table->foreignId('area_id')->nullable()->after('employee_id')->constrained('areas')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_claims', 'area_id')) {
                $table->dropForeign(['area_id']);
            }
        });
    }
};
