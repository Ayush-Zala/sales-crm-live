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
        Schema::create('zoom_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'call_logs', 'meetings', 'recordings'
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed'
            $table->integer('total_processed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_sync_logs');
    }
};
