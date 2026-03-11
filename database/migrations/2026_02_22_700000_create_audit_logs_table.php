<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('actor_type', 20)->default('EMPLOYEE'); // EMPLOYEE | ADMIN | SYSTEM
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action_category', 32); // AUTH | FACE | ATTENDANCE | LEAVE | PROFILE
            $table->string('action_type', 64);
            $table->string('action_status', 20)->default('SUCCESS'); // SUCCESS | FAILED
            $table->string('severity', 10)->default('INFO'); // INFO | WARN | ERROR
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action_category', 'created_at']);
            $table->index(['employee_id', 'created_at']);
            $table->index('actor_id');
        });

        try {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreign('employee_id')->references('employee_id')->on('employees')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }

        try {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreign('actor_id')->references('user_id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
