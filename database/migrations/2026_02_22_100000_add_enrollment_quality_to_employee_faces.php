<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_faces', function (Blueprint $table) {
            $table->string('model_version')->nullable()->after('model_name');
            $table->float('enrollment_quality_score')->nullable()->after('model_version');
        });
    }

    public function down(): void
    {
        Schema::table('employee_faces', function (Blueprint $table) {
            $table->dropColumn(['model_version', 'enrollment_quality_score']);
        });
    }
};
