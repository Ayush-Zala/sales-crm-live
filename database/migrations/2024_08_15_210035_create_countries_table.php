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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->char('iso3')->nullable()->default('NULL');
            $table->char('numeric_code')->nullable()->default('NULL');
            $table->char('iso2')->nullable()->default('NULL');
            $table->string('phonecode')->nullable()->default('NULL');
            $table->string('capital')->nullable()->default('NULL');
            $table->string('currency')->nullable()->default('NULL');
            $table->string('currency_name')->nullable()->default('NULL');
            $table->string('currency_symbol')->nullable()->default('NULL');
            $table->string('tld')->nullable()->default('NULL');
            $table->string('native')->nullable()->default('NULL');
            $table->string('region')->nullable()->default('NULL');
            $table->string('subregion')->nullable()->default('NULL');
            $table->string('nationality')->nullable()->default('NULL');
            $table->text('timezones');
            $table->text('translations');
            $table->decimal('latitude')->nullable();
            $table->decimal('longitude')->nullable();
            $table->string('emoji')->nullable()->default('NULL');
            $table->string('emojiU')->nullable()->default('NULL');
            $table->tinyInteger('flag')->nullable()->default(1);
            $table->timestamps();
            $table->string('wikiDataId')->nullable()->default('NULL');

            $table->unsignedBigInteger('region_id');  // Add the region_id column
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->unsignedBigInteger('subregion_id');  // Add the region_id column
            $table->foreign('subregion_id')->references('id')->on('subregions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
