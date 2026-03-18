<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->string('proof_requirement', 20)->default('none')->after('default_days_year')
                ->comment('none, optional, required');
            $table->string('proof_label', 100)->nullable()->after('proof_requirement');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('proof_path', 500)->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['proof_requirement', 'proof_label']);
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('proof_path');
        });
    }
};
