---
name: review-tests
description: Decide whether rd-blog's tests can be trusted. Check that they assert intended behavior rather than current behavior, prove they fail when the code breaks (manual mutation plus Infection), keep coverage through refactors and deletions, and catch flakiness. Use after /code-review on any branch that changes tests, before rewriting or deleting tests, and for the Phase 1 test audit.
---

`/code-review` reads code for bugs, and the linters enforce style. This review answers
something neither can: **would these tests catch a real regression?** It matters more
here than usual, because the suite was written after the code. Run things; don't stop
at reading the diff.

Work in the scratchpad. When you finish, `git status --short` must show only intended
changes.

## 0. Scope

- For a branch: `git diff --stat develop...HEAD -- tests app resources/js`.
- For an audit: the directories the user or roadmap names (for example
  `app/Policies`, `app/Http/Middleware`, `app/Http/Controllers/Auth`).

## 1. Intended vs. current behavior

For each test in scope, read the test name and assertions and ask: is this what the
product should do, or just what the code happens to do? Red flags:

- Authorization tests asserting "everyone can" (`BlogPostPolicyTest::create` is a
  known example).
- Tests that mock the very thing they claim to test.
- Snapshot tests (`tests/.pest/snapshots`) whose snapshots were generated from the
  code rather than from a spec.
- Assertions only on status 200 or "has prop", with nothing about content or side
  effects.
- Security-relevant behavior with no negative test (a guest, a member, or another
  admin trying the action).

List every questionable test with what you think the intended behavior is. Ask the
user when it isn't clear. Don't silently "fix" expectations.

## 2. Can each test fail? (mutation)

**Manual, for new or changed tests.** Break the app behavior the test claims to check,
not the test. Copy the file to the scratchpad first, mutate it, run only that test,
then restore from the copy:

| Test asserts | Mutation |
|---|---|
| forbidden or redirect for a role | delete the policy check or middleware |
| validation error | remove the rule from the FormRequest |
| cache invalidation | comment out the `saved` hook |
| a prop or a rendered element | remove it from the controller or component |
| composable state | change the update logic |

```sh
vendor/bin/pest --filter "<test name>"
npx vitest run tests/frontend/<path> -t "<test name>"
```

A test that still passes under its mutation is vacuous. That's the most important
finding this review can produce.

**Automated, for audits.** Use Pest 4's built-in mutation testing, scoped to one
namespace to keep runs fast:

```sh
vendor/bin/pest --mutate --class='App\Policies' --parallel
vendor/bin/pest --mutate --class='App\Http\Middleware' --covered-only
```

Infection is also installed (`infection.json5`: Unit suite only, `minMsi` 70), but
it's configured with `testFramework: phpunit` while the tests are Pest. Confirm it
actually runs before relying on it; deciding whether to keep it is a Phase 2 item.

Escaped mutants in auth, policy, or middleware code are findings; elsewhere, judge
case by case.

## 3. Coverage kept (refactors and deletions)

When a branch deletes or rewrites tests, list the old ones from a temporary worktree
so the working tree is untouched:

```sh
git worktree add --detach "$SCRATCH/base" develop
ln -s "$PWD/vendor" "$SCRATCH/base/vendor"
(cd "$SCRATCH/base" && vendor/bin/pest --list-tests) > "$SCRATCH/before.txt"
git worktree remove --force "$SCRATCH/base"
vendor/bin/pest --list-tests > "$SCRATCH/after.txt"
```

For Vitest, `npx vitest list` does the same. Map every
removed test to the new test that covers the same behavior, matching on what's
asserted, not on names. Behavior with no new home is a finding, unless the PR drops
it on purpose (for example, Phase 2 pruning) and says why.

## 4. Flakiness

Neither runner has a repeat flag, so loop:

```sh
for i in $(seq 10); do vendor/bin/pest --filter "<changed tests>" || break; done
for i in $(seq 10); do npx vitest run <changed files> || break; done
```

Also run the full backend suite with and without `--parallel`, since isolation bugs
often show up in only one mode.

The usual causes here are reliance on `now()` without `travelTo`, cache state leaking
between tests (`CACHE_STORE=array` is per-process, and `--parallel` splits processes),
ordering assumptions in DB queries without `orderBy`, and seeded Faker data.

## 5. Report

Fix confirmed findings in follow-up commits, or record why one is accepted. Then add
this to the PR body:

```markdown
## Test review (`review-tests`)
- Intent: N tests checked; <questionable tests and resolution>
- Mutation: K tests checked; <mutations>; <vacuous tests found and fixed>
- Infection (if run): <dir>, MSI X%, <escaped mutants and resolution>
- Coverage: <old → new mapping, or "no tests removed">
- Flakiness: <repeat results>
```
