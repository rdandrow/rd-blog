# Roadmap: clean baseline, then features

Source: codebase audit, 2026-11-27. Decision: **keep and clean up, don't rebuild.**

The core is a conventional Laravel 12 + Inertia 2 + Vue 3 starter kit with working
features (2FA, invitations, roles, comments, likes, follows, markdown editor, dashboard
metrics) and green CI. The problems are in guardrails and process rather than
structure. Tests were written after the code, so a passing suite proves less than it
looks like it does. Phase 1 deals with that.

Work each item on its own branch, test-first, with the `work-step` skill. Tick items
off in the PR that completes them.

## Audit findings

Each finding lists the phase that fixes it.

### Guardrails

- [x] **CI lint could never fail.** `lint.yml` ran Pint, Prettier, and ESLint in
      write mode and discarded the result. About 110 PHP files and 4 Vue/TS files had
      drifted. → Phase 0
- [x] **No type-checking in CI.** `vue-tsc` had 5 errors: `User` lacked
      `role`/`bio`/`website`, and the 2FA modal's error-bag typing was wrong. → Phase 0
- [x] **No agent guidance.** There was no CLAUDE.md, no skills, and no permission
      allowlist, so every session started cold. → Phase 0
- [x] **Backend tests can't run locally.** `phpunit.xml` uses Postgres, and this
      machine had no Postgres. Now Postgres 16 runs session-only, and its lifecycle
      and cleanup are in the `run-locally` skill. → Phase 0
- [ ] **Frontend types still don't match the shared payload.** `HandleInertiaRequests`
      sends `auth.user` as `null` for guests and never sends `email_verified_at`,
      `created_at`, `updated_at`, or `avatar`, but `Auth`/`User` claim all of them.
      Profile.vue's "unverified email" banner and the avatar rendering read fields
      that never arrive. Making `auth.user` nullable touches every authenticated page.
      → Phase 1
- [ ] **Dead `/dashboard` links.** The route is `/admin/dashboard`, but breadcrumbs
      in five `Admin/BlogPosts/*` pages, and `SetupTwoFactor.vue`'s post-setup button,
      point to `/dashboard` (404). Use the Wayfinder `dashboard()` route. → Phase 1
- [ ] **`tests/frontend` isn't covered by Prettier** (62 files unformatted).
      `format:check` only covers `resources/`. → Phase 0 follow-up

### Bugs (fix test-first)

- [x] **2FA secret rotation on GET.** `GET /register/setup-two-factor`
      (`routes/web.php`, `RegisterController::setupTwoFactorAuthentication`) generates
      a new secret and recovery codes on every visit, even after 2FA is confirmed.
      `two_factor_confirmed_at` stays set, so the user's authenticator stops working
      and they're locked out. The route is a GET guarded only by `auth`, so a
      cross-site `<img>` can trigger it. **Highest priority.** → Phase 1
- [ ] **The 2FA setup GET still creates a secret when none is usable.** The rotation
      lockout is fixed, but a first visit still writes, against the "no
      state-changing GETs" rule. That leaves a cross-site request able to create a
      pending secret, and two tabs able to race to create one. Fix: render a "Begin
      setup" step that POSTs (through Fortify's `EnableTwoFactorAuthentication`), and
      make the GET read-only. → Phase 1, next 2FA PR
- [ ] **New users never see their recovery codes.** `SetupTwoFactor.vue` has a
      "Save Recovery Codes" step that nothing ever reaches: confirming redirects on
      the server. Show the codes after confirmation (same PR as above). → Phase 1
- [ ] **Role-based home redirect is copied in three places** (`LoginResponse`,
      `RegisterResponse`, `RegisterController::homeFor`). Extract it once. → Phase 2
- [ ] **`DashboardSeeder` writes plain-text 2FA secrets** (`Str::random(32)`), which
      don't decrypt. The setup page now replaces them, but seeded data should match
      real data. → Phase 2
- [ ] **`BlogPostPolicy::create` returns true for every user.** Only the route's
      `admin` middleware keeps members out. `BlogPostPolicyTest` asserts this
      behavior ("allows all authenticated users to create posts"), so the test
      protects the bug. → Phase 1
- [ ] **Admin `BlogPostController::show` has no authorization.** Any admin can view
      any other admin's drafts. Decide whether that's intended, then encode it in the
      policy. → Phase 1
