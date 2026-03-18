<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('applicant_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->string('language_name'); // e.g., English, Chinese - Mandarin
            $table->string('proficiency')->nullable(); // e.g., Native, Fluent, Basic
            $table->timestamps();

            $table->foreign('applicant_id')->references('applicant_id')->on('applicant_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_languages');
    }
};
