<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->string('period_month', 7)->unique();
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['OPEN','DRAFT','LOCKED','PAID','PUBLISHED'])->default('OPEN');
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->foreignId('published_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
                $table->decimal('basic_salary', 12, 2)->default(0);
                $table->decimal('allowance_total', 12, 2)->default(0);
                $table->decimal('ot_total', 12, 2)->default(0);
                $table->decimal('unpaid_leave_deduction', 12, 2)->default(0);
                $table->decimal('absent_deduction', 12, 2)->default(0);
                $table->decimal('late_deduction', 12, 2)->default(0);
                $table->decimal('penalty_total', 12, 2)->default(0);
                $table->decimal('adjustment_total', 12, 2)->default(0);
                $table->decimal('epf_total', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('gross_pay', 12, 2)->default(0);
                $table->decimal('net_pay', 12, 2)->default(0);
                $table->enum('status', ['DRAFT','LOCKED','PAID'])->default('DRAFT');
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->unique(['payroll_period_id', 'employee_id']);
            });
        }

        if (!Schema::hasTable('payroll_line_items')) {
            Schema::create('payroll_line_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
                $table->enum('item_type', ['EARNING','DEDUCTION']);
                $table->string('code', 50);
                $table->string('source_ref_type', 50)->nullable();
                $table->unsignedBigInteger('source_ref_id')->nullable();
                $table->decimal('quantity', 12, 2)->nullable();
                $table->decimal('rate', 12, 4)->nullable();
                $table->decimal('amount', 12, 2);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // no-op safe rollback
    }
};
