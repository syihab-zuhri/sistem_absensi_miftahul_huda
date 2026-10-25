@extends('layouts.dashboard')
@section('title', 'Sesi Presensi')
@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Sesi Presensi</h2>
        <p class="mt-2 text-gray-700">Kelola pertemuan berdasarkan tanggal. Buka sesi untuk menyimpan peserta dan mulai mencatat kehadiran.</p>
    </div>
    <section class="workflow-card" aria-labelledby="create-session-title">
        <h3 id="create-session-title" class="mb-4 text-lg font-bold text-gray-900">Buat pertemuan</h3>
        @if($schedules->isEmpty())
            <p class="text-gray-700">Belum ada jadwal Anda pada semester aktif. Hubungi admin untuk menyiapkan jadwal.</p>
        @else
        <form method="POST" action="{{ route('attendance-sessions.store') }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="schedule_id" class="workflow-label">Jadwal pelajaran</label>
                    <select id="schedule_id" name="schedule_id" required class="workflow-input">
                        <option value="">Pilih jadwal</option>
                        @foreach($schedules as $schedule)
                        <option value="{{ $schedule->id }}" @selected(old('schedule_id') == $schedule->id)>{{ $schedule->subject->name }} · {{ $schedule->class_id }} · {{ $schedule->day }}, {{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</option>
                        @endforeach
                    </select>
                    @error('schedule_id')<p class="workflow-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lesson_date" class="workflow-label">Tanggal pertemuan</label>
                    <input id="lesson_date" type="date" name="lesson_date" value="{{ old('lesson_date', today()->toDateString()) }}" required class="workflow-input">
                    @error('lesson_date')<p class="workflow-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <details class="rounded-lg border border-gray-200 p-4">
                <summary class="min-h-11 cursor-pointer font-semibold text-gray-800">Ubah jam atau pengajar pertemuan</summary>
                <p class="mb-4 text-sm text-gray-700">Biarkan jam kosong untuk mengikuti jadwal mingguan. Isi kedua jam bila ingin mengubahnya.</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div><label for="start_time" class="workflow-label">Jam mulai (opsional)</label><input id="start_time" type="time" name="start_time" value="{{ old('start_time') }}" class="workflow-input">@error('start_time')<p class="workflow-error">{{ $message }}</p>@enderror</div>
                    <div><label for="end_time" class="workflow-label">Jam selesai (opsional)</label><input id="end_time" type="time" name="end_time" value="{{ old('end_time') }}" class="workflow-input">@error('end_time')<p class="workflow-error">{{ $message }}</p>@enderror</div>
                    @if($teachers->isNotEmpty())
                    <div><label for="teacher_id" class="workflow-label">Pengajar (opsional)</label><select id="teacher_id" name="teacher_id" class="workflow-input"><option value="">Ikuti pengajar jadwal</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>@endforeach</select>@error('teacher_id')<p class="workflow-error">{{ $message }}</p>@enderror</div>
                    @endif
                </div>
            </details>
            <button class="workflow-button" type="submit">Buat sesi pertemuan</button>
        </form>
        @endif
    </section>
    <section class="workflow-card" aria-labelledby="sessions-title">
        <h3 id="sessions-title" class="mb-4 text-lg font-bold text-gray-900">Daftar pertemuan</h3>
        <form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
            <div><label for="filter-start" class="workflow-label">Dari tanggal</label><input id="filter-start" type="date" name="start_date" value="{{ $startDate }}" class="workflow-input">@error('start_date')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            <div><label for="filter-end" class="workflow-label">Sampai tanggal</label><input id="filter-end" type="date" name="end_date" value="{{ $endDate }}" class="workflow-input">@error('end_date')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            <button type="submit" class="workflow-secondary">Tampilkan</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 text-gray-700"><tr><th scope="col" class="p-3">Pertemuan</th><th scope="col" class="p-3">Pelajaran & pengajar</th><th scope="col" class="p-3">Status</th><th scope="col" class="p-3">Presensi</th><th scope="col" class="p-3">Tindakan</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sessions as $session)
                    <tr>
                        <td class="p-3"><div class="whitespace-nowrap font-semibold text-gray-900">{{ $session->lesson_date->locale('id')->translatedFormat('d M Y') }}</div><div class="whitespace-nowrap text-gray-700">{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}</div></td>
                        <td class="p-3"><div class="font-semibold text-gray-900">{{ $session->schedule->subject->name }} · {{ $session->class_id }}</div><div class="text-gray-700">{{ $session->teacher->name }}</div></td>
                        <td class="p-3"><span class="rounded-full bg-blue-50 px-3 py-1 font-semibold text-blue-800">{{ ['planned' => 'Terencana', 'open' => 'Terbuka', 'closed' => 'Ditutup', 'cancelled' => 'Dibatalkan'][$session->status] }}</span></td>
                        <td class="p-3 text-gray-800">{{ $session->attendances_count }} siswa</td>
                        <td class="p-3"><a class="workflow-secondary whitespace-nowrap" href="{{ route('attendance-sessions.show', $session) }}">Kelola sesi<span class="sr-only"> {{ $session->schedule->subject->name }}, {{ $session->lesson_date->format('d-m-Y') }}</span></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="p-8 text-center text-gray-700">Belum ada pertemuan pada rentang tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $sessions->links() }}</div>
    </section>
</div>
@endsection
