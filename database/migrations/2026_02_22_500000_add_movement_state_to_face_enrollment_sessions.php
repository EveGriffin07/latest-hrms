<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_enrollment_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('face_enrollment_sessions', 'movement_state')) {
                $table->string('movement_state', 32)->default('CENTER_REQUIRED')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('face_enrollment_sessions', function (Blueprint $table) {
            $table->dropColumn('movement_state');
        });
    }
};
