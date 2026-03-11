<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_records', 'supervisor_approved_by')) {
                $table->unsignedBigInteger('supervisor_approved_by')->nullable()->after('ot_status');
                $table->foreign('supervisor_approved_by')->references('user_id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            $table->dropForeign(['supervisor_approved_by']);
            $table->dropColumn('supervisor_approved_by');
        });
    }
};