- [ ] **`/blog` is unpaginated.** `BlogPostService::getAllPublishedPosts` loads every
      published post, with its content, on each request. → Phase 1
- [x] **The 2FA setup route is a closure that calls `new RegisterController()`.**
      Make it a normal controller route; it's fixed along with the rotation bug.
      → Phase 1
- [ ] **Dead code:** `BlogPostController::showBySlug` (no route uses it). → Phase 2

### Over-engineering for a blog (prune)

- [ ] `app/Database/Concerns/HasBatchOperations.php` (533 lines): included by
      `BlogPost` and `Comment`, but no app code calls its methods. → Phase 2
- [ ] `ExplainQuery` (637 lines) and `AnalyzeQueryPerformance` console commands, the
      `pg_stat_statements` migration, and `ResetQueryMonitoring` middleware. → Phase 2
- [ ] Postgres covering and partial-tag index migrations: keep only those that real
      queries use (check with `EXPLAIN`). Removing one needs a new down-migration;
      never edit a migration that has already run. → Phase 2
- [ ] 18 Postgres/performance docs in `docs/`: collapse into one short
      `docs/architecture.md`. → Phase 2
- [ ] Infection (`infection.json5`) is configured for PHPUnit, but the suite is Pest.
      Keep it only if it adds something over `pest --mutate`. → Phase 2
- [ ] Tests that exist only for the pruned code (`ExplainQueryCommandTest`,
      `PostgreSQLBatchOperationsTest`, and others) go with it. → Phase 2

### Frontend size and duplication

- [ ] `MarkdownEditor.vue` (1,690 lines): split into toolbar, editor, and preview
      parts. → Phase 2
- [ ] `Admin/BlogPosts/Create.vue` (734) and `Edit.vue` (969): likely duplicates;
      extract one shared form component. → Phase 2
- [ ] `Dashboard.vue` (775 lines): split into widgets. → Phase 2

### Dependencies

- [x] **Known vulnerabilities.** Once alerts were on, GitHub listed 146 open (2
      critical, 59 high), including a critical laravel/framework TokenGuard bypass.
      Fixed 2026-11-27 ahead of Phase 3, because security updates take priority:
      - Dependabot security PRs #39–#45 were merged after CI and local checks. #45 is
        the one out-of-order major (happy-dom 20 → 21, test-only; its only breaking
        change turns off CSS and iframe loading, which no test uses).
      - #46 (Vitest) was closed: Dependabot couldn't rebase it after the
        `dependabot.yml` change, and the refresh below covers it.
      - `chore/security-lockfile-refresh`: a targeted `composer update` (Laravel
        12.74, Pest 4.7, PHPUnit 12.5) plus `npm audit fix`, both within existing
        constraints. `composer audit` is clean. → done
- [ ] **Two moderate npm findings left**, both build or test tooling. The patched
      version is outside a range their parent pins exactly, so `npm audit fix` can't
      reach them and `--force` would *downgrade* the parent:
      `postcss-selector-parser` (exact pin in `@tailwindcss/typography`) and
      `minimatch` (via `@vue/test-utils` → `js-beautify` → `editorconfig`). Fix
      either when a parent release unpins it, or with an npm `overrides` entry if the
      audit step needs to be required sooner. → Phase 3
