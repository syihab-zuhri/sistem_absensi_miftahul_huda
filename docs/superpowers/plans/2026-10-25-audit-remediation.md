# Audit Remediation Implementation Plan

**Goal:** Resolve A01–A19 and operational weaknesses from docs/audit-2026-10-25/README.md.
**Architecture:** Keep Laravel controllers and Blade views. Centralize session authorization, report filtering, student provisioning, and login identity lookup; preserve academic records and enforce daily attendance uniqueness in the database.
**Tech Stack:** Laravel 12, PHP 8.2+, SQLite/MySQL, Tailwind 3, Alpine, Vite.
**Spec:** docs/audit-2026-10-25/README.md and user authorization to repair all problematic code.

## Constraints

- Preserve existing school records; archive conflicting attendance rows during migration.
- Use additive migrations; test migrations on a separate database before touching local data.
- Run tests with SQLite in memory, using PHP 8.2 and development dependencies.
- Keep audit evidence as a historical record; add regression tests for the repaired behavior.
- Execute inline without commits or publication.

## Task 1: Regression coverage

- [x] Add tests/Feature/AuditRegressionTest.php for foreign-teacher 403, closed-session rejection, active-year selection, atomic writes, timestamp preservation, and daily uniqueness.
- [x] Add tests/Feature/AuditRegressionTest.php for rollover history, archived schedule edits, interval conflicts, scoped reset, protected deletion, classroom rename, valid routes, and transaction cleanup.
- [x] Add tests/Feature/AuditRegressionTest.php for correct session PDF date/status, alpa statistics, literal Excel text, and bounded report queries.
- [x] Add tests/Feature/AuditRegressionTest.php for personal-email reset, safe initial credentials, import idempotency, and password-change enforcement.
- [x] Run each new suite against current code and verify failures demonstrate the audited defects.

## Task 2: Attendance and retention

- [x] Implement SchedulePolicy and active-session checks in AttendanceController.
- [x] Add attendance_date, archive duplicate rows, and enforce unique schedule/student/date.
- [x] Save validated batches atomically and preserve the first attendance timestamp.
- [x] Disable self-service account deletion and prevent deleting referenced master records.
- [x] Run AuditRegressionTest and retention cases.

## Task 3: Academic workflows

- [x] Persist active_semester per academic year; remove operational dependence on cache.
- [x] Fix schedule validation, preserve original year, implement scoped bulk deletion and route ordering.
- [x] Update student status/history and classroom references atomically.
- [x] Fix academic-year activation transactions and initial history creation.
- [x] Run AuditRegressionTest and migrations on a copy of existing SQLite data.

## Task 4: Reports and accounts

- [x] Share validated report filters, paginate tables, bound PDF exports, and stream query-backed Excel exports with explicit string cells.
- [x] Filter session PDFs by date and render actual status/counts.
- [x] Aggregate daily/weekly dashboard statistics with the canonical alpa status.
- [x] Resolve NISN/system/personal email consistently for login and password reset.
- [x] Provision random initial passwords, require change, and make imports preserve alumni and existing identities.
- [x] Remove static production seed passwords and automatic demo records.
- [x] Run AuditRegressionTest and AuditRegressionTest.

## Task 5: Browser safety and assets

- [x] Render scanner student fields with DOM textContent; handle invalid QR input and HTTP failures.
- [x] Replace unsafe JS interpolation and SweetAlert HTML handling.
- [x] Bundle scanner, alerts, charts, icons, and fonts locally; retain established layout.
- [x] Fix labels, contrast, loading state, and mobile overflow.
- [x] Verify stored-XSS regression and scanner accessibility with agent-browser.

## Task 6: Dependencies and verification

- [x] Update compatible PHP/npm packages and lock files, reviewing remaining advisories.
- [x] Run full tests, PHP lint, clean frontend build, route/view/config cache checks, and dependency audits.
- [x] Document migrations, backups, credential delivery, deployment requirements, and any remaining verified limitations.

Final verification and migration evidence: docs/remediation-2026-10-25/README.md.
