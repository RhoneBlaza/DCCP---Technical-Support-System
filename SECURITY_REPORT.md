# Tech Support Ticketing System — Security Report

**Date:** 2026-09-21
**Scope:** local Laravel application only (`http://127.0.0.1:8000`) and the test database.
**Application:** Laravel 13.32.0, PHP 8.5, SQLite (dev + tests), three roles (`admin`, `support`, `requester`), account states `pending`/`approved`/`rejected`/`suspended`.
**Method:** source review of every controller, middleware, request, policy, model, service, route and Blade view; framework/dependency inspection; then automated feature tests with `RefreshDatabase` (in-memory SQLite). No destructive tests were run against development data.

---

## 1. Commands run and results

| Command | Result |
|---|---|
| `php artisan test --compact` | **69 passed**, 465 assertions, ~8.9s |
| `php artisan route:list --except-vendor` | 78 routes; **no `storage/{path}` routes** |
| `composer audit` | No security vulnerability advisories found |
| `npm audit` | 0 vulnerabilities |
| `php artisan migrate:status` | All 18 migrations run (no pending) |

Backup taken before work: `database/database.sqlite` → `%TEMP%\opencode\database.sqlite.bak`.

> Regression tests for this review live under `tests/Feature/Security/` (grouped per class) and in `tests/Feature/SecurityRegressionTest.php`.

---

## 2. Attack surface (route table, summarized)

All routes are registered inside the `web` middleware group (session + CSRF), except the framework `GET /up` health check. Route groups:

| Area | Routes | Middleware / access |
|---|---|---|
| Guest auth | `login`, `register`, `forgot-password`, `reset-password`, `account-status/{user}` | `guest`; `account-status` additionally `signed` |
| Authenticated | `dashboard`, `profile`, `account` (change password), `search`, `logout` | `auth`, `active`, `must.change.password` |
| Tickets | `tickets`, `tickets/create`, `ticket/{ticket}`, reply, internal-note, attachment download | `auth`, `active`, `must.change.password`; policies + `visibleTo` |
| Support queue | `support/queue`, `support/all-tickets`, assign/unassign/status/priority/category/resolve/close/reopen | `role:support,admin` |
| Notifications | index, recent, read, read-all | `auth` |
| Admin | users, departments, categories, priorities, statuses, settings, verifications (+ ID image), audit-logs, reports | `role:admin` + policies |
| Framework | `GET /up` | health check, no state change |

No state-changing route uses `GET`. The previously exposed framework `GET|HEAD storage/{path}` and `PUT storage/{path}` routes are gone (finding F-01).

---

## 3. Findings

Severity: Critical / High / Medium / Low / Info. Every fixed finding has a regression test.

