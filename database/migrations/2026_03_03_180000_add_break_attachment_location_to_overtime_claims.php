<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_claims', 'break_minutes')) {
                $table->unsignedSmallInteger('break_minutes')->default(0)->after('end_time');
            }
            if (!Schema::hasColumn('overtime_claims', 'attachment_path')) {
                $table->string('attachment_path', 500)->nullable()->after('supporting_info');
            }
            if (!Schema::hasColumn('overtime_claims', 'location_other')) {
                $table->string('location_other', 255)->nullable()->after('location_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_claims', 'location_other')) {
                $table->dropColumn('location_other');
            }
            if (Schema::hasColumn('overtime_claims', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
            if (Schema::hasColumn('overtime_claims', 'break_minutes')) {
                $table->dropColumn('break_minutes');
            }
        });
    }
};
