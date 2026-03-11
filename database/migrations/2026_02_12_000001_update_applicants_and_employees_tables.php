<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Applicants table updates (add-only, idempotent)
        if (Schema::hasTable('applicant_profiles')) {
            Schema::table('applicant_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('applicant_profiles', 'first_name')) {
                    $table->string('first_name')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('applicant_profiles', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
                if (!Schema::hasColumn('applicant_profiles', 'photo_path')) {
                    $table->string('photo_path')->nullable()->after('avatar_path');
                }
                if (!Schema::hasColumn('applicant_profiles', 'status')) {
                    $table->enum('status', ['pending', 'approved', 'denied', 'converted'])
                        ->default('pending')
                        ->after('resume_path');
                }
            });

            // Best-effort index on status without Doctrine
            try {
                Schema::table('applicant_profiles', function (Blueprint $table) {
                    $table->index('status', 'applicant_profiles_status_index');
                });
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        }

        // Employees table updates (add-only, idempotent)
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'first_name')) {
                    $table->string('first_name')->nullable()->after('position_id');
                }
                if (!Schema::hasColumn('employees', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
                if (!Schema::hasColumn('employees', 'photo_path')) {
                    $table->string('photo_path')->nullable()->after('last_name');
                }
            });

            // Best-effort unique on employee_code without Doctrine
            try {
                Schema::table('employees', function (Blueprint $table) {
                    $table->unique('employee_code', 'employees_employee_code_unique');
                });
            } catch (\Throwable $e) {
                // ignore if exists
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applicant_profiles')) {
            Schema::table('applicant_profiles', function (Blueprint $table) {
                try { $table->dropIndex('applicant_profiles_status_index'); } catch (\Throwable $e) {}
                if (Schema::hasColumn('applicant_profiles', 'status')) $table->dropColumn('status');
                if (Schema::hasColumn('applicant_profiles', 'photo_path')) $table->dropColumn('photo_path');
                if (Schema::hasColumn('applicant_profiles', 'last_name')) $table->dropColumn('last_name');
                if (Schema::hasColumn('applicant_profiles', 'first_name')) $table->dropColumn('first_name');
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                try { $table->dropUnique('employees_employee_code_unique'); } catch (\Throwable $e) {}
                if (Schema::hasColumn('employees', 'photo_path')) $table->dropColumn('photo_path');
                if (Schema::hasColumn('employees', 'last_name')) $table->dropColumn('last_name');
                if (Schema::hasColumn('employees', 'first_name')) $table->dropColumn('first_name');
            });
        }
    }
};
