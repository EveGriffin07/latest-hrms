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
            $table->string('bank_name', 100)->nullable()->after('address');
            $table->string('bank_account_holder', 120)->nullable()->after('bank_name');
            $table->string('bank_account_number', 50)->nullable()->after('bank_account_holder');
            $table->string('bank_code', 20)->nullable()->after('bank_account_number')->comment('e.g. SWIFT, branch code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_holder', 'bank_account_number', 'bank_code']);
        });
    }
};
