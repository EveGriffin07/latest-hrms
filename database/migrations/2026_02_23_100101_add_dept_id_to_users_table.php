<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'dept_id')) {
                $table->unsignedBigInteger('dept_id')->nullable()->after('avatar_path');
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dept_id')) {
                $table->foreign('dept_id')->references('department_id')->on('departments')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dept_id')) {
                $table->dropForeign(['dept_id']);
                $table->dropColumn('dept_id');
            }
        });
    }
};
