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
        Schema::create('meeting_logs', function (Blueprint $table) {
            $table->id();

            $table->string('meeting_id')->size(100);
            $table->string('account_id')->size(100)->nullable();
            $table->string('host_id')->size(100)->nullable();
            $table->string('topic')->size(200)->nullable();
            $table->string('type')->size(100)->nullable();
            $table->datetime('start_time')->size(300)->nullable();
            $table->datetime('record_start')->size(300)->nullable();
            $table->datetime('record_end')->size(300)->nullable();
            $table->string('timezone')->size(100)->nullable();
            $table->string('duration')->size(100)->nullable();
            $table->longText('share_url')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_logs');
    }
};
