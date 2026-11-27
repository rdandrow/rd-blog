---
name: dependency-upgrades
description: Review and merge Dependabot PRs (security, grouped minor/patch, grouped major) and run planned major upgrades (Laravel, Inertia, Pest, Vitest, Vite) in rd-blog. Use when a Dependabot PR or alert needs handling, when asked to "update dependencies" or fix `npm audit` / `composer audit` findings, and for roadmap Phase 3.
---

Dependabot setup (`.github/dependabot.yml`):
- **Version updates:** monthly, for Composer, npm, and GitHub Actions. Each
  ecosystem's updates are grouped into production and development PRs, split by
  minor/patch vs major.
- **Security updates:** enabled in repo settings. They aren't tied to the schedule,
  and they arrive as individual PRs (groups don't apply to them).
- **Majors aren't ignored** in config, because ignore rules can also suppress
  security updates. Major PRs can wait until Phase 3.

A PR's checks only mean something once branch protection requires them. Until then,
check that `quality` and `ci` actually ran and passed (`gh pr checks <n>`); don't
assume.

## 1. Triage the PR

```sh
gh pr view <n> --json title,files,body -q '.title, (.files[].path)'
gh pr checks <n>
gh pr diff <n> -- package.json composer.json   # which constraints changed
```

| PR kind | Handling |
|---|---|
| Security update | First priority. Read the advisory link in the PR body, then step 2. Merge even if it's a major bump, after the major-upgrade checks in section 3. |
| Grouped minor/patch, no build tooling | Green checks plus a changelog skim, then merge. |
| Grouped minor/patch that touches build tooling or the framework (vite, @vitejs/*, tailwindcss, @tailwindcss/*, laravel-vite-plugin, wayfinder, @inertiajs/*, inertiajs/inertia-laravel, laravel/framework, laravel/fortify) | Step 2, then merge. |
| Grouped major | Leave it open, or close it, until its Phase 3 turn. Then follow section 3; never merge a group of majors as is. |

## 2. Verify locally (tooling, framework, or security PRs)

Load `run-locally` for Postgres. Then:

```sh
gh pr checkout <n>
composer install && npm ci          # match the lockfiles exactly; never `npm install`
php artisan wayfinder:generate --with-form
composer test && npm run test:run && npm run types && npm run lint:check \
  && npm run format:check && vendor/bin/pint --test && npm run build
```

For tooling or framework changes, also start `composer dev` and load the home page,
a post, login, and the admin dashboard, and check the browser console for errors.
Afterwards, go back to the previous branch and follow the cleanup in `run-locally`.

If something fails, don't patch around it on the Dependabot branch. Record why in
`docs/ROADMAP.md` and either split the fix into its own PR or tell Dependabot to skip
it. On a single-dependency PR, comment `@dependabot ignore this minor version` (or
`... this major version`, `... this dependency`). On a grouped PR, name the package:
`@dependabot ignore <package> minor version`. Comment commands change over time, so
check GitHub's Dependabot docs if one isn't recognized. To refresh a stale PR, comment
`@dependabot rebase` (or `@dependabot recreate` to start it over).

## 3. Major upgrades (Phase 3)

Order and scope come from `docs/ROADMAP.md` Phase 3: one major per PR, on a
`chore/upgrade-<pkg>-<version>` branch, following `work-step` for the PR mechanics.

1. **Read the official upgrade guide and changelog** for the target version before
   changing anything (laravel.com/docs/<v>/upgrade, inertiajs.com upgrade guide,
   pestphp.com upgrade guide, vitest.dev/guide/migration, vite.dev/guide/migration).
   These versions are newer than training data. Don't upgrade from memory.
2. **List what the guide says changes for this codebase.** Grep for each removed or
   renamed API, and note config keys and middleware that changed.
3. **Bump only that package and its required companions** (for example,
   `inertiajs/inertia-laravel` and `@inertiajs/vue3` together;
   `pestphp/pest` and `pestphp/pest-plugin-laravel` together):
   `composer require <pkg>:^<v> -W` or `npm install <pkg>@^<v>`.
4. **Diff published config against the new defaults**:
   `php artisan vendor:publish --tag=<tag> --force` into a scratch copy, compare,
   and port only what's needed. Don't overwrite this repo's config wholesale.
5. **Regenerate the generated files**: Wayfinder output, and Pest snapshots only when
   the change is understood (`tests/.pest/snapshots`; never blindly update them).
6. **Run the full baseline from section 2** in both Pest modes (parallel and
   `composer test:sequential`), plus the `composer dev` smoke check.
7. **In the PR body:** link the upgrade guide, list each guide item and what it
   needed here (or "not applicable"), and note any deprecations left for later.

Update `CLAUDE.md`'s stack line and this skill when a major lands.

## Watch for

- **Lockfile-only churn.** A PR that rewrites most of `package-lock.json` for a few
  patches usually means a lockfile format or npm version change. Check
  `npm --version` against what generated the lockfile before merging.
- **The ignored optional platform packages** (`@rollup/rollup-*`,
  `@tailwindcss/oxide-*`, `lightningcss-*`) are pinned in `optionalDependencies`
  for Linux and Windows. If a tooling bump breaks CI with a missing native binding,
  update those pins in the same PR.
- **`@types/node` is held at the Node major CI runs (22).** When CI moves to a new
  Node major, update the ignore rule in `dependabot.yml` too.
