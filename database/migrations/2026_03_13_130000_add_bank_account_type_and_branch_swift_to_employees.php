<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('account_type', 20)->nullable()->after('bank_code')->comment('savings, current');
            $table->string('branch_swift_code', 50)->nullable()->after('account_type')->comment('Branch or SWIFT code, optional');
        });
        // Clarify: bank_code stores internal bank identifier (e.g. MBB); branch_swift_code stores optional branch/SWIFT
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'branch_swift_code']);
        });
    }
};
