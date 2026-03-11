<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'actor_name')) {
                $table->string('actor_name', 255)->nullable()->after('actor_id');
            }
            if (! Schema::hasColumn('audit_logs', 'actor_role')) {
                $table->string('actor_role', 64)->nullable()->after('actor_name');
            }
            if (! Schema::hasColumn('audit_logs', 'actor_avatar_url')) {
                $table->string('actor_avatar_url', 512)->nullable()->after('actor_role');
            }
            if (! Schema::hasColumn('audit_logs', 'entity_type')) {
                $table->string('entity_type', 64)->nullable()->after('action_category');
            }
            if (! Schema::hasColumn('audit_logs', 'entity_id')) {
                $table->string('entity_id', 64)->nullable()->after('entity_type');
            }
            if (! Schema::hasColumn('audit_logs', 'log_type')) {
                $table->string('log_type', 32)->default('Web')->after('action_status'); // Web | API | System | FaceScan
            }
            if (! Schema::hasColumn('audit_logs', 'environment')) {
                $table->string('environment', 32)->default('Production')->after('log_type'); // Production | Staging | Demo
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('action_type');
            $table->index('entity_type');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $cols = ['actor_name', 'actor_role', 'actor_avatar_url', 'entity_type', 'entity_id', 'log_type', 'environment'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('audit_logs', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
