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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('website');
            $table->string('fax');
            $table->string('converted');
            $table->unsignedBigInteger('assign_by')->nullable();
            $table->unsignedBigInteger('create_user_id')->nullable();
            $table->unsignedBigInteger('assign_to')->nullable();
            // $table->unsignedBigInteger('assign')->nullable();
            $table->foreign('assign_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('create_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('assign_to')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            // $table->foreign('assign')
            //     ->references('id')
            //     ->on('users')
            //     ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
