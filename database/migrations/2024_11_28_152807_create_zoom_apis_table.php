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
        Schema::create('zoom_apis', function (Blueprint $table) {
            $table->id();

            $table->string('email_id')->size(400);
            $table->string('password')->size(300);
            $table->string('account_id')->size(300);
            $table->string('client_key')->size(300);
            $table->string('client_secret')->size(300);

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_apis');
    }
};
