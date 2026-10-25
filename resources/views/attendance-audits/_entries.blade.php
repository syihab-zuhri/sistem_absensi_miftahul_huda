@php
    $actions = ['session_created' => 'Sesi dibuat', 'session_opened' => 'Sesi dibuka', 'session_closed' => 'Sesi ditutup', 'session_cancelled' => 'Sesi dibatalkan', 'session_rescheduled' => 'Waktu sesi diubah', 'attendance_recorded' => 'Presensi dicatat', 'attendance_corrected' => 'Presensi dikoreksi', 'leave_submitted' => 'Izin/sakit diajukan', 'leave_approved' => 'Izin/sakit disetujui', 'leave_rejected' => 'Izin/sakit ditolak', 'leave_cancelled' => 'Pengajuan dibatalkan'];
    $statusLabels = ['planned' => 'Terencana', 'open' => 'Terbuka', 'closed' => 'Ditutup', 'cancelled' => 'Dibatalkan', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
@endphp
<ol class="divide-y divide-gray-200">
@forelse($audits as $audit)
    <li class="space-y-2 py-4 first:pt-0">
        <div class="flex flex-wrap items-baseline justify-between gap-2"><p class="font-semibold text-gray-900">{{ $actions[$audit->action] ?? $audit->action }}</p><time class="text-sm text-gray-700" datetime="{{ $audit->created_at->toIso8601String() }}">{{ $audit->created_at->format('d-m-Y H:i:s') }}</time></div>
        <p class="text-sm text-gray-700">Oleh {{ $audit->actor->name }}@if($audit->student) · {{ $audit->student->user->name }} ({{ $audit->student->nisn }})@endif</p>
        @if($audit->old_status || $audit->new_status)<p class="text-sm font-semibold text-blue-800">{{ $statusLabels[$audit->old_status] ?? ucfirst($audit->old_status ?? 'Belum tercatat') }} → {{ $statusLabels[$audit->new_status] ?? ucfirst($audit->new_status ?? 'Belum tercatat') }}</p>@endif
        @if($audit->reason)<p class="whitespace-pre-wrap break-words text-sm text-gray-800">{{ $audit->reason }}</p>@endif
        @if($audit->action === 'session_rescheduled')
            <p class="text-sm text-gray-700">Sebelumnya: {{ $audit->before_values['lesson_date'] }} {{ substr($audit->before_values['start_time'], 0, 5) }}–{{ substr($audit->before_values['end_time'], 0, 5) }}.<br>Sesudahnya: {{ $audit->after_values['lesson_date'] }} {{ substr($audit->after_values['start_time'], 0, 5) }}–{{ substr($audit->after_values['end_time'], 0, 5) }}.</p>
        @endif
        @if($showSessionLink ?? false)<a class="inline-flex min-h-11 items-center text-sm font-semibold text-blue-700 underline" href="{{ route('attendance-sessions.show', $audit->session) }}">{{ $audit->session->schedule->subject->name }} · {{ $audit->session->class_id }} · {{ $audit->session->lesson_date->format('d-m-Y') }}</a>@endif
    </li>
@empty
    <li class="py-5 text-gray-700">Belum ada perubahan tercatat. Riwayat sebelum fitur ini diaktifkan tidak memiliki identitas pencatat.</li>
@endforelse
</ol>
<div class="mt-4">{{ $audits->links() }}</div>
