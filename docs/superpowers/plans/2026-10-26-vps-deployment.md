# VPS Attendance Deployment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deploy the verified attendance branch to absensi.zuhri.my.id while preserving production records and recoverable backups.

**Architecture:** Prepare dependencies and assets in a private release directory over `ssh vps-codex`, rehearse migrations against an online SQLite backup, then briefly enable maintenance for final backups and installation. Preserve production environment values, uploads, and database; verify record fingerprints and public HTTP behavior after migration.

**Tech Stack:** Laravel 12, PHP-FPM 8.3, Nginx, SQLite, Composer, Vite, SSH/rsync.

**Spec:** `docs/attendance-workflows.md`, `docs/attendance-workflows-2026-10-25/README.md`, and the user's authorization to deploy through the dedicated `codex` SSH account.

## Global Constraints

- Use branch `codex/attendance-workflows`; preserve and verify the currently staged improvements.
- Access the VPS exclusively through its configured `vps-codex` SSH alias. Never copy private keys or production credentials into Git or local deployment reports.
- Production root: `/www/wwwroot/absensi.zuhri.my.id`. User: `codex`, without sudo.
- Do not run test-suite resets against production data. Rehearse using a private database copy and isolated cache/session/mail settings.
- Preserve the existing application key, account passwords, uploads, and historical attendance values.
- Prepare and verify backups before production migration. Restore the prior application/database if deployment verification fails.
- Execute inline; the referenced superpowers executor packages are unavailable in this environment.

---

### Task 1: Verify the release and current installation

**Files:** Existing staged application/tests and this deployment record.

- [ ] Inspect remote write permissions, PHP-FPM routing, database driver, counts, migration state, storage size, and available backup tools.
- [ ] Review staged recording-grace, email-routing, and restartable migration changes.
- [ ] Run `php82 artisan test --compact`, `npm test`, `npm run build`, Composer validation, and Pint checks. Record the results without production credentials.
- [ ] Commit the verified application changes on the feature branch and identify the exact release commit.

### Task 2: Prepare and rehearse privately

**Files:** A private timestamped directory under `/home/codex/absensi-deployments/`, outside the web root.

- [ ] Transfer a clean source archive without `.env`, keys, database files, or uploads.
- [ ] Install dependencies privately, verify the PHP suite under the server's PHP 8.3 with an explicitly isolated in-memory database, then run `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts`; transfer the verified Vite build.
- [ ] Create a consistent online SQLite backup through the SQLite backup API and check `PRAGMA integrity_check`.
- [ ] Point the private release at a separate copy of that backup; disable real mail/queue processing and use isolated cache/session settings.
- [ ] Run production migrations on that copy and verify original account, student, schedule, and attendance fingerprints, accounting for archived duplicates and explicitly assigned academic years.

### Task 3: Deploy with a recoverable maintenance window

**Files:** Production code/dependencies/assets; preserve `.env`, `database/*.sqlite`, and `storage/`.

- [ ] Enable Laravel maintenance and create final code/environment/uploads/database backups in the private deployment directory.
- [ ] Check backup integrity and fingerprint baselines before changing production code.
- [ ] Synchronize verified code/dependencies/assets, preserve environment values, and regenerate Laravel package/cache files for the real production path.
- [ ] Run `php artisan migrate --force`, verify fingerprints and required table counts, and check Laravel boot/routes.
- [ ] Reopen the application only after checks pass; if they fail, restore the old code/database and verify recovery before reopening.

### Task 4: Verify delivery

**Files:** Deployment report with safe metadata and backup locations; no private data.

- [ ] Check public login, CSRF/session handling, protected feature routes, health endpoint, and built assets over HTTPS.
- [ ] Inspect the login interface using a named browser session, without creating or changing real school accounts.
- [ ] Verify the working branch is pushed to GitHub and record the deployed commit, backup location, test results, and any limitations.
