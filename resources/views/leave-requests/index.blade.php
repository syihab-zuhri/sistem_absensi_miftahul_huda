@extends('layouts.dashboard')
@section('title', 'Pengajuan Izin/Sakit')
@section('content')
@php($labels = ['pending' => 'Menunggu keputusan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'])
<div class="mx-auto max-w-5xl space-y-6">
    <div><h2 class="text-2xl font-bold text-gray-900">Pengajuan Izin/Sakit</h2><p class="mt-2 text-gray-700">{{ $reviewer ? 'Periksa pengajuan peserta pada sesi yang Anda kelola.' : 'Ajukan izin atau sakit untuk pertemuan kelas Anda dan pantau keputusannya.' }}</p></div>
    @foreach(['decision', 'leave_request'] as $field)
        @error($field)<p class="workflow-error" role="alert">{{ $message }}</p>@enderror
    @endforeach
    @if($student && !$reviewer)
    <section class="workflow-card" aria-labelledby="submit-leave-title">
        <h3 id="submit-leave-title" class="mb-4 text-lg font-bold text-gray-900">Buat pengajuan</h3>
        @if($sessions->isEmpty())<p class="text-gray-700">Tidak ada pertemuan lain yang tersedia untuk pengajuan. Pengajuan yang sudah dikirim dapat dilihat pada riwayat di bawah. Hubungi guru jika sesi kelas Anda belum dibuat.</p>
        @else
        <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data" class="space-y-4">@csrf
            <div><label for="leave-session" class="workflow-label">Pertemuan</label><select id="leave-session" name="attendance_session_id" required class="workflow-input"><option value="">Pilih pertemuan</option>@foreach($sessions as $session)<option value="{{ $session->id }}" @selected(old('attendance_session_id') == $session->id)>{{ $session->lesson_date->format('d-m-Y') }} · {{ substr($session->start_time, 0, 5) }} · {{ $session->schedule->subject->name }} · {{ $session->class_id }}</option>@endforeach</select>@error('attendance_session_id')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            <div><label for="leave-type" class="workflow-label">Jenis pengajuan</label><select id="leave-type" name="type" class="workflow-input" required><option value="izin" @selected(old('type') === 'izin')>Izin</option><option value="sakit" @selected(old('type') === 'sakit')>Sakit</option></select>@error('type')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            <div><label for="leave-reason" class="workflow-label">Alasan (minimal 10 karakter)</label><textarea id="leave-reason" name="reason" rows="3" minlength="10" maxlength="2000" required class="workflow-input">{{ old('reason') }}</textarea>@error('reason')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            <div><label for="leave-attachment" class="workflow-label">Lampiran (opsional)</label><input id="leave-attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png" class="workflow-input p-2" aria-describedby="attachment-help"><p id="attachment-help" class="mt-2 text-sm text-gray-700">PDF, JPEG, atau PNG, maksimal 5 MB. Lampiran hanya dapat dilihat oleh Anda dan pengajar sesi/admin.</p>@error('attachment')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            <button class="workflow-button" type="submit">Kirim pengajuan</button>
        </form>
        @endif
    </section>
    @endif
    <section class="workflow-card" aria-labelledby="requests-title">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4"><h3 id="requests-title" class="text-lg font-bold text-gray-900">{{ $reviewer ? 'Pengajuan peserta' : 'Riwayat pengajuan Anda' }}</h3>
            <form method="GET" class="flex flex-wrap items-end gap-3"><div><label class="workflow-label" for="request-status">Filter status</label><select class="workflow-input" name="status" id="request-status"><option value="">Semua status</option>@foreach($labels as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div><button type="submit" class="workflow-secondary">Tampilkan</button></form>
        </div>
        <div class="space-y-5">
            @forelse($requests as $leave)
            <article class="space-y-4 rounded-xl border border-gray-200 p-4 sm:p-5" aria-labelledby="leave-title-{{ $leave->id }}">
                <div class="flex flex-wrap justify-between gap-3"><div><h4 id="leave-title-{{ $leave->id }}" class="font-bold text-gray-900">{{ ucfirst($leave->type) }} · {{ $leave->session->schedule->subject->name }}</h4><p class="mt-1 text-sm text-gray-700">{{ $leave->session->lesson_date->format('d-m-Y') }} · {{ substr($leave->session->start_time, 0, 5) }} · {{ $leave->session->class_id }}</p>@if($reviewer)<p class="mt-1 break-words text-sm font-semibold text-gray-800">{{ $leave->student->user->name }} · NISN {{ $leave->student->nisn }}</p>@endif</div><span class="self-start rounded-full bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-800">{{ $labels[$leave->status] }}</span></div>
                <p class="whitespace-pre-wrap break-words text-gray-800">{{ $leave->reason }}</p>
                @if($leave->attachment_path)<a href="{{ route('leave-requests.attachment', $leave) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-blue-700 underline">Unduh lampiran pengajuan #{{ $leave->id }}</a>@endif
                @if($leave->review_note)<div class="rounded-lg bg-gray-50 p-3 text-sm text-gray-800"><p class="mb-1 font-semibold">Catatan {{ $leave->reviewer?->name ?? 'pengajar' }} · {{ $leave->reviewed_at?->format('d-m-Y H:i') }}</p><p class="whitespace-pre-wrap break-words">{{ $leave->review_note }}</p></div>@endif
                @if($leave->status === 'approved' && $leave->session->status === 'planned')<p class="text-sm text-blue-800">Status presensi diterapkan ketika sesi pertemuan dibuka.</p>@endif
                @if($leave->status === 'pending')
                    @if($reviewer)
                    <form method="POST" action="{{ route('leave-requests.review', $leave) }}" class="space-y-3 border-t border-gray-200 pt-4">@csrf
                        <input type="hidden" name="leave_request_id" value="{{ $leave->id }}">
                        <div><label for="review-{{ $leave->id }}" class="workflow-label">Catatan keputusan (minimal 10 karakter)</label><textarea id="review-{{ $leave->id }}" name="review_note" rows="2" minlength="10" maxlength="1000" required class="workflow-input">{{ old('leave_request_id') == $leave->id ? old('review_note') : '' }}</textarea>@error('review_note')<p class="workflow-error">{{ $message }}</p>@enderror</div>
                        <div class="flex flex-wrap gap-3"><button name="decision" value="approved" class="workflow-button" type="submit">Setujui pengajuan</button><button name="decision" value="rejected" class="workflow-secondary" type="submit">Tolak pengajuan</button></div>
                    </form>
                    @else
                    <form method="POST" action="{{ route('leave-requests.cancel', $leave) }}">@csrf<button class="workflow-secondary" type="submit">Batalkan pengajuan #{{ $leave->id }}</button></form>
                    @endif
                @endif
            </article>
            @empty<p class="py-5 text-gray-700">Belum ada pengajuan pada filter ini.</p>@endforelse
        </div>
        <div class="mt-5">{{ $requests->links() }}</div>
    </section>
</div>
@endsection
