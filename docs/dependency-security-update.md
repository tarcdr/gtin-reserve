# Dependency security update — 2026-10-25

## Audit result

Registry checks cover production and development dependencies in both lockfiles.

| Check | Before | After |
| --- | --- | --- |
| npm audit | 15 affected packages (9 high, 5 moderate, 1 low) | 0 |
| Composer audit | 58 advisories across 16 packages | 0 |

These checks identify published dependency advisories; they do not constitute an application penetration test or a Docker/OS/Oracle Instant Client vulnerability scan.

## Main version changes

| Package | Previous | Locked version |
| --- | --- | --- |
| laravel/framework | 10.50.0 | 12.70.1 |
| laravel/sanctum | 3.3.3 | 4.3.3 |
| inertiajs/inertia-laravel | 0.6.11 | 1.3.4 |
| yajra/laravel-oci8 | 10.6.0 | 12.11.0 |
| guzzlehttp/guzzle | 7.10.0 | 7.16.0 |
| phpoffice/phpspreadsheet | 1.30.1 | 1.30.7 |
| phpunit/phpunit | 10.5.60 | 11.5.57 |
| axios | 1.13.2 | 1.20.0 |
| vite | 7.3.1 | 7.3.7 |

Laravel 10 still reported five advisory entries after compatible patch updates, including a critical TokenGuard authentication bypass. Laravel's minimum constraint is now `^12.69`. Related Laravel packages, Symfony components and Carbon were updated together. The existing Laravel application structure is retained, as supported by the upgrade guide.

PHP now requires 8.2 or later. Composer resolves against PHP 8.2.0 so updates made on a newer local PHP do not accidentally require a newer production runtime. Docker frontend/development Node defaults now use Node 24; package.json requires Node >=22.12.0.

Sanctum configuration uses the v4 middleware keys, including session authentication. UserFactory creates password hashes with the configured hasher instead of a fixed bcrypt cost.

Tailwind 3 and postcss-nested require an older postcss-selector-parser range with an outstanding advisory. An npm override selects the patched 7.x parser (7.1.7 in the lockfile), keeping the existing Tailwind configuration. Production CSS generation succeeded with this override; inspect styling when deploying.

## Verification

- `npm audit --json`: zero vulnerabilities.
- `composer audit --locked --format=json`: no advisories or abandoned packages.
- `composer validate --strict`: passed.
- `npm run build`: passed; a CSS minifier warning about `-: T` remains.
- `node --test tests/js/*.test.mjs`: 2 passed.
- Authentication upgrade tests, Overview tests, unit tests and Example feature test: 21 passed, 226 assertions on local PHP 8.3.13 and Docker PHP 8.2.25 with OCI8 3.4.0.
- `composer check-platform-reqs` in the PHP 8.2 Docker image: passed, including OCI8.
- Artisan route registration and `git diff --check`: passed.

The complete existing PHPUnit suite was also run on SQLite: 40 tests, 19 errors and 1 failure. The old Auth/Profile tests use the starter-kit `name` / `email_verified_at` fields and email login, while the application's table/model and login request use `employee_name` / `user_login`. Registration also fails its authentication assertion. These failures remain unresolved; the complete suite is not green. The added UpgradeAuthenticationTest exercises the application's actual employee schema, login/logout, inactive account rejection, invalid credentials and password rehashing with a string primary key.

No live Oracle database was contacted. Oracle SQL/procedure behavior and an end-to-end browser session still require a staging check. The existing Docker image was used to verify runtime compatibility; the changed Dockerfiles have not been rebuilt or deployed. Rebuild images to incorporate the updated application dependencies and current base/OS packages.

## Deployment

Use `composer install` and `npm ci` with the committed lockfiles, then build frontend assets. Rebuild the production images and clear/rebuild Laravel caches through the existing deployment workflow. Do not use `--ignore-platform-reqs` in deployment. The local Composer update bypassed only the unavailable host OCI8 extension; the actual extension requirement was subsequently verified in Docker.

Review login, inactive accounts, password changes, Overview pages and Excel downloads against staging Oracle before promoting. No database migration or production deployment was executed by this update.

References: [Laravel 11 upgrade guide](https://laravel.com/docs/11.x/upgrade), [Laravel 12 upgrade guide](https://laravel.com/docs/12.x/upgrade).
