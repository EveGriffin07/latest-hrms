<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Statutory flag on leave_types
        if (!Schema::hasColumn('leave_types', 'is_statutory')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->boolean('is_statutory')->default(true)->after('default_days_year');
            });
        }

        // Override table
        Schema::create('leave_balance_overrides', function (Blueprint $table) {
            $table->id('override_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types', 'leave_type_id')->onDelete('cascade');
            $table->integer('plan_year');
            $table->integer('total_entitlement')->default(0);
            $table->foreignId('updated_by')->constrained('users', 'user_id');
            $table->text('reason');
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id', 'plan_year'], 'lb_override_unique');
        });

        // Audit history
        Schema::create('leave_balance_adjustments', function (Blueprint $table) {
            $table->id('adjustment_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types', 'leave_type_id')->onDelete('cascade');
            $table->integer('plan_year');
            $table->integer('old_total')->default(0);
            $table->integer('new_total')->default(0);
            $table->foreignId('admin_user_id')->constrained('users', 'user_id');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_adjustments');
        Schema::dropIfExists('leave_balance_overrides');

        if (Schema::hasColumn('leave_types', 'is_statutory')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->dropColumn('is_statutory');
            });
        }
    }
};
