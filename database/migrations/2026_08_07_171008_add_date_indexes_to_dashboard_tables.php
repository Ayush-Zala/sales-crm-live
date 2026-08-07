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
        Schema::table('dispositions', function (Blueprint $table) {
            $table->index(['updated_at', 'status_id', 'user_id'], 'idx_disp_updated_status_user');
            $table->index(['updated_at', 'user_id'], 'idx_disp_updated_user');
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->index(['start_time', 'user_id'], 'idx_clog_start_user');
        });
        
        Schema::table('assign_companies', function (Blueprint $table) {
            $table->index(['created_at', 'assign_by', 'is_active'], 'idx_assign_created_by_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            $table->dropIndex('idx_disp_updated_status_user');
            $table->dropIndex('idx_disp_updated_user');
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropIndex('idx_clog_start_user');
        });
        
        Schema::table('assign_companies', function (Blueprint $table) {
            $table->dropIndex('idx_assign_created_by_active');
        });
    }
};
