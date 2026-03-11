<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\User;
use App\Models\OvertimeClaim;
use Illuminate\Http\Request;

class AdminAreaController extends Controller
{
    public function index()
    {
        $areas = Area::withCount(['users', 'overtimeClaims'])
            ->with('supervisor:user_id,name,email')
            ->orderBy('name')
            ->get();
        return view('admin.areas.index', compact('areas'));
    }

    public function create()
    {
        $supervisors = User::whereIn('role', ['supervisor', 'manager', 'admin', 'administrator', 'hr'])->orderBy('name')->get(['user_id', 'name', 'email', 'role']);
        return view('admin.areas.create', compact('supervisors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'supervisor_id' => ['nullable', 'exists:users,user_id'],
        ]);
        Area::create($validated);
        return redirect()->route('admin.areas.index')->with('success', 'Area created.');
    }

    public function edit(Area $area)
    {
        $area->load('supervisor');
        $supervisors = User::whereIn('role', ['supervisor', 'manager', 'admin', 'administrator', 'hr'])->orderBy('name')->get(['user_id', 'name', 'email', 'role']);
        $employeesInArea = User::where('area_id', $area->id)->whereHas('employee')->with('employee')->orderBy('name')->get();
        $otherAreas = Area::where('id', '!=', $area->id)->orderBy('name')->get();
        $usersNotInArea = User::whereHas('employee')
            ->where(function ($q) use ($area) {
                $q->whereNull('area_id')->orWhere('area_id', '!=', $area->id);
            })
            ->orderBy('name')
            ->get(['user_id', 'name', 'email']);
        return view('admin.areas.edit', compact('area', 'supervisors', 'employeesInArea', 'otherAreas', 'usersNotInArea'));
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'supervisor_id' => ['nullable', 'exists:users,user_id'],
        ]);
        $area->update($validated);
        return redirect()->route('admin.areas.index')->with('success', 'Area updated.');
    }

    public function moveEmployee(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
            'area_id' => ['required', 'exists:areas,id'],
        ]);
        User::where('user_id', $validated['user_id'])->update(['area_id' => $validated['area_id']]);
        return redirect()->back()->with('success', 'Employee moved to new area.');
    }
}
