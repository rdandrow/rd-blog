---
name: run-locally
description: Set up local PostgreSQL, start the rd-blog dev stack, run the Pest and Vitest suites, and verify the app is actually serving. Use before running backend tests, before reporting any user-visible change as complete, or when tests fail with database connection errors.
---

## PostgreSQL (required for backend tests)

`phpunit.xml` defaults to `pgsql` on `127.0.0.1:5432`, database `rd_blog_test`, user
`rd_blog_user`. The suite uses Postgres-only features (full-text search, JSONB, partial
indexes), so SQLite won't work.

One-time setup with Homebrew (ask the user before installing anything):

```sh
brew install postgresql@16
brew services start postgresql@16
export PATH="/opt/homebrew/opt/postgresql@16/bin:$PATH"   # psql isn't linked by default
psql postgres -c "CREATE USER rd_blog_user WITH PASSWORD 'secret' CREATEDB;"
psql postgres -c "CREATE DATABASE rd_blog_dev OWNER rd_blog_user;"
psql postgres -c "CREATE DATABASE rd_blog_test OWNER rd_blog_user;"
```

`CREATEDB` is required: `composer test` runs Pest with `--parallel`, which creates a
database for each worker. Put `DB_PASSWORD` in `.env` (gitignored); never commit it
or put it in `phpunit.xml`.

Check that it's up: `pg_isready -h 127.0.0.1`. When tests fail with "connection
refused", run `brew services start postgresql@16`.

## Test suites

```sh
composer test                          # Pest, parallel (clears config first)
vendor/bin/pest --filter "name"        # one test; add tests/Feature/X.php for one file
npm run test:run                       # Vitest, once
npx vitest run tests/frontend/<path>   # one file
```

## Dev stack

```sh
cp -n .env.example .env && php artisan key:generate   # first time only
php artisan migrate --seed                             # seeds with DB_SEED for stable data
composer dev                                           # serve :8000 + queue + pail + vite
```

`composer dev` stays in the foreground, so run it in the background (Bash
`run_in_background`) and wait until it's actually serving rather than sleeping:

```sh
until curl -sf http://127.0.0.1:8000/ >/dev/null; do sleep 0.5; done
```

If `php artisan serve` finds 8000 busy, it picks the next free port; check the output
for the URL. Stop the stack by killing the background task, or by port with
`lsof -ti:8000,5173 -sTCP:LISTEN | xargs -r kill`.

## Logging in

Every account needs 2FA (`ensure.2fa`). The seeded users in
`database/seeders/DatabaseSeeder.php` don't have 2FA confirmed, so the first login
goes through the setup page. Ask the user to scan the QR code, or confirm it in
`php artisan tinker` with the secret the page shows. In feature tests, use factory
users with `two_factor_confirmed_at` set.

## Gotchas

- `vite build` doesn't type-check. Run `npm run types` too.
- Wayfinder generates `resources/js/{actions,routes,wayfinder}` during Vite runs. If
  imports from `@/routes/...` fail type-checking on a fresh clone, run
  `npm run build` once.
- `CACHE_STORE=database` locally, `array` in tests. Cache bugs can hide in tests;
  reproduce them in the running app.
