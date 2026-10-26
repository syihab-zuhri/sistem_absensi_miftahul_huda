# Login Desktop Performance Implementation Plan

> **For agentic workers:** Execute this plan inline, task by task. The referenced superpowers executors are unavailable in this workspace.

**Goal:** Reduce the public login page's rendering and download cost and measure the result using unchanged Lighthouse desktop settings.

**Architecture:** Give guest authentication views a separate Vite CSS/JS entry. Keep the existing dashboard entry, replace login webfont icons with decorative inline SVG, and serve one local Jakarta variable font instead of four static weights.

**Tech Stack:** Laravel 12, PHP 8.2–8.4, Vite 7, Tailwind 3, vanilla JavaScript, Node test runner/jsdom, Lighthouse 13.5.0, Chrome 151.

**Spec:** User request: improve Lighthouse performance on the login page in desktop mode. Baseline: 99, 99, 87; unused CSS ~32 KiB, unused JS ~28 KiB, icon font ~119 KiB.

## Global Constraints

- Preserve login, password recovery, validation, CSRF, session handling, and existing visual design.
- Preserve dashboard assets and all production data, credentials, hosting settings, and existing hashed assets.
- Work on `codex/lighthouse-performance`; do not commit the user's untracked `vendor.zip`.
- Measure the public `/login` with `--preset=desktop --only-categories=performance`, the same Chrome and Lighthouse, three sequential runs before and after.
- Publish only sanitized measurements; no cookies, private keys, environment contents, or user data.

### Task 1: Lightweight guest assets and password behavior

**Files:** `tests/js/guest.test.js`, `resources/js/guest.js`, `resources/css/guest.css`, `tailwind.guest.config.js`, `vite.config.js`, `package.json`, `package-lock.json`, `resources/views/layouts/guest.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/components/auth-icon.blade.php`.

**Interface:** `initializePasswordToggle(document)` attaches the login button's click handler using `data-password-toggle`, `aria-controls`, `data-password-hidden`, and `data-password-visible`. It is safe on other guest pages.

- [x] Write DOM tests that click the button twice, check password type/value, icon visibility, ARIA state, and no form submission; also cover a page without a toggle. Run `node --test tests/js/guest.test.js` and observe missing behavior.
- [x] Implement the small module and module entry initialization. Use a native `type="button"` and keep all form input names/actions unchanged.
- [x] Add seven trusted Font Awesome SVG paths with explicit dimensions and `aria-hidden`, keeping each icon's size/color. Remove the inline toggle script.
- [x] Add a dedicated Tailwind config scanning auth views, guest layout, and shared components. Import only Latin Jakarta variable font; preload that hashed font through `Vite::asset`.
- [x] Register both guest entries alongside existing application entries. Run `npm test`, `npm run build`, and PHP authentication tests with `php82`.

### Task 2: Verify, release, and measure

**Files:** `docs/lighthouse-2026-10-26/README.md`, sanitized measurements JSON; private deployment scripts/backups outside the repository.

- [x] Inspect a desktop/mobile preview and exercise password visibility and password recovery. Verify all Vite manifest dependencies exist and the login requests only its own entries.
- [x] Run application PHP tests in an isolated environment. Review `git diff --check` and the change scope before committing.
- [ ] Commit and push the dedicated branch. Back up changed production files privately, upload hashed assets first, update views/manifest, rebuild the view cache, and verify public health/login. Roll back targeted files on failure; do not alter the database.
- [ ] Run three sequential public Lighthouse desktop audits with the original flags. Compare median scores, FCP/LCP, layout shift, request counts, and transferred asset bytes. Record variability honestly, including the unreproduced score 78.
- [ ] Publish sanitized evidence and deployment commit on the branch, then report measured results in Indonesian.
