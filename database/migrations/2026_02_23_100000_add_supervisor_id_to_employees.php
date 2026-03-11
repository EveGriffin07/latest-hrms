<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'supervisor_id')) {
            return;
        }
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('supervisor_id')->nullable()->after('department_id');
        });
        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreign('supervisor_id')->references('user_id')->on('users')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn('supervisor_id');
        });
    }
};
