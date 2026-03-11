<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_claims', 'location_type')) {
                $table->string('location_type', 16)->default('INSIDE')->after('supporting_info'); // INSIDE | OUTSIDE
            }
            if (!Schema::hasColumn('overtime_claims', 'proof_image_path')) {
                $table->string('proof_image_path', 512)->nullable()->after('location_type');
            }
            if (!Schema::hasColumn('overtime_claims', 'missing_proof_reason')) {
                $table->text('missing_proof_reason')->nullable()->after('proof_image_path');
            }
            if (!Schema::hasColumn('overtime_claims', 'no_proof_flag')) {
                $table->boolean('no_proof_flag')->default(false)->after('missing_proof_reason');
            }
            if (!Schema::hasColumn('overtime_claims', 'approved_hours')) {
                $table->decimal('approved_hours', 5, 2)->nullable()->after('supervisor_action_at')->comment('Supervisor-approved hours for payout');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtime_claims', function (Blueprint $table) {
            $columns = ['location_type', 'proof_image_path', 'missing_proof_reason', 'no_proof_flag', 'approved_hours'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('overtime_claims', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
