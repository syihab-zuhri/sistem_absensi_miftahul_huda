@extends('layouts.dashboard')
@section('title', 'Detail Sesi Presensi')
@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <a class="inline-flex min-h-11 items-center font-semibold text-blue-700 underline" href="{{ route('attendance-sessions.index') }}">Kembali ke daftar sesi</a>
    <section class="workflow-card space-y-4" aria-labelledby="session-title">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><h2 id="session-title" class="text-2xl font-bold text-gray-900">{{ $session->schedule->subject->name }} · {{ $session->class_id }}</h2><p class="mt-2 text-gray-700">{{ $session->lesson_date->locale('id')->translatedFormat('l, d F Y') }} · {{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}</p><p class="mt-1 text-gray-700">Pengajar: {{ $session->teacher->name }} · {{ $session->schedule->academicYear->name }}</p></div>
            <span class="rounded-full bg-blue-50 px-4 py-2 font-semibold text-blue-800">{{ ['planned' => 'Terencana', 'open' => 'Terbuka', 'closed' => 'Ditutup', 'cancelled' => 'Dibatalkan'][$session->status] }}</span>
        </div>
        @foreach(['session', 'attendances', 'student_id', 'expected_status', 'reason'] as $field)
            @error($field)<p class="workflow-error" role="alert">{{ $message }}</p>@enderror
        @endforeach
        @if($session->cancellation_reason)<p class="rounded-lg bg-red-50 p-4 text-red-800">{{ $session->cancellation_reason }}</p>@endif
        <div class="flex flex-wrap gap-3">
            @if($session->status === 'planned')<form method="POST" action="{{ route('attendance-sessions.open', $session) }}">@csrf<button class="workflow-button" type="submit">Buka sesi dan simpan peserta</button></form>@endif
            @can('record', $session)<a class="workflow-button" href="{{ route('attendance-sessions.scanner', $session) }}">Buka scanner / presensi manual</a>@endcan
            @if($session->lesson_date->lte(today()))<a class="workflow-secondary" target="_blank" rel="noopener" href="{{ route('reports.session', ['schedule_id' => $session->schedule_id, 'date' => $session->lesson_date->toDateString()]) }}">Laporan PDF pertemuan</a>@endif
            <a class="workflow-secondary" href="{{ route('leave-requests.index') }}">Pengajuan izin/sakit ({{ $pendingCount }} menunggu)</a>
        </div>
        @if($session->status === 'planned')<p class="text-sm text-gray-700">Sesi dapat dibuka pada hari pertemuan sebelum jam selesai. Pencatatan dimulai sesuai jam pertemuan.</p>@endif
        @if($session->status === 'open')
        <form method="POST" action="{{ route('attendance-sessions.close', $session) }}" class="space-y-4 border-t border-gray-200 pt-4">
            @csrf
            <p class="text-sm text-gray-700">Pastikan semua peserta sudah tercatat sebelum menutup. Koreksi setelah penutupan wajib menyertakan alasan.</p>
            <label class="flex min-h-11 items-start gap-3 text-sm text-gray-800"><input class="mt-1 rounded border-gray-500 text-blue-700 focus:ring-blue-600" type="checkbox" name="mark_unrecorded_absent" value="1"><span>Isi alpa untuk peserta yang belum tercatat. Pengajuan izin/sakit yang masih menunggu harus diputuskan terlebih dahulu.</span></label>
            <button type="submit" class="workflow-secondary">Tutup sesi pertemuan</button>
        </form>
        @endif
    </section>
    @if($session->status === 'planned')
    <details class="workflow-card">
        <summary class="min-h-11 cursor-pointer text-lg font-bold text-gray-900">Ubah waktu pertemuan</summary>
        <form method="POST" action="{{ route('attendance-sessions.update', $session) }}" class="mt-4 space-y-4">
            @csrf @method('PATCH')
            <p class="text-sm text-gray-700">Perubahan berlaku untuk pertemuan ini. Sesi dengan pengajuan aktif perlu dibatalkan terlebih dahulu jika jadwalnya berubah.</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label for="new-date" class="workflow-label">Tanggal pertemuan</label><input id="new-date" type="date" name="lesson_date" required value="{{ old('lesson_date', $session->lesson_date->toDateString()) }}" class="workflow-input">@error('lesson_date')<p class="workflow-error">{{ $message }}</p>@enderror</div>
                <div><label for="new-start" class="workflow-label">Jam mulai</label><input id="new-start" type="time" name="start_time" required value="{{ old('start_time', substr($session->start_time, 0, 5)) }}" class="workflow-input">@error('start_time')<p class="workflow-error">{{ $message }}</p>@enderror</div>
                <div><label for="new-end" class="workflow-label">Jam selesai</label><input id="new-end" type="time" name="end_time" required value="{{ old('end_time', substr($session->end_time, 0, 5)) }}" class="workflow-input">@error('end_time')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            </div>
            @if($teachers->isNotEmpty())<div><label for="new-teacher" class="workflow-label">Pengajar pertemuan</label><select id="new-teacher" name="teacher_id" class="workflow-input">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id', $session->teacher_id) == $teacher->id)>{{ $teacher->name }}</option>@endforeach</select>@error('teacher_id')<p class="workflow-error">{{ $message }}</p>@enderror</div>@endif
            <div><label for="reschedule-reason" class="workflow-label">Alasan perubahan (minimal 10 karakter)</label><textarea id="reschedule-reason" name="reason" minlength="10" maxlength="1000" required rows="2" class="workflow-input">{{ old('reason') }}</textarea>@error('reason')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            <button class="workflow-button" type="submit">Simpan perubahan pertemuan</button>
        </form>
    </details>
    @endif
    @if(in_array($session->status, ['planned', 'open']) && $attendances->isEmpty())
    <details class="workflow-card">
        <summary class="min-h-11 cursor-pointer font-semibold text-red-800">Batalkan pertemuan</summary>
        <form method="POST" action="{{ route('attendance-sessions.cancel', $session) }}" class="mt-4 space-y-4">@csrf
            <p class="text-sm text-gray-700">Gunakan untuk hari libur atau pertemuan yang tidak berlangsung. Pengajuan aktif untuk sesi ini juga dibatalkan dan riwayatnya tetap tersimpan.</p>
            <div><label for="cancel-reason" class="workflow-label">Alasan pembatalan (minimal 10 karakter)</label><textarea id="cancel-reason" name="reason" rows="2" minlength="10" maxlength="1000" required class="workflow-input">{{ old('reason') }}</textarea>@error('reason')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            <button type="submit" class="workflow-danger">Batalkan sesi dan pengajuan terkait</button>
        </form>
    </details>
    @endif
    <section class="workflow-card" aria-labelledby="participants-title">
        <h3 id="participants-title" class="text-lg font-bold text-gray-900">Peserta pertemuan ({{ $students->count() }})</h3>
        <p class="mb-4 mt-2 text-sm text-gray-700">{{ $session->opened_at ? 'Daftar peserta disimpan saat sesi dibuka.' : 'Daftar ini mengikuti siswa aktif di kelas sampai sesi dibuka.' }}</p>
        <ul class="divide-y divide-gray-200">
            @forelse($students as $student)
            @php($attendance = $attendances->get($student->id))
            <li class="py-4">
                <div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0"><p class="break-words font-semibold text-gray-900">{{ $student->user->name }}</p><p class="text-sm text-gray-700">NISN {{ $student->nisn }}</p></div><span class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-800">{{ $attendance ? ucfirst($attendance->status) : 'Belum tercatat' }}</span></div>
                @if($attendance)
                @can('correct', $session)
                <details class="mt-3 rounded-lg border border-gray-200 p-3"><summary class="min-h-11 cursor-pointer text-sm font-semibold text-blue-700">Koreksi presensi {{ $student->user->name }}</summary>
                    <form method="POST" action="{{ route('attendance-sessions.correct', $session) }}" class="mt-3 grid items-end gap-4 md:grid-cols-3">@csrf
                        <input type="hidden" name="student_id" value="{{ $student->id }}"><input type="hidden" name="expected_status" value="{{ $attendance->status }}">
                        <div><label for="status-{{ $student->id }}" class="workflow-label">Status baru</label><select id="status-{{ $student->id }}" name="status" class="workflow-input" required>@foreach(\App\Models\Attendance::STATUSES as $status)<option value="{{ $status }}" @selected((old('student_id') == $student->id ? old('status', $attendance->status) : $attendance->status) === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                        <div><label for="reason-{{ $student->id }}" class="workflow-label">Alasan koreksi (minimal 10 karakter)</label><textarea id="reason-{{ $student->id }}" name="reason" minlength="10" maxlength="1000" required rows="2" class="workflow-input">{{ old('student_id') == $student->id ? old('reason') : '' }}</textarea></div>
                        <button class="workflow-button" type="submit">Simpan koreksi</button>
                    </form>
                </details>
                @endcan
                @endif
            </li>
            @empty<li class="py-5 text-gray-700">Belum ada peserta untuk pertemuan ini.</li>@endforelse
        </ul>
    </section>
    <section class="workflow-card" aria-labelledby="session-history-title"><h3 id="session-history-title" class="mb-5 text-lg font-bold text-gray-900">Riwayat Perubahan</h3>@include('attendance-audits._entries')</section>
</div>
@endsection
