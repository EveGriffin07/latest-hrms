<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_enrollment_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_step')->default(1)->after('status');
            $table->unsignedTinyInteger('movement_completed')->default(0)->after('current_step');
        });
    }

    public function down(): void
    {
        Schema::table('face_enrollment_sessions', function (Blueprint $table) {
            $table->dropColumn(['current_step', 'movement_completed']);
        });
    }
};
