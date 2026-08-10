<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('queued'); // queued | running | completed | failed
            $table->unsignedInteger('months')->default(6);
            $table->json('summary')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_import_logs');
    }
};
