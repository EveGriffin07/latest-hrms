<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payroll_periods')) {
            return;
        }

        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'release_note')) {
                $table->text('release_note')->nullable()->after('locked_by');
            }
            if (!Schema::hasColumn('payroll_periods', 'snapshot')) {
                $table->json('snapshot')->nullable()->after('release_note');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payroll_periods')) {
            return;
        }
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'release_note')) {
                $table->dropColumn('release_note');
            }
            if (Schema::hasColumn('payroll_periods', 'snapshot')) {
                $table->dropColumn('snapshot');
            }
        });
    }
};
