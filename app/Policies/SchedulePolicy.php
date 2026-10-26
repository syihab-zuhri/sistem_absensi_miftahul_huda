<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function view(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('guru') && (int) $schedule->teacher_id === (int) $user->id);
    }

    public function record(User $user, Schedule $schedule): bool
    {
        $session = AttendanceSession::where('schedule_id', $schedule->id)->whereDate('lesson_date', today())->first();
        if ($session) {
            $policy = new AttendanceSessionPolicy;

            return $policy->view($user, $session) && in_array($session->status, ['planned', 'open'], true)
                && $session->isCurrentTerm() && $session->hasStarted();
        }
        if ($schedule->sessionMoves()->whereDate('original_date', today())->exists()) {
            return false;
        }
        $year = $schedule->academicYear;
        $now = now();

        return $this->view($user, $schedule) && $year && $year->is_active
            && $schedule->semester === $year->active_semester
            && $schedule->day === $now->copy()->locale('id')->dayName
            && $schedule->start_time <= $now->format('H:i:s')
            && (! $year->start_date || $year->start_date->startOfDay()->lte($now))
            && (! $year->end_date || $year->end_date->endOfDay()->gte($now));
    }
}
