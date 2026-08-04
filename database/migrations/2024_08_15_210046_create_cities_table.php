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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->string('state_code');

            $table->char('country_code');
            $table->decimal('latitude');
            $table->decimal('longitude');
            $table->timestamps();
            $table->tinyInteger('flag')->nullable()->default(1);

            $table->string('wikiDataId')->nullable();


            $table->unsignedBigInteger('state_id');  // Add the region_id column
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->unsignedBigInteger('country_id');  // Add the region_id column
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
