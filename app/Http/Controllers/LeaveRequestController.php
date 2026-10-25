<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\LeaveRequest;
use App\Services\LeaveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->hasRole('siswa') ? $request->user()->student : null;
        $reviewer = $request->user()->hasAnyRole(['admin', 'guru']);
        abort_unless($reviewer || $student, 403);
        $filters = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'cancelled'])]]);
        $requests = LeaveRequest::with(['student.user', 'session.schedule.subject', 'session.teacher', 'reviewer'])
            ->when($reviewer, fn ($q) => $q->whereHas('session', fn ($s) => $s->visibleTo($request->user())), fn ($q) => $q->where('student_id', $student->id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->orderByDesc('id')->paginate(25)->withQueryString();
        $sessions = $student && $student->status === 'aktif' ? AttendanceSession::with(['schedule.subject', 'schedule.academicYear'])
            ->where('status', '!=', 'cancelled')->whereBetween('lesson_date', [today()->subMonth()->toDateString(), today()->addMonths(3)->toDateString()])
            ->whereDoesntHave('leaveRequests', fn ($q) => $q->where('student_id', $student->id)->whereIn('status', ['pending', 'approved']))
            ->where(fn ($q) => $q->where(fn ($planned) => $planned->whereNull('opened_at')->where('class_id', $student->class_id))
                ->orWhereHas('students', fn ($s) => $s->where('students.id', $student->id)))
            ->orderBy('lesson_date')->orderBy('start_time')->get()->filter(fn ($s) => $s->isCurrentTerm()) : collect();

        return view('leave-requests.index', compact('requests', 'sessions', 'student', 'reviewer', 'filters'));
    }

    public function store(Request $request, LeaveRequests $workflow)
    {
        abort_unless($request->user()->hasRole('siswa') && ($student = $request->user()->student), 403);
        $data = $request->validate(['attendance_session_id' => 'required|integer|exists:attendance_sessions,id', 'type' => ['required', Rule::in(['izin', 'sakit'])],
            'reason' => 'required|string|min:10|max:2000', 'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $path = $request->file('attachment')?->store('leave-attachments', 'local');
        if ($request->hasFile('attachment') && ! $path) {
            throw ValidationException::withMessages(['attachment' => 'Lampiran gagal disimpan. Silakan kirim ulang.']);
        }
        try {
            $workflow->submit(AttendanceSession::findOrFail($data['attendance_session_id']), $student, $request->user(), $data + ['attachment_path' => $path]);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return redirect()->route('leave-requests.index')->with('success', 'Pengajuan dikirim dan menunggu keputusan pengajar.');
    }

    public function review(Request $request, LeaveRequest $leaveRequest, LeaveRequests $workflow)
    {
        Gate::authorize('review', $leaveRequest);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'review_note' => 'required|string|min:10|max:1000']);
        $workflow->review($leaveRequest, $request->user(), $data['decision'], $data['review_note']);

        return back()->with('success', 'Keputusan pengajuan sudah dicatat.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest, LeaveRequests $workflow)
    {
        $workflow->cancel($leaveRequest, $request->user());

        return back()->with('success', 'Pengajuan dibatalkan.');
    }

    public function attachment(LeaveRequest $leaveRequest)
    {
        Gate::authorize('view', $leaveRequest);
        abort_unless($leaveRequest->attachment_path && Storage::disk('local')->exists($leaveRequest->attachment_path), 404);

        return Storage::disk('local')->download($leaveRequest->attachment_path, 'lampiran-izin-'.$leaveRequest->id.'.'.pathinfo($leaveRequest->attachment_path, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
