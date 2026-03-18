<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->decimal('previous_salary', 12, 2);
            $table->decimal('new_salary', 12, 2);
            $table->string('effective_month', 7); // YYYY-MM
            $table->text('reason')->nullable();
            $table->string('status', 32)->default('approved'); // pending, approved
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_revisions');
    }
};
