<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Model stores face data in embedding_encrypted and sets embedding to null; column must be nullable.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE employee_faces MODIFY embedding JSON NULL');
        }
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE employee_faces ALTER COLUMN embedding DROP NOT NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE employee_faces MODIFY embedding JSON NOT NULL');
        }
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE employee_faces ALTER COLUMN embedding SET NOT NULL');
        }
    }
};
