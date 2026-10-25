<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAudit;
use Illuminate\Http\Request;

class AttendanceAuditController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['date' => 'nullable|date_format:Y-m-d', 'session_id' => 'nullable|integer']);
        $audits = AttendanceAudit::with(['actor', 'student.user', 'session.schedule.subject'])
            ->whereHas('session', fn ($q) => $q->visibleTo($request->user()))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', $date))
            ->when($filters['session_id'] ?? null, fn ($q, $id) => $q->where('attendance_session_id', $id))
            ->orderByDesc('id')->paginate(50)->withQueryString();

        return view('attendance-audits.index', compact('audits', 'filters'));
    }
}
