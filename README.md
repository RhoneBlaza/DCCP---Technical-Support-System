<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About This Application

Tech Support Ticketing System (TSTS) for Data Center College of the Philippines - Bangued.

### Registration & Account Verification

- New accounts self-register as **Pending**; an official ID image is uploaded to the private disk and a verification request is created for administrators.
- Admins review requests under **Admin > Verifications**: approve (activates the account), reject, or request resubmission (with a required reason, shared with the requester).
- Users who are not approved are blocked from signing in and are shown an account-status page with their decision/reason.
- `role` is not accepted from registration forms (enforced server-side).
- Every ID-image view is recorded in the audit log as `id_image_viewed`, and image responses disable browser caching.

### Activity Monitor

- Under **Admin > Reports > Activity Monitor**, administrators can browse the full audit log feed and export it as a CSV. CSV formula-injection risk is mitigated by escaping cells that start with `=`, `+`, `-`, or `@`.

### ID Document Retention

- The retention period is configurable under **Admin > Settings** (`id_image_retention_days`; `0` keeps files forever).
- Run `php artisan purge:expired-id-images` (scheduled daily) to delete ID documents older than the retention window. Verification records (metadata) are retained; only the uploaded file is removed.

### Database

- **SQLite** is the default connection for quick local testing — no server setup required. The test suite always runs on in-memory SQLite; run `php artisan test` after any migration change.
- **MySQL/MariaDB** is the target for deployment. Switch to it with `DB_CONNECTION=mysql` in `.env` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) and run the migrations with `php artisan migrate`. Run `php artisan test` against the SQLite test config to confirm migrations are portable.

### Account Status Page Security

- The account-status page is only reachable via a **signed, temporary (15-minute) URL** generated after a successful-password login attempt against a non-approved account. Guessing or changing the user ID in the URL is rejected by the signature.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
