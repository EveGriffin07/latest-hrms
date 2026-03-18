<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_claims', 'short_note')) {
                $table->dropColumn('short_note');
            }
            if (Schema::hasColumn('overtime_claims', 'is_emergency')) {
                $table->dropColumn('is_emergency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            $table->string('short_note')->nullable()->after('reason');
            $table->boolean('is_emergency')->default(false)->after('short_note');
        });
    }
};

