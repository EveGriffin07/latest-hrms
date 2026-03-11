<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penalties', function (Blueprint $table) {
            if (! Schema::hasColumn('penalties', 'rejection_remark')) {
                $table->text('rejection_remark')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penalties', function (Blueprint $table) {
            if (Schema::hasColumn('penalties', 'rejection_remark')) {
                $table->dropColumn('rejection_remark');
            }
        });
    }
};
