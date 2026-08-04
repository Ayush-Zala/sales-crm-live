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
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('country_code');
            $table->string('fips_code')->nullable()->default('NULL');
            $table->string('iso2')->nullable()->default('NULL');
            $table->string('type')->nullable()->default('NULL');
            $table->decimal('latitude')->nullable();
            $table->decimal('longitude')->nullable();
            $table->tinyInteger('flag')->nullable()->default(1);
            $table->timestamps();
            $table->string('wikiDataId')->nullable()->default('NULL');
            $table->unsignedBigInteger('country_id');  // Add the region_id column
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
