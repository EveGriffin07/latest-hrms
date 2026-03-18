<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Department; // <--- Import Department Model
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    // 1. Show List (Consolidated View)
    public function index()
    {
        $announcements = Announcement::latest('publish_at')->get();
        // Fetch departments for the dynamic dropdown in the Modal
        $departments = Department::all(); 

        return view('admin.dashboard_view', compact('announcements', 'departments'));
    }

    // 2. Store (Create)
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'message'  => 'required|string',
            'audience' => 'required|string',
            'priority' => 'required|string',
        ]);

        Announcement::create([
            'title'         => $request->title,
            'content'       => $request->message,
            'audience_type' => $request->audience,
            'priority'      => $request->priority,
            // Store Department Name or ID depending on your DB design. 
            // Assuming storing ID is better, but if your table stores strings:
            'department'    => $request->department, 
            'publish_at'    => now(),
            'expires_at'    => $request->expires,
            'remarks'       => $request->remarks,
            'posted_by'     => Auth::id() ?? 1,
        ]);

        return redirect()->route('admin.announcements.index')
                         ->with('success', 'Announcement posted successfully!');
    }

    // 3. Update (Edit)
    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title'    => 'required|string|max:255',
            'message'  => 'required|string',
            'priority' => 'required|string',
        ]);

        $announcement->update([
            'title'         => $request->title,
            'content'       => $request->message,
            'audience_type' => $request->audience,
            'priority'      => $request->priority,
            'department'    => $request->department,
            'expires_at'    => $request->expires,
            'remarks'       => $request->remarks,
        ]);

        return redirect()->route('admin.announcements.index')
                         ->with('success', 'Announcement updated successfully!');
    }

    // 4. Delete
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
                         ->with('success', 'Announcement deleted successfully!');
    }

}