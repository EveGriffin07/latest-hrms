<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->text('personal_summary')->nullable();
            $table->text('career_history')->nullable();
            $table->text('education_details')->nullable();
            $table->text('licenses_certifications')->nullable();
            // Skills is already there based on our previous chat, but let's ensure it exists
            if (!Schema::hasColumn('applicant_profiles', 'skills')) {
                $table->string('skills')->nullable();
            }
            $table->string('languages')->nullable();
            
            // Malaysian Address Standard
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();

            // Industry Interest
            $table->string('industry_interest')->nullable();
        });
    }

    public function down()
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'personal_summary',
                'career_history',
                'education_details',
                'licenses_certifications',
                'skills',
                'languages',
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'postcode',
                'industry_interest'
            ]);
        });
    }
};
