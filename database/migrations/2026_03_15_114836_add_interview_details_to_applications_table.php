<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dateTime('interview_datetime')->nullable();
            $table->string('interview_location')->nullable(); // Can be "Zoom link" or "Meeting Room A"
        });
    }

    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['interview_datetime', 'interview_location']);
        });
    }
};
