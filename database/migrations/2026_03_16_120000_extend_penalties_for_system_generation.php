<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penalties', function (Blueprint $table) {
            if (! Schema::hasColumn('penalties', 'penalty_type')) {
                $table->string('penalty_type', 32)->nullable()->after('attendance_id');
            }
            if (! Schema::hasColumn('penalties', 'created_source')) {
                $table->string('created_source', 32)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penalties', function (Blueprint $table) {
            if (Schema::hasColumn('penalties', 'penalty_type')) {
                $table->dropColumn('penalty_type');
            }
            if (Schema::hasColumn('penalties', 'created_source')) {
                $table->dropColumn('created_source');
            }
        });
    }
};

