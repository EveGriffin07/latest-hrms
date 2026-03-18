<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrainingProgram;
use App\Models\TrainingEnrollment;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TrainingController extends Controller
{
    // 1. Index (Main Page)
    public function index()
    {
        // Auto-update status
        TrainingProgram::where('end_date', '<', Carbon::today())
            ->where('tr_status', '!=', 'completed')
            ->update(['tr_status' => 'completed']);

        TrainingProgram::whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->where('tr_status', '!=', 'active')
            ->update(['tr_status' => 'active']);

        $programs = TrainingProgram::with('department')->orderBy('start_date', 'desc')->get();
        $departments = Department::all();

        $total = $programs->count();
        $ongoing = $programs->where('tr_status', 'active')->count();
        $completed = $programs->where('tr_status', 'completed')->count();
        $upcoming = $programs->where('tr_status', 'planned')->count();

        return view('admin.training_admin', compact('programs', 'departments', 'total', 'ongoing', 'completed', 'upcoming'));
    }

    // 2. Store (Create New Training)
    public function store(Request $request)
    {
        // STRICT VALIDATION
        $request->validate([
            'trainingTitle'   => 'required|string|max:255',
            // Regex enforces alphabets, spaces, dots, commas, and hyphens only
            'trainerName'     => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.\,\-]+$/'],
            'trainerCompany'  => 'nullable|string|max:255',
            'trainerEmail'    => 'nullable|email|max:255',
            'department'      => 'nullable|string', 
            // after_or_equal:today ensures you cannot schedule a NEW training in the past
            'startDate'       => 'required|date|after_or_equal:today',
            'startTime'       => 'required', 
            'endDate'         => 'required|date|after_or_equal:startDate', 
            'mode'            => 'required|in:Onsite,Online',
            'maxParticipants' => 'nullable|integer|min:1|required_if:mode,Onsite', 
            'location'        => 'required|string|max:255',
            'description'     => 'nullable|string',
        ], [
            'trainerName.regex' => 'The Trainer Name can only contain letters, spaces, dots, and hyphens.',
            'startDate.after_or_equal' => 'The Start Date must be today or a future date.',
        ]);

        $deptId = null;
        if ($request->department) {
            $dept = Department::where('department_name', $request->department)->first();
            $deptId = $dept ? $dept->department_id : null;
        }

        $today = Carbon::today();
        $start = Carbon::parse($request->startDate);
        $end   = Carbon::parse($request->endDate);

        $status = 'planned';
        if ($today->between($start, $end)) {
            $status = 'active';
        } elseif ($today->gt($end)) {
            $status = 'completed';
        }

        TrainingProgram::create([
            'training_name'    => $request->trainingTitle,
            'provider'         => $request->trainerName,
            'trainer_company'  => $request->trainerCompany,
            'trainer_email'    => $request->trainerEmail, 
            'department_id'    => $deptId,
            'start_date'       => $request->startDate,
            'start_time'       => $request->startTime, 
            'end_date'         => $request->endDate,
            'mode'             => $request->mode,
            'max_participants' => $request->mode == 'Online' ? null : $request->maxParticipants, 
            'location'         => $request->location,
            'tr_description'   => $request->description,
            'tr_status'        => $status,
            'qr_token'         => Str::random(40), 
        ]);

        return redirect()->route('admin.training')->with('success', 'Training program created successfully!');
    }

    // 3. Update (Edit Existing Training)
    public function update(Request $request, $id)
    {
        $program = TrainingProgram::findOrFail($id);

        // STRICT VALIDATION
        $request->validate([
            'trainingTitle'   => 'required|string|max:255',
            'trainerName'     => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.\,\-]+$/'],
            'trainerCompany'  => 'nullable|string|max:255', 
            'trainerEmail'    => 'nullable|email|max:255',  
            // Notice: NO after_or_equal:today here, so Admin can still edit historical records!
            'startDate'       => 'required|date',
            'startTime'       => 'required', 
            'endDate'         => 'required|date|after_or_equal:startDate',
            'mode'            => 'required|in:Onsite,Online',
            'maxParticipants' => 'nullable|integer|min:1|required_if:mode,Onsite', 
            'location'        => 'required|string|max:255',
        ], [
            'trainerName.regex' => 'The Trainer Name can only contain letters, spaces, dots, and hyphens.',
        ]);

        $deptId = null;
        if ($request->department) {
            $dept = Department::where('department_name', $request->department)->first();
            $deptId = $dept ? $dept->department_id : null;
        }

        $today = Carbon::today();
        $start = Carbon::parse($request->startDate);
        $end   = Carbon::parse($request->endDate);
        
        $status = 'planned';
        if ($today->between($start, $end)) $status = 'active';
        elseif ($today->gt($end)) $status = 'completed';

        $program->update([
            'training_name'    => $request->trainingTitle,
            'provider'         => $request->trainerName,
            'trainer_company'  => $request->trainerCompany,
            'trainer_email'    => $request->trainerEmail,  
            'department_id'    => $deptId,
            'start_date'       => $request->startDate,
            'start_time'       => $request->startTime, 
            'end_date'         => $request->endDate,
            'mode'             => $request->mode,
            'max_participants' => $request->mode == 'Online' ? null : $request->maxParticipants, 
            'location'         => $request->location,
            'tr_description'   => $request->description,
            'tr_status'        => $status,
        ]);

        return redirect()->back()->with('success', 'Training program updated successfully!');
    }

    // 4. Delete
    public function destroy($id)
    {
        $program = TrainingProgram::findOrFail($id);
        $program->enrollments()->delete();
        $program->delete();

        return redirect()->route('admin.training')->with('success', 'Training program deleted successfully.');
    }

    // 5. Show Details
    public function show($id)
    {
        $program = TrainingProgram::with(['enrollments.employee.user', 'department'])
            ->findOrFail($id);

        $enrolledIds = $program->enrollments->pluck('employee_id')->toArray();
        
        $potentialTrainees = Employee::with(['user', 'department'])
            ->whereNotIn('employee_id', $enrolledIds)
            ->where('employee_status', 'active')
            ->get();

        $departments = Department::all();

        return view('admin.training_show', compact('program', 'potentialTrainees', 'departments'));
    }

    // 6. Store Enrollment (BULK)
    public function storeEnrollment(Request $request, $id)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,employee_id',
        ]);

        // Capacity check
        $program = TrainingProgram::findOrFail($id);
        $currentEnrollmentCount = $program->enrollments()->count();
        $attemptedEnrollmentCount = count($request->employee_ids);

        if ($program->mode == 'Onsite' && $program->max_participants) {
            if (($currentEnrollmentCount + $attemptedEnrollmentCount) > $program->max_participants) {
                $availableSlots = $program->max_participants - $currentEnrollmentCount;
                return redirect()->back()->with('error', "Cannot enroll. Only $availableSlots slot(s) remaining for this onsite training.");
            }
        }

        $count = 0;
        foreach ($request->employee_ids as $empId) {
            $exists = TrainingEnrollment::where('training_id', $id)
                        ->where('employee_id', $empId)->exists();
            
            if (!$exists) {
                TrainingEnrollment::create([
                    'training_id'       => $id,
                    'employee_id'       => $empId,
                    'enrollment_date'   => now(),
                    'completion_status' => 'enrolled',
                ]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "$count employees enrolled successfully!");
    }

    // 7. Update Status
    public function updateEnrollmentStatus(Request $request, $id)
    {
        $enrollment = TrainingEnrollment::findOrFail($id);
        $enrollment->update([
            'completion_status' => $request->completion_status,
            'remarks'           => $request->remarks
        ]);
        return redirect()->back()->with('success', 'Participant status updated.');
    }

    // API for Calendar
    public function getEvents()
    {
        $programs = TrainingProgram::all();
        $events = [];
        foreach ($programs as $prog) {
            $events[] = [
                'title' => $prog->training_name,
                'start' => $prog->start_date,
                'end'   => Carbon::parse($prog->end_date)->addDay()->format('Y-m-d'),
                'url'   => route('admin.training.show', $prog->training_id),
                'backgroundColor' => $prog->tr_status == 'completed' ? '#10b981' : ($prog->tr_status == 'active' ? '#3b82f6' : '#f97316'),
            ];
        }
        return response()->json($events);
    }
}