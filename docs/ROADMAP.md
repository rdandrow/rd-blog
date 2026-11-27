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

- [ ] `npm audit`: 16 vulnerabilities (14 high, mostly Vite and tooling).
      `composer audit`: 1 low (symfony/yaml). → Phase 3
- [ ] 5 stale Dependabot PRs (#21, #22, #23, #34, #36). → Phase 3
- [ ] Majors available: Laravel 13, Inertia 3 (server and client), Pest 5, Vitest 5,
      laravel-vite-plugin 3, Vite 8. → Phase 3

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

### Phase 1: make the tests trustworthy, then fix bugs

Done when every bug above has a failing-then-passing test, and the auth, policy, and
middleware tests survive mutation testing.

1. Run mutation testing (`pest --mutate --class=...`) on `App\Policies`,
   `App\Http\Middleware`, and `App\Http\Controllers\Auth` to find tests that pass
   even when the code is broken. Use the `review-tests` skill.
2. Rewrite tests that assert current behavior instead of intended behavior. Start
   with `BlogPostPolicyTest`.
3. Fix each bug test-first, starting with the 2FA rotation.

### Phase 2: prune and split

Done when the pruned code and its tests are gone, the suite is still green, and no
Vue file is over about 400 lines without a written reason.

### Phase 3: upgrades

One major version per PR, with CI green after each: Dependabot PRs and
`npm audit fix` → Laravel 13 → Inertia 3 (server and client together) → Pest 5 and
Vitest 5 → Vite 8 and laravel-vite-plugin 3.

### Phase 4: features

Resume feature work, test-first, through the `work-step` skill.
