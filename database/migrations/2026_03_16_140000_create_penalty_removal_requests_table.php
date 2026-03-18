<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('penalty_removal_requests')) {
            return;
        }

        Schema::create('penalty_removal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penalty_id')->constrained('penalties', 'penalty_id')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();

            $table->text('request_reason');
            $table->string('attachment_path')->nullable();
            $table->text('employee_note')->nullable();
            $table->text('supervisor_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->string('status', 64);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('supervisor_reviewed_at')->nullable();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->timestamp('final_decision_at')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['supervisor_id', 'status']);
            $table->index(['penalty_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_removal_requests');
    }
};

