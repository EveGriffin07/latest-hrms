<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_records', 'reason')) {
                $table->string('reason')->nullable()->after('rate_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_records', 'reason')) {
                $table->dropColumn('reason');
            }
        });
    }
};
