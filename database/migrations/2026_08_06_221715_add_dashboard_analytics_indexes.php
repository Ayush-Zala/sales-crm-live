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
            $table->index(['user_id', 'updated_at'], 'idx_disp_user_updated');
            $table->index(['user_id', 'status_id', 'updated_at'], 'idx_disp_user_status_updated');
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->index(['user_id', 'start_time'], 'idx_clog_user_start');
        });

        Schema::table('assign_companies', function (Blueprint $table) {
            $table->index(['is_active', 'assign_by', 'created_at'], 'idx_acomp_active_assign_created');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index(['created_at'], 'idx_comp_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            $table->dropIndex('idx_disp_user_updated');
            $table->dropIndex('idx_disp_user_status_updated');
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropIndex('idx_clog_user_start');
        });

        Schema::table('assign_companies', function (Blueprint $table) {
            $table->dropIndex('idx_acomp_active_assign_created');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('idx_comp_created');
        });
    }
};
