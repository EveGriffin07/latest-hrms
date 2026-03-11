<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->enum('item_type', ['EARNING', 'DEDUCTION']);
            $table->string('code', 64);
            $table->string('source_ref_type', 64)->nullable();
            $table->unsignedBigInteger('source_ref_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();

            $table->index(['payroll_run_id', 'code']);
            $table->index(['source_ref_type', 'source_ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_line_items');
    }
};
