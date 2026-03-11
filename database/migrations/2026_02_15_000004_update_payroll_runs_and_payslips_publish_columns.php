<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_runs')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                if (!Schema::hasColumn('payroll_runs', 'is_published')) {
                    $table->boolean('is_published')->default(false)->after('status');
                }
                if (!Schema::hasColumn('payroll_runs', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('is_published');
                }
            });
        }

        if (Schema::hasTable('payslips')) {
            Schema::table('payslips', function (Blueprint $table) {
                if (!Schema::hasColumn('payslips', 'payroll_run_id')) {
                    $table->foreignId('payroll_run_id')->nullable()->after('payslip_id')->constrained('payroll_runs');
                }
                if (!Schema::hasColumn('payslips', 'period_month')) {
                    $table->string('period_month', 7)->nullable()->after('period_id');
                }
                if (!Schema::hasColumn('payslips', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('generated_at');
                }
                if (!Schema::hasColumn('payslips', 'publish_version')) {
                    $table->unsignedInteger('publish_version')->default(1)->after('published_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_runs')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                if (Schema::hasColumn('payroll_runs', 'published_at')) {
                    $table->dropColumn('published_at');
                }
                if (Schema::hasColumn('payroll_runs', 'is_published')) {
                    $table->dropColumn('is_published');
                }
            });
        }

        if (Schema::hasTable('payslips')) {
            Schema::table('payslips', function (Blueprint $table) {
                if (Schema::hasColumn('payslips', 'publish_version')) {
                    $table->dropColumn('publish_version');
                }
                if (Schema::hasColumn('payslips', 'published_at')) {
                    $table->dropColumn('published_at');
                }
                if (Schema::hasColumn('payslips', 'period_month')) {
                    $table->dropColumn('period_month');
                }
                if (Schema::hasColumn('payslips', 'payroll_run_id')) {
                    $table->dropConstrainedForeignId('payroll_run_id');
                }
            });
        }
    }
};