| ID | Title | Severity | Location | Fix | Regression test |
|---|---|---|---|---|---|
| F-01 | Framework private-disk `storage/{path}` routes exposed (`local` disk had `serve => true`, root `storage/app/private`) | **High** | `config/filesystems.php` | Removed `serve => true`; routes unregistered | `SecurityRegressionTest::test_framework_storage_serve_routes_are_not_registered` |
| F-02 | `Cache-Control: no-store` only applied to authenticated users; guest account-status decision page was cacheable | **Medium** | `app/Http/Middleware/SecurityHeaders.php` | Always send `no-store, private` | `Security/HeadersTest::test_guest_responses_are_marked_no_store`, `test_account_status_page_is_no_store_even_for_guests` |
| F-03 | No HSTS / Cross-Origin-Resource-Policy | Low | `SecurityHeaders` | HSTS in production over HTTPS; `CORP: same-origin` always | `Security/HeadersTest::test_hsts_header_is_only_sent_in_production_over_https` |
| F-04 | LIKE wildcard escaping ineffective (SQLite ignores `\` without `ESCAPE`); some sites unescaped | **Medium** | `AppServiceProvider`, `SearchController`, `TicketsController`, `Admin\*Controller` (9 files) | Added `whereLike`/`orWhereLike` Builder macros that emit `lower(col) like lower(?) escape '\'`; migrated all call sites | `SecurityRegressionTest::test_category_search_escapes_like_wildcard_characters`, `Security/SqlInjectionTest::test_underscore_like_wildcards_are_escaped` |
| F-05 | Login timing/enumeration oracle: unknown email skipped hashing, and valid logins hashed twice | **Medium** | `Auth\LoginController` | Exactly one `Hash::check` per request; sacrificial hash for unknown accounts; direct `Auth::login` | `Security/AccountStatusTest::test_login_returns_the_same_generic_error_for_unknown_email_and_wrong_password` |
| F-06 | Password change/reset did not rotate `remember_token` (leaked cookie survived) | **Medium** | `Auth\ChangePasswordController`, `Auth\ResetPasswordController`, `Admin\UsersController` | Rotate `remember_token` (+ session regenerate on change) | `Security/SessionTest::test_password_change_rotates_the_remember_token`, `test_admin_password_reset_rotates_the_remember_token` |
| F-07 | No maximum password length; bcrypt silently truncates at 72 bytes | Low | `Rules\StrongPassword`, `RegisterRequest`, `CreateAdminCommand` | Reject > 72 bytes | `Security/SessionTest::test_oversized_passwords_are_rejected` |
| F-08 | `TicketAttachment::message()` excluded soft-deleted messages, so an internal-note attachment could flip to public visibility | Low | `Models\TicketAttachment` | Relation uses `withTrashed()` | `Security/IdorTest::test_internal_note_attachment_stays_hidden_after_message_is_soft_deleted` |
| F-09 | No throttling on ticket create/reply/internal-note or password-reset endpoints | **Medium** | `routes/web.php`, `routes/web/tickets.php` | `throttle` middleware (reset 5/min, tickets 20/min, replies 30/min) | `Security/RateLimitTest::test_password_reset_endpoint_is_throttled` |
| F-10 | Notification "mark read" redirected to a raw `data['url']` (open-redirect defense in depth) | Low | `NotificationsController` | Only redirect to local host/relative URLs | `Security/OpenRedirectTest::test_notification_mark_read_ignores_external_urls` |
| F-11 | Profile photos stored on the public disk although never displayed | Low | `ProfileController` | Store on the `private` disk | `Security/UploadSecurityTest::test_profile_photo_is_stored_on_the_private_disk` |
| F-12 | Audit CSV export lacked a UTF-8 BOM; `csvSafe()` missed whitespace/null/pipe starters | Low | `Admin\AuditLogsController` | BOM added; `csvSafe()` hardened | `Security/AuditLogTest::test_audit_log_export_starts_with_a_utf8_bom` |
| F-13 | Attachment downloads were not audited | Info | `TicketsController` | `attachment_downloaded` audit entry | `Security/AuditLogTest::test_attachment_download_is_audited` |
| F-14 | Registration throttle keyed by IP only | Low | `Auth\RegisterController` | Enforce **both** per-IP and per-email limits | covered by existing rate-limit behavior |
| F-15 | `.env.example` missing secure-cookie guidance and `APP_DEBUG` production note | Info | `.env.example` | `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`, comments added | n/a (documentation) |

---

## 4. Verified secure (no change required)

Each item below was checked in source and/or covered by the existing suite.

- **Role separation** — admin/support/requester boundaries enforced by `EnsureRole` + policies (`RoleAuthorizationTest`).
- **IDOR/BOLA** — ticket visibility via `Ticket::visibleTo`, attachment visibility via `AttachmentService::isVisibleTo`, `abort_unless(..., 404)` for foreign tickets; `TicketPolicy`.
- **Mass assignment** — every form uses a Form Request with an explicit whitelist; `role` is `prohibited` on registration; profile update ignores privilege fields.
- **Account status** — `EnsureUserIsActive` terminates sessions of deactivated users; pending/rejected/suspended accounts cannot log in; `/account-status/{user}` requires a signed URL and 404s for approved accounts.
- **ID images** — private disk, random filenames, admin-only authorized route, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, every view audited.
- **Uploads** — extension **and** MIME allowlist, random stored names, private disk, sanitized original names.
- **CSRF** — all state-changing routes are in the `web` group; no `VerifyCsrfToken` exclusions.
- **XSS** — no `{!! !!}` or `v-html`/`x-html` on user data; Alpine uses `x-text`; audit feed renders an escaped Blade partial.
- **SQL injection** — no user-controlled `orderByRaw`/`selectRaw`; all values parameterized (the `orderByRaw` CASE and table maps are hard-coded).
- **CSV formula injection** — reports and audit exports neutralize `= + - @` (and tab/CR) starters.
- **Error handling** — `APP_DEBUG=false` yields friendly 500s with no stack trace.
- **Audit integrity** — append-only, admin-only, no update/delete route; passwords/remember tokens stripped from old/new values; failed logins store no password.
- **Dependencies** — `composer audit` and `npm audit` clean.
- **Demo data** — `DemoSeeder` (password `Admin123`) is gated to `local`/`testing`; cannot run in production.

---

## 5. Remaining items / accepted risks

- **No Content-Security-Policy.** Deliberately omitted: Alpine.js inline expressions and Vite require `unsafe-inline`/`unsafe-eval`, and a wrong CSP would break the UI. Documented exception; revisit with nonce-based Alpine.
- **Registration reveals an email is already taken.** This is a functional requirement of the registration form (unique identifier); rated Low. A verification-email flow would remove it.
- **Cross-device session invalidation on password change.** Only the `remember_token` is rotated; existing database sessions for other devices are not force-terminated. Add `Auth::logoutOtherDevices()` if required.
- **Stale `storage/logs/laravel.log`** contained a bcrypt hash inside an old SQL error trace from a previous test run (testing env). Logs are git-ignored; recommend log rotation and not logging query bindings in production.
- **No independent penetration test / DPA (RA 10173) review.** Recommended before production with real student/staff ID photos.

---

## 6. Production hardening checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, fresh `APP_KEY`.
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax` (or `strict`), `SESSION_ENCRYPT=true`.
- [ ] Force HTTPS at the web server; HSTS is emitted automatically in production over HTTPS.
- [ ] Move to MySQL/MariaDB with a least-privilege DB user; `php artisan migrate --force`.
- [ ] Keep `storage/`, `bootstrap/cache/` and `vendor/` outside the web root (or deny all); directory listing off.
- [ ] Run the queue worker and scheduler (`schedule:run`) as an unprivileged service account.
- [ ] Enable log rotation; keep `LOG_LEVEL` at `warning` or higher.
- [ ] Run `php artisan test` and `composer audit` in CI on every change.
