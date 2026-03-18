<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            $table->string('supervisor_action_type', 32)->nullable()->after('supervisor_action_at')
                ->comment('approved, approved_with_adjustment, escalated_to_admin');
            $table->text('adjustment_reason')->nullable()->after('supervisor_action_type');
            $table->text('escalation_reason')->nullable()->after('adjustment_reason');
            $table->text('recommendation')->nullable()->after('escalation_reason')
                ->comment('Supervisor recommendation for admin when passing to admin');
        });
    }

    public function down(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            $table->dropColumn([
                'supervisor_action_type',
                'adjustment_reason',
                'escalation_reason',
                'recommendation',
            ]);
        });
    }
};
