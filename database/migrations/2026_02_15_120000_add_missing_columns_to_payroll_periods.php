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
            if (!Schema::hasColumn('payroll_periods', 'status')) {
                $table->string('status', 20)->default('OPEN')->after('end_date');
            }
            if (!Schema::hasColumn('payroll_periods', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payroll_periods', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users', 'user_id')->nullOnDelete();
            }
            if (!Schema::hasColumn('payroll_periods', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('locked_by');
            }
            if (!Schema::hasColumn('payroll_periods', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users', 'user_id')->nullOnDelete();
            }
            if (!Schema::hasColumn('payroll_periods', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('paid_by');
            }
            if (!Schema::hasColumn('payroll_periods', 'published_by')) {
                $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users', 'user_id')->nullOnDelete();
            }
            if (!Schema::hasColumn('payroll_periods', 'period_month')) {
                $table->string('period_month', 7)->after('id');
            }
        });

        // ensure unique index on period_month
        $connection = Schema::getConnection();
        $tableName = $connection->getTablePrefix() . 'payroll_periods';
        $uniqueExists = $connection->selectOne("
            SELECT COUNT(*) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = database()
              AND table_name = ?
              AND column_name = 'period_month'
              AND non_unique = 0
        ", [$tableName]);

        if (($uniqueExists->cnt ?? 0) === 0) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->unique('period_month');
            });
        }
    }

    public function down(): void
    {
        // No destructive rollback to avoid dropping existing data
    }
};
