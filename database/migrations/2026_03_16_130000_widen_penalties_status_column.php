<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('penalties') || ! Schema::hasColumn('penalties', 'status')) {
            return;
        }

        $driver = config('database.default');
        $connection = config("database.connections.$driver.driver");

        // For MySQL, convert enum to a more flexible VARCHAR so we can store richer states.
        if ($connection === 'mysql') {
            DB::statement("ALTER TABLE penalties MODIFY status VARCHAR(32) NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('penalties', function (Blueprint $table) {
                $table->string('status', 32)->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        // Best-effort: for MySQL, revert to the original enum definition.
        if (! Schema::hasTable('penalties') || ! Schema::hasColumn('penalties', 'status')) {
            return;
        }

        $driver = config('database.default');
        $connection = config("database.connections.$driver.driver");

        if ($connection === 'mysql') {
            DB::statement("ALTER TABLE penalties MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('penalties', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
            });
        }
    }
};

