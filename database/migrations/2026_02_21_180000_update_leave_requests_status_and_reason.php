<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'reject_reason')) {
                $table->text('reject_reason')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('leave_requests', 'decision_at')) {
                $table->timestamp('decision_at')->nullable()->after('approved_by');
            }
        });

        // Extend status enum to include cancelled
        $driver = config('database.default');
        $connection = config("database.connections.$driver.driver");
        if ($connection === 'mysql') {
            DB::statement("ALTER TABLE leave_requests MODIFY leave_status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        } elseif ($connection === 'pgsql') {
            // PostgreSQL: drop default enum and recreate
            DB::statement("ALTER TYPE leave_requests_leave_status_check RENAME TO leave_status_old");
            DB::statement("CREATE TYPE leave_status_new AS ENUM ('pending','approved','rejected','cancelled')");
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN leave_status DROP DEFAULT");
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN leave_status TYPE leave_status_new USING leave_status::text::leave_status_new");
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN leave_status SET DEFAULT 'pending'");
            DB::statement("DROP TYPE leave_status_old");
        } else {
            // Fallback: widen to string
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->string('leave_status', 20)->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'reject_reason')) {
                $table->dropColumn('reject_reason');
            }
            if (Schema::hasColumn('leave_requests', 'decision_at')) {
                $table->dropColumn('decision_at');
            }
        });

        // Revert enum
        $driver = config('database.default');
        $connection = config("database.connections.$driver.driver");
        if ($connection === 'mysql') {
            DB::statement("ALTER TABLE leave_requests MODIFY leave_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
