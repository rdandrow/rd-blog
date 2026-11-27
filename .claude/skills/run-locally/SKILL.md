---
name: run-locally
description: Set up, start, stop, and clean up local PostgreSQL; start the rd-blog dev stack; run the Pest and Vitest suites; and verify the app is actually serving. Use before running backend tests, before reporting any user-visible change as complete, when tests fail with database connection errors, and at the end of any session that started Postgres or the dev stack.
---

## PostgreSQL (required for backend tests)

`phpunit.xml` defaults to `pgsql` on `127.0.0.1:5432`, database `rd_blog_test`, user
`rd_blog_user`. The suite uses Postgres-only features (full-text search, JSONB, partial
indexes), so SQLite won't work.

psql isn't linked by default, so start every shell command that uses it with:

```sh
export PATH="/opt/homebrew/opt/postgresql@16/bin:$PATH"
```

One-time setup (already done on this machine; ask the user before installing
anything). The password goes only into the gitignored `.env`. Never commit it or put
it in `phpunit.xml`.

```sh
brew install postgresql@16
PW=$(openssl rand -hex 16)
psql postgres -v ON_ERROR_STOP=1 -v pw="$PW" <<'SQL'
CREATE USER rd_blog_user WITH PASSWORD :'pw' CREATEDB;
CREATE DATABASE rd_blog_dev OWNER rd_blog_user;
CREATE DATABASE rd_blog_test OWNER rd_blog_user;
SQL
# then, in the same shell: set DB_DATABASE=rd_blog_dev, DB_USERNAME=rd_blog_user,
# and DB_PASSWORD=$PW in .env
```

psql variables (`:'pw'`) only work in SQL sent on stdin, not with `-c`. `CREATEDB` is
required because `composer test` runs Pest with `--parallel`, which creates a database
for each worker.

## Postgres lifecycle: start it, use it, stop it

The server runs only while someone is working. Leave nothing behind.

**Start (session only):**

```sh
brew services run postgresql@16    # NOT `start`: that also launches it at every login
until pg_isready -h 127.0.0.1 -q; do sleep 0.5; done
```

If it was already running when you arrived, the user started it. Leave it running at
the end, and say so.

**Stop, when the work session ends** (not between commands; tests need it all
session):

```sh
brew services stop postgresql@16
ls ~/Library/LaunchAgents | grep -c postgres   # must print 0: nothing auto-starts
```

**Stale state, and how to clear it:**

| State | Goes stale when | Reset |
|---|---|---|
| `rd_blog_test_test_1..N` (parallel workers) | they pile up; Laravel keeps them, and neither `--drop-databases` nor its env var cleans them in this version | `psql postgres -Atc "select datname from pg_database where datname like 'rd\_blog\_test\_test\_%'" \| xargs -n1 dropdb --if-exists` |
| `rd_blog_test` | rarely: `RefreshDatabase` re-migrates it each run | nothing to do |
| `rd_blog_dev` data | migrations or seeders change, or manual testing leaves junk | `php artisan migrate:fresh --seed` (wipes dev data; ask first if the user may have content there) |
| dev cache (`CACHE_STORE=database`) | after reseeding, or when cache-version bugs are suspected | `php artisan cache:clear` |

Drop the worker databases at the end of any session that ran `composer test`, then
stop the server. Check for leftovers with
`psql postgres -Atc "select datname from pg_database where datname like 'rd_blog%'"`.
Only `rd_blog_dev` and `rd_blog_test` should remain.

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

Before starting, check `lsof -i:8000,5173 -sTCP:LISTEN`. If something is already
listening, it's the user's: use it or ask, and never kill it. If `php artisan serve`
finds 8000 busy, it picks the next free port; check the output for the URL.

Stop only what you started: stop the background task you launched (the
`--kill-others` flag in `composer dev` takes down the whole group). Don't kill by
port, which can hit the user's own processes.

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
