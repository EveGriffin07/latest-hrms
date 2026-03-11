<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->unique();
            $table->foreignId('employee_id')->nullable()->constrained('employees', 'employee_id')->onDelete('cascade');
            $table->timestamp('scanned_at');
            $table->string('device_id')->nullable();
            $table->string('mode', 20)->default('CHECK_IN'); // CHECK_IN | CHECK_OUT
            $table->string('status', 20)->default('IN_PROGRESS'); // IN_PROGRESS | SUCCESS | FAILED
            $table->string('failure_reason', 32)->nullable(); // NO_FACE | MULTI_FACE | LOW_QUALITY | LIVENESS_FAIL | BELOW_THRESHOLD | POLICY_BLOCK
            $table->float('confidence_score')->nullable();
            $table->timestamps();
        });

        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'device_id')) {
                $table->string('device_id')->nullable()->after('verify_score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'device_id')) {
                $table->dropColumn('device_id');
            }
        });
        Schema::dropIfExists('attendance_scan_sessions');
    }
};
