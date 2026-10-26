<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\User;

class AttendanceSessionPolicy
{
    public function view(User $user, AttendanceSession $session): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('guru') && (int) $session->teacher_id === (int) $user->id);
    }

    public function manage(User $user, AttendanceSession $session): bool
    {
        return $this->view($user, $session);
    }

    public function record(User $user, AttendanceSession $session): bool
    {
        // Recording after the grace period stays possible but requires a reason (see AttendanceSession::isLate).
        return $this->view($user, $session) && $session->status === 'open' && $session->isCurrentTerm() && $session->hasStarted();
    }

    public function correct(User $user, AttendanceSession $session): bool
    {
        return $this->view($user, $session) && in_array($session->status, ['open', 'closed'], true) && $session->lesson_date->lte(today());
    }
}
