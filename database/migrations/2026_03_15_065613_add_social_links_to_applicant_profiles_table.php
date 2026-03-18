<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            // Adding the columns and making them optional (nullable)
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
        });
    }

    public function down()
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropColumn(['linkedin_url', 'portfolio_url']);
        });
    }
};
