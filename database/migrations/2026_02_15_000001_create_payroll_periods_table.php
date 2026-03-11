<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration upgrades the existing payroll_periods table to the new schema.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payroll_periods')) {
            // Fallback: create table if it was missing (keeps primary key name consistent with legacy).
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id('period_id');
                $table->string('period_month', 7)->unique(); // Format: YYYY-MM
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['OPEN', 'DRAFT', 'LOCKED', 'PAID', 'PUBLISHED'])->default('OPEN');
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->foreignId('published_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->timestamps();
            });
            return;
        }

        Schema::table('payroll_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_periods', 'status')) {
                $table->enum('status', ['OPEN', 'DRAFT', 'LOCKED', 'PAID', 'PUBLISHED'])
                    ->default('OPEN')
                    ->after('end_date');
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
            // Ensure period_month uniqueness and length
            if (!Schema::hasColumn('payroll_periods', 'period_month')) {
                $table->string('period_month', 7)->after('period_id');
            }
        });

        // Add unique index on period_month if missing
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

        // Drop legacy payroll_status column if it exists
        if (Schema::hasColumn('payroll_periods', 'payroll_status')) {
            Schema::table('payroll_periods', function (Blueprint $table) {
                $table->dropColumn('payroll_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'published_by')) {
                $table->dropConstrainedForeignId('published_by');
            }
            if (Schema::hasColumn('payroll_periods', 'published_at')) {
                $table->dropColumn('published_at');
            }
            if (Schema::hasColumn('payroll_periods', 'paid_by')) {
                $table->dropConstrainedForeignId('paid_by');
            }
            if (Schema::hasColumn('payroll_periods', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('payroll_periods', 'locked_by')) {
                $table->dropConstrainedForeignId('locked_by');
            }
            if (Schema::hasColumn('payroll_periods', 'locked_at')) {
                $table->dropColumn('locked_at');
            }
            if (Schema::hasColumn('payroll_periods', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('payroll_periods', 'period_month')) {
                $table->dropUnique('payroll_periods_period_month_unique');
                $table->dropColumn('period_month');
            }
        });
    }
};