- [x] 5 stale Dependabot PRs (#21, #22, #23, #34, #36): closed 2026-11-27, because
      every version in them had been superseded. → done
- [x] **Dependabot was effectively off.** Alerts and security updates were disabled,
      and version updates had stopped after June 15 (npm was at its 5-open-PR cap and
      likely auto-paused). Alerts and security updates are now on. Version updates
      run monthly, majors grouped but not ignored (ignore rules can also suppress
      security updates), and `@types/node` is held at Node 22. Process: the
      `dependency-upgrades` skill. → done
- [ ] Majors available: Laravel 13, Inertia 3 (server and client), Pest 5, Vitest 5,
      laravel-vite-plugin 3, Vite 8. → Phase 3

### CI enhancements

- [ ] **No branch protection on `develop` or `main`**, so every check is advisory
      and a red PR can still be merged. Add a ruleset that requires the `quality`
      and `ci` checks and blocks direct pushes. This is a repo setting change, so
      confirm with the owner first. → Phase 0 follow-up, do first
- [ ] **CI runs only on PRs into `develop` or `main`.** Stacked PRs get no checks.
      Retargeting a stacked PR after its parent merges doesn't trigger a run either
      (#38 sat with no checks), so it needs a push or a close and reopen. Drop the
      `branches:` filter under `pull_request:` in `lint.yml` and `tests.yml`.
      → Phase 0 follow-up
- [ ] **Coverage is collected but never used.** `tests.yml` sets `coverage: xdebug`,
      which slows every run, but nothing reads the result. Measure today's number,
      then add `pest --coverage --min=<that number>` as a floor, or set
      `coverage: none`. → Phase 0 follow-up
- [ ] **No PHP static analysis.** Add Larastan, starting around level 5 with a
      baseline so it fails only on new errors. It would have flagged `decrypt()` on
      a value that can be null (the 2FA confirm 500). → Phase 1
- [ ] **No dependency audit in CI.** Add `composer audit` and
      `npm audit --audit-level=high`. Audit dev dependencies too: the known highs are
      in dev tooling (the Vite dev server and build plugins), which `--omit=dev`
      would skip. Start non-blocking (`continue-on-error`), and make it required
      once Phase 3 clears the existing highs. → Phase 1 (non-blocking), Phase 3 (required)
- [ ] **Migrations are never rolled back in CI.** Add `migrate`, then
      `migrate:rollback`, then `migrate` again, on the CI Postgres, before Phase 2
      removes index migrations. → Phase 2
- [ ] **Mutation testing is manual.** Add a PR check that mutates only the classes a
      PR touches. Decide between Infection and `pest --mutate` first. → Phase 2
- [ ] **No browser tests.** Add a few Playwright smoke tests for register, 2FA
      setup, and login. Both deferred 2FA issues only show up in a real browser.
      The portfolio repo's Playwright skills can be adapted. → Phase 4

- [ ] **Auto-merge for Dependabot minor/patch PRs** (`dependabot/fetch-metadata`
      plus GitHub auto-merge). Only once CI can be trusted unattended: required
      checks, Larastan, and the browser smoke tests in place. → Phase 4, optional

Not planned: running Pest both parallel and sequential in CI (doubles backend time
for a rare bug class), and a CI secret scanner. GitHub's built-in secret scanning and
push protection are already enabled on this public repo.

## Phases

### Phase 0: guardrails

Done when CI fails on any style, lint, or type error, and an agent can run the whole
suite locally.

- [x] Check-only CI: `pint --test`, `format:check`, `lint:check`, `types`
- [x] Fix the existing type errors and formatting drift (formatting commit is in
      `.git-blame-ignore-revs`)
- [x] `CLAUDE.md`, `.claude/settings.json`, and the skills `work-step`,
      `review-tests`, `run-locally`
- [x] Local Postgres 16 and a green `composer test` (975 passed, parallel and
      sequential), plus dev migrate and seed
- [ ] Extend Prettier to `tests/frontend`, in its own formatting commit
- [ ] CI follow-ups (see "CI enhancements"): require checks with a ruleset, run CI on
      all PRs, use or drop coverage

### Phase 1: make the tests trustworthy, then fix bugs

Done when every bug above has a failing-then-passing test, and the auth, policy, and
middleware tests survive mutation testing.

1. Run mutation testing (`pest --mutate --class=...`) on `App\Policies`,
   `App\Http\Middleware`, and `App\Http\Controllers\Auth` to find tests that pass
   even when the code is broken. Use the `review-tests` skill.
2. Rewrite tests that assert current behavior instead of intended behavior. Start
   with `BlogPostPolicyTest`.
3. Fix each bug test-first, starting with the 2FA rotation.
4. CI: add Larastan with a baseline, and a non-blocking dependency audit.

### Phase 2: prune and split

Done when the pruned code and its tests are gone, the suite is still green, and no
Vue file is over about 400 lines without a written reason. CI checks migration
rollbacks before any index migration is removed, and mutation testing runs on
changed classes.

### Phase 3: upgrades

One major version per PR, with CI green after each, following the
`dependency-upgrades` skill: merge the pending grouped minor/patch PRs (the security
fixes already landed; see Dependencies) → Laravel 13 → Inertia 3 (server and client together) → Pest 5 and
Vitest 5 → Vite 8 and laravel-vite-plugin 3. Once the audit is clean, make the
dependency-audit CI step required.

### Phase 4: features

Resume feature work, test-first, through the `work-step` skill. Add Playwright smoke
tests for the auth flows first.
