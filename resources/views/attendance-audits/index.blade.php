@extends('layouts.dashboard')
@section('title', 'Riwayat Perubahan')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div><h2 class="text-2xl font-bold text-gray-900">Riwayat Perubahan</h2><p class="mt-2 text-gray-700">Pelaku, alasan, dan perubahan pada sesi yang Anda kelola.</p></div>
    <section class="workflow-card" aria-label="Filter riwayat">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div><label for="audit-date" class="workflow-label">Tanggal perubahan</label><input id="audit-date" type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="workflow-input">@error('date')<p class="workflow-error">{{ $message }}</p>@enderror</div>
            @if(isset($filters['session_id']))<input type="hidden" name="session_id" value="{{ $filters['session_id'] }}">@endif
            <button class="workflow-secondary" type="submit">Tampilkan riwayat</button><a class="workflow-secondary" href="{{ route('attendance-audits.index') }}">Semua tanggal</a>
        </form>
    </section>
    <section class="workflow-card" aria-label="Catatan perubahan">@include('attendance-audits._entries', ['showSessionLink' => true])</section>
</div>
@endsection
