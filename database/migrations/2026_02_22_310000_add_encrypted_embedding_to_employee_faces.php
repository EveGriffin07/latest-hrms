<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_faces', function (Blueprint $table) {
            $table->longText('embedding_encrypted')->nullable()->after('embedding');
        });
    }

    public function down(): void
    {
        Schema::table('employee_faces', function (Blueprint $table) {
            $table->dropColumn('embedding_encrypted');
        });
    }
};
