<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id('appraisal_id');
            
            // Link to the Employee being reviewed
            $table->unsignedBigInteger('employee_id');
            
            // Link to the Manager/Supervisor doing the reviewing
            $table->unsignedBigInteger('evaluator_id')->nullable(); 
            
            // The specific review cycle (e.g., "Annual Review 2026", "Mid-Year 2026")
            $table->string('review_period'); 

            // ==========================================
            // CORE COMPETENCIES (Scored 1.0 to 5.0)
            // ==========================================
            $table->decimal('score_attendance', 3, 1)->nullable();
            $table->decimal('score_teamwork', 3, 1)->nullable();
            $table->decimal('score_productivity', 3, 1)->nullable();
            $table->decimal('score_communication', 3, 1)->nullable();
            
            // The final calculated average score
            $table->decimal('overall_score', 3, 1)->nullable();

            // ==========================================
            // QUALITATIVE FEEDBACK & COMMENTS
            // ==========================================
            $table->text('employee_comments')->nullable(); // Employee's self-evaluation notes
            $table->text('manager_comments')->nullable();  // Manager's final review notes

            // ==========================================
            // WORKFLOW TRACKING
            // ==========================================
            // pending_self_eval -> pending_manager -> completed
            $table->enum('status', ['pending_self_eval', 'pending_manager', 'completed'])->default('pending_self_eval');

            $table->timestamps();

            // Database Relationships
            $table->foreign('employee_id')->references('employee_id')->on('employees')->onDelete('cascade');
            $table->foreign('evaluator_id')->references('employee_id')->on('employees')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('appraisals');
    }
};