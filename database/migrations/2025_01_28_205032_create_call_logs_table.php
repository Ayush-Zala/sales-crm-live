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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('call_id')->size(400);
            $table->string('caller_number')->size(300)->nullable();
            $table->string('callee_number')->size(300)->nullable();
            $table->datetime('start_time')->size(300)->nullable();
            $table->datetime('answer_time')->size(300)->nullable();
            $table->datetime('end_time')->size(300)->nullable();
            $table->string('call_duration')->size(300)->nullable();
            $table->string('direction')->size(50)->nullable();
            $table->string('department')->size(50)->nullable();
            $table->string('caller_name')->size(150)->nullable();
            $table->string('caller_email')->size(350)->nullable();
            $table->string('calle_name')->size(150)->nullable();
            $table->string('calle_email')->size(350)->nullable();
            $table->string('international')->size(55)->nullable();
            $table->string('event')->size(200)->nullable();
            $table->string('result')->size(55)->nullable();
            $table->string('caller_ext_number')->size(55)->nullable();
            $table->string('caller_ext_type')->size(55)->nullable();
            $table->string('caller_number_type')->size(55)->nullable();
            $table->string('caller_device_type')->size(200)->nullable();
            $table->string('group_id')->size(100)->nullable();
            $table->string('recording_id')->size(100)->nullable();
            $table->string('recording_type')->size(55)->nullable();
            $table->string('talk_time')->size(55)->nullable();
            $table->string('hold_time')->size(55)->nullable();
            $table->string('wait_time')->size(55)->nullable();
            $table->string('ai_call_summary_id')->size(100)->nullable();
            $table->string('operator_name')->size(100)->nullable();
            $table->string('operator_ext_number')->size(55)->nullable();
            $table->string('operator_ext_Type')->size(55)->nullable();
            $table->string('operator_ext_id')->size(55)->nullable();
            $table->longText('download_url')->nullable();
            $table->longText('file_url')->nullable();
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
        Schema::dropIfExists('call_logs');
    }
};
