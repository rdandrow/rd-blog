---
name: work-step
description: Execute one item from docs/ROADMAP.md, a bug fix, or a feature in rd-blog. Find the item, branch, get a green baseline, work test-first, verify the new tests can fail, run /code-review, then commit and open a PR. Use when asked to "do the next step", "continue the roadmap", fix a bug, or build a feature.
---

`docs/ROADMAP.md` is the plan; this skill is how to execute one item from it. CLAUDE.md
has the rules.

## 1. Pick the item

- Read `docs/ROADMAP.md`. Take the first unticked item in the lowest unfinished phase,
  unless the user named one.
- Check `git log --oneline -15` and `gh pr list --state all --limit 10`. An open PR
  may already cover it. If an earlier branch isn't merged yet and this item depends on
  it, branch from it and say in the PR that it's stacked.
- If the item needs a product decision (for example, "should admins see each other's
  drafts?"), ask before writing code.

## 2. Baseline

```sh
composer test && npm run test:run && npm run types && npm run lint:check \
  && npm run format:check && vendor/bin/pint --test
```

If the baseline isn't green, fix that first, in its own commit. If Postgres isn't
running, see `run-locally`.

## 3. Branch

From `develop`: `fix/<name>` for bugs, `feature/<name>` for features, and
`chore/<name>` for tooling, pruning, and upgrades.

## 4. Test-first

1. Write the failing test at the level that shows the behavior:
   - HTTP behavior, auth, and redirects: Pest feature test in `tests/Feature`
     (`actingAs`, `get`/`post`, `assertForbidden`, `assertRedirect`).
   - Policies, services, and model logic: Pest unit test in `tests/Unit`.
   - Vue component or composable behavior: Vitest in `tests/frontend`, mirroring the
     `resources/js` path.
2. **Run it and confirm it fails for the right reason**: the assertion, not a typo or
   a missing import. Paste the failure line into your notes for the PR.
3. Implement the smallest change that makes it pass.
4. Remove code the change leaves unused. Don't add new abstractions, caching, indexes,
   or docs the item didn't ask for.

When a fix contradicts an existing test, the test is probably encoding the bug. Change
it deliberately and call that out in the PR. Never delete a test only to get green.

## 5. Verify, including that the tests can fail

- Run the full baseline command from step 2. Everything must be green.
- **Mutation check:** revert just the implementation (copy the file to the scratchpad
  first; never `git checkout -- <file>`, which discards uncommitted work), confirm the
  new test fails, then restore it from the copy. A test that still passes is vacuous;
  fix the test.
- For user-visible changes, run the app (`run-locally`) and look at it, including at
  phone width and in dark mode.
- Tick the item in `docs/ROADMAP.md`.

## 6. Review before pushing

Commit locally, then run the `/code-review` skill on the branch (Skill tool,
`skill: "code-review"`, `args: "high develop...HEAD"`; use `medium` for docs or
config-only changes). Never use `max` or `ultra` unless the user asks. If the branch
changes tests, run `review-tests` next.

Fix confirmed findings in follow-up commits. Rejecting a finding is fine; say why in
the PR.

## 7. Commit and PR

- Small commits. Tests are committed with or before the code they drive. Message: an
  imperative summary line, then a body that explains why, ending with the attribution
  line from the system reminder.
- Push and open the PR only with the user's go-ahead. PR base: `develop`. Body:

  ```markdown
  ## Summary
  Roadmap: <phase / item>. <bullets: what and why>

  ## Test plan
  - [x] Failing test first: <test name> failed with <reason>
  - [x] Mutation check: reverted <change>; <test> failed
  - [x] `composer test`: N passed
  - [x] `npm run test:run`: N passed
  - [x] `npm run types`, `lint:check`, `format:check`, `pint --test`: clean
  - [x] /code-review (<level>): <N findings, how resolved>
  - [x] review-tests (if tests changed): see below

  ## Changed existing tests
  <tests whose expectations changed and why; or "none">
  ```

- Update `CLAUDE.md`, the skills, and the README when the change affects commands,
  conventions, or file layout.
