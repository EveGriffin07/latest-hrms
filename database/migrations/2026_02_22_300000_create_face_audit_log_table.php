<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('face_audit_logs')) {
            Schema::create('face_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 32);
                $table->foreignId('employee_id')->nullable()->constrained('employees', 'employee_id')->onDelete('set null');
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_type', 20)->nullable();
                $table->boolean('success')->default(false);
                $table->string('failure_reason', 64)->nullable();
                $table->decimal('confidence_score', 8, 4)->nullable();
                $table->text('reason')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                $table->index(['event_type', 'occurred_at']);
                $table->index('employee_id');
            });
        }

        try {
            Schema::table('face_audit_logs', function (Blueprint $table) {
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
        Schema::dropIfExists('face_audit_logs');
    }
};
