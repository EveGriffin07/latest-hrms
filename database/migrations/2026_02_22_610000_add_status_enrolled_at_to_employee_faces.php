<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_faces', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_faces', 'status')) {
                $table->string('status', 20)->default('ACTIVE')->after('enrollment_quality_score');
            }
            if (! Schema::hasColumn('employee_faces', 'enrolled_at')) {
                $table->timestamp('enrolled_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_faces', function (Blueprint $table) {
            $table->dropColumn(['status', 'enrolled_at']);
        });
    }
};
