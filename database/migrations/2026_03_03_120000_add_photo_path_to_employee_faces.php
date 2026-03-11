<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Store path to uploaded face photo (for display / comparison); embedding still used for matching.
     */
    public function up(): void
    {
        Schema::table('employee_faces', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_faces', 'photo_path')) {
                $table->string('photo_path', 500)->nullable()->after('employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_faces', function (Blueprint $table) {
            if (Schema::hasColumn('employee_faces', 'photo_path')) {
                $table->dropColumn('photo_path');
            }
        });
    }
};
