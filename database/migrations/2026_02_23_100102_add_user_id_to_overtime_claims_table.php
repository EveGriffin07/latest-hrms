<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_claims', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('employee_id');
            }
        });
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_claims', 'user_id')) {
                $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_claims', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
