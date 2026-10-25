# Attendance Workflows Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver dated attendance sessions, accountable corrections, and private student leave requests.

**Architecture:** Add sessions and frozen participant rosters alongside the existing schedule/date attendance key. Transactional services serialize actions on the session row and append audit entries. Blade pages expose the workflow while legacy scanner endpoints remain usable.

**Tech Stack:** Laravel 12, PHP 8.2–8.4, SQLite/MariaDB, Blade, Tailwind 3, Alpine, Vite 7.

**Spec:** `docs/attendance-workflows.md`

## Global Constraints

- Implement the rules in `docs/attendance-workflows.md`.
- Preserve existing uncommitted remediation and the unrelated `vendor.zip`.
- Work on `codex/attendance-workflows`; never push to the existing branch or deploy production automatically.
- Use isolated test databases; do not run destructive test migrations on workspace/live data.
- Execute inline in this session; the referenced superpowers executor packages are not installed in this environment.

---

### Task 1: Dated sessions and immutable participant lists

**Files:**
- Create: `database/migrations/2026_10_25_000003_add_attendance_workflows.php`
- Create: `app/Models/AttendanceSession.php`, `app/Models/AttendanceAudit.php`, `app/Models/LeaveRequest.php`
- Create: `app/Models/AttendanceSessionMove.php` to reserve dates left behind by moved meetings without creating duplicate meetings or losing the ability to move back.
- Create: `app/Policies/AttendanceSessionPolicy.php`
- Create: `app/Services/AttendanceSessions.php`, `app/Services/AttendanceRecorder.php`
- Create: `app/Http/Controllers/AttendanceSessionController.php`
- Modify: attendance/schedule/student model relationships, `AttendanceController`, `SchedulePolicy`, and `routes/web.php`
- Test: `tests/Feature/AttendanceWorkflowTest.php`, `tests/Feature/AttendanceWorkflowMigrationTest.php`

**Interfaces:**
- Produces `AttendanceSessions::create(Schedule $schedule, array $data, User $actor): AttendanceSession` and `open/close/cancel/reschedule` methods operating on session rows inside transactions.
- Produces `AttendanceRecorder::record(AttendanceSession $session, Student $student, string $status, User $actor, ?string $reason, ?string $expectedStatus, bool $checkExpected): Attendance`.
- Produces `/attendance-sessions` list/create and per-session open/close/cancel/reschedule/scanner/roster/attendances routes.

- [x] Write and run failing HTTP tests for session ownership, unique date, frozen roster, record time, cancellation, rescheduling, close completeness, and legacy scanner compatibility.

```php
$this->actingAs($teacher)->post('/attendance-sessions', [
    'schedule_id' => $schedule->id, 'lesson_date' => '2026-10-26',
])->assertRedirect();
$this->assertDatabaseHas('attendance_sessions', ['status' => 'planned']);
```

- [x] Implement the migration, including historical session/roster backfill without changing original attendance timestamps/statuses. Add a rollback preservation test.
- [x] Implement session authorization and services, keeping the existing schedule/student/date uniqueness constraint.
- [x] Integrate legacy scanner reads/writes and add dated scanner endpoints.
- [x] Run `php82 artisan test --filter='AttendanceWorkflow|AuditRegression' --compact`.

### Task 2: Audited corrections and history protection

**Files:**
- Modify: `AttendanceRecorder`, `AttendanceSessionController`, `ReportController`, deletion guards for users/students/schedules
- Create: `app/Http/Controllers/AttendanceAuditController.php`
- Modify: `app/Services/AttendanceReport.php`
- Test: `tests/Feature/AttendanceWorkflowTest.php`

**Interfaces:**
- Consumes the session and recorder interfaces from Task 1.
- Produces `/attendance-sessions/{session}/corrections` with `student_id`, `status`, `expected_status`, `reason`.
- Produces scoped `/attendance-audits` history with session/date filters.

- [x] Write failing tests for required correction reason, stale status rejection, one audit per actual change, substitute teacher report isolation, and protected history deletion.

```php
$this->actingAs($teacher)->postJson('/attendance-sessions/'.$session->id.'/corrections', [
    'student_id' => $student->id, 'status' => 'izin',
    'expected_status' => 'alpa', 'reason' => 'Surat izin telah diperiksa.',
])->assertOk();
$this->assertDatabaseHas('attendance_audits', ['old_status' => 'alpa', 'new_status' => 'izin']);
```

- [x] Implement transactional audit recording and authorization-scoped history/report queries.
- [x] Reject destructive actions against records with session/audit/leave references with validation feedback.
- [x] Run focused tests and the existing full PHP suite.

### Task 3: Student leave requests and decisions

**Files:**
- Create: `app/Services/LeaveRequests.php`, `app/Policies/LeaveRequestPolicy.php`
- Create: `app/Http/Controllers/LeaveRequestController.php`
- Modify: session open/close/cancel services and routes
- Test: `tests/Feature/LeaveRequestWorkflowTest.php`

**Interfaces:**
- Consumes session row locks and `AttendanceRecorder::record`.
- Produces `/leave-requests` list/create, `/{leaveRequest}/review`, `/cancel`, `/attachment`.
- Produces `LeaveRequests::submit`, `review`, and `cancel`, with private attachment storage and audit events.

- [x] Write failing tests for private ownership, validation, duplicate submissions, cancellation/resubmission, review authorization, future application, closed-session correction, and conflict with recorded presence.

```php
$this->actingAs($student->user)->post('/leave-requests', [
    'attendance_session_id' => $session->id, 'type' => 'sakit',
    'reason' => 'Sedang berobat dan perlu istirahat.',
])->assertRedirect();
$this->assertDatabaseHas('leave_requests', ['status' => 'pending']);
```

- [x] Implement private upload/download and transactional decisions; clean up newly stored attachments if submission fails.
- [x] Apply approved requests when sessions open; block incorrect overwrites and unresolved automatic alpa.
- [x] Run `php82 artisan test --filter=LeaveRequestWorkflowTest --compact`.

### Task 4: Usable pages and end-to-end verification

**Files:**
- Create: `resources/views/attendance-sessions/index.blade.php`, `show.blade.php`
- Create: `resources/views/attendance-audits/index.blade.php`, `resources/views/leave-requests/index.blade.php`
- Modify: dashboard navigation, student portal links, scanner Blade/JS and JS DOM tests
- Modify: feature documentation and README links

**Interfaces:**
- Consumes the routes and view data from Tasks 1–3.
- Produces mobile-friendly pages with labeled inputs, inline errors, status text, clear history, and explicit consequential action controls.

- [x] Add failing rendered-page/DOM behavior tests for correction reasons, dirty-row saves, and access-relevant navigation.
- [x] Implement pages using the existing blue/white dashboard and local assets. Preserve old input and show field errors.
- [x] Run complete PHP tests, JS tests, Pint, asset build, and migration upgrade/rollback tests on SQLite and an isolated MariaDB instance.
- [x] Exercise student submission, teacher decision, session open/save/close/correction, audit history, mobile navigation, and attachment access in a named browser session using isolated fixtures.
- [x] Review the diff and document evidence and deployment steps. Commit verified changes on the feature branch; exclude secrets, private uploads, and `vendor.zip`.
