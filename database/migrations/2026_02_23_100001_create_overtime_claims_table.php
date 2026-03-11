<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
            $table->foreignId('period_id')->nullable()->constrained('payroll_periods', 'period_id')->onDelete('set null');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 5, 2);
            $table->decimal('rate_type', 5, 2)->default(1.5);
            $table->string('reason')->nullable();
            $table->text('supporting_info')->nullable();

            $table->string('status', 32)->default('DRAFT');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->text('supervisor_remark')->nullable();
            $table->timestamp('supervisor_action_at')->nullable();

            $table->unsignedBigInteger('admin_acted_by')->nullable();
            $table->text('admin_remark')->nullable();
            $table->timestamp('admin_action_at')->nullable();

            $table->unsignedBigInteger('overtime_record_id')->nullable()->comment('Set when admin approves; links to payroll');
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->index('supervisor_id');
        });

        try {
            Schema::table('overtime_claims', function (Blueprint $table) {
                $table->foreign('supervisor_id')->references('user_id')->on('users')->onDelete('set null');
                $table->foreign('admin_acted_by')->references('user_id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_claims');
    }
};
