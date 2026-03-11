<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'face_enrolled')) {
                $table->boolean('face_enrolled')->default(false)->after('employee_status');
            }
            if (! Schema::hasColumn('employees', 'face_enrolled_at')) {
                $table->timestamp('face_enrolled_at')->nullable()->after('face_enrolled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'face_enrolled')) {
                $table->dropColumn('face_enrolled');
            }
            if (Schema::hasColumn('employees', 'face_enrolled_at')) {
                $table->dropColumn('face_enrolled_at');
            }
        });
    }
};
