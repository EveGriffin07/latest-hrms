<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rules = [
            'Annual Leave'        => ['proof_requirement' => 'none',  'proof_label' => null],
            'Sick Leave'          => ['proof_requirement' => 'required', 'proof_label' => 'Medical certificate / proof'],
            'Emergency Leave'     => ['proof_requirement' => 'optional', 'proof_label' => 'Supporting document (optional)'],
            'Compassionate Leave' => ['proof_requirement' => 'optional', 'proof_label' => 'Supporting document (optional)'],
            'Study Leave'         => ['proof_requirement' => 'required', 'proof_label' => 'Course / exam proof'],
            'Maternity Leave'     => ['proof_requirement' => 'required', 'proof_label' => 'Medical / birth proof'],
            'Paternity Leave'     => ['proof_requirement' => 'required', 'proof_label' => 'Birth proof'],
        ];

        foreach ($rules as $name => $attrs) {
            DB::table('leave_types')->where('leave_name', $name)->update($attrs);
        }
    }

    public function down(): void
    {
        DB::table('leave_types')->update([
            'proof_requirement' => 'none',
            'proof_label' => null,
        ]);
    }
};
