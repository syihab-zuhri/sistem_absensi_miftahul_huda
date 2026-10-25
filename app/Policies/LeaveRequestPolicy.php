<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class LeaveRequestPolicy
{
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return (int) $user->student?->id === (int) $leaveRequest->student_id || Gate::forUser($user)->allows('view', $leaveRequest->session);
    }

    public function review(User $user, LeaveRequest $leaveRequest): bool
    {
        return Gate::forUser($user)->allows('manage', $leaveRequest->session);
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole('siswa') && (int) $user->student?->id === (int) $leaveRequest->student_id;
    }
}
