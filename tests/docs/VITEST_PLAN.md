# Vitest Coverage Plan

This plan builds on existing guidance in [tests/FRONTEND_TESTING.md](../tests/FRONTEND_TESTING.md) (note: parts may be outdated) and aligns with Vitest docs: https://vitest.dev/

## Current Test Status

**Total: 1,026 tests passing across 36 test files**

### Completed Coverage:
- ✅ **Composables** (270 tests): useAutoSave (46), useMarkdown (48), useBlogUtils (26), useInitials (17), useAppearance (31), useTwoFactorAuth (31), useBlogPostForm (59), useClickOutside (9), useSearchState (8)
- ✅ **Form Components** (190 tests): Input (32), Button (38), Checkbox (41), Textarea (42), Label (37)
- ✅ **Feedback Components** (44 tests): AlertError (21), ErrorDisplay (23)
- ✅ **Content Components** (83 tests): MarkdownEditor (52), MarkdownRender (31)
- ✅ **Layout Components** (23 tests): AppShell (23)
- ✅ **Display Components** (102 tests): Icon (26), TextLink (26), UserInfo (20), Breadcrumbs (15), Heading (13), HeadingSmall (15), AppLogo (8), AppLogoIcon (13), PlaceholderPattern (22), InputError (10)
- ✅ **Admin Pages** (91 tests): Blog Post Create (46), Blog Post Edit (45)
- ✅ **Auth Pages** (93 tests): Login (26), Register (29), TwoFactorChallenge (38)
- ✅ **Public Pages - BlogPost** (55 tests): BlogPost view (55) - individual blog post with likes, comments, replies, follow functionality
- ✅ **Test Infrastructure**: Setup file (4 tests)
- 🔧 **Public Pages - In Progress** (tests created, mocking needs fix): Blog list (~30 tests), AuthorProfile (~40 tests)

### Next Priority:
- 🔄 **Fix Blog & AuthorProfile Test Mocks**: Fix `$page` and `usePage` mocking patterns (tests created, need mock configuration updates)
- 🔄 **Sidebar Component**: Individual sidebar sub-components (target: 85%+ coverage)
- ⏳ **Settings Pages**: Profile, Password, Appearance

## 1) Compatibility & baseline
- Ensure Node >= 20 and Vite >= 6 (per Vitest Getting Started).
- Prefer a single config: add `test` block to Vite config rather than separate `vitest.config.ts` (Vitest recommends unified config).

## 2) Dependencies
- Install core: `vitest`, `@vitest/ui`, `@vitest/coverage-v8` (default coverage provider).
- Vue test stack: `@vue/test-utils`, `@testing-library/vue`, `@testing-library/user-event`.
- DOM env: `happy-dom` (fast) or `jsdom` (compat).

## 3) Configuration (Vitest best practices)
- **IMPORTANT**: Add `test` block directly to `vite.config.ts` (unified config preferred over separate vitest.config.ts)
- Core test config:
  - `environment: 'happy-dom'` (faster) or `jsdom` (more compatible)
  - `setupFiles: ['./tests/frontend/setup.ts']` (relative path from project root)
  - `globals: true` (optional; enables `describe`, `it`, `expect` without imports)
  - `restoreMocks: true` (auto-restore mocks between tests)
  - `unstubEnvs: true`, `unstubGlobals: true` (auto-cleanup env/globals)
  - `clearMocks: true` (clear mock call history between tests)
- Coverage config (per https://vitest.dev/guide/coverage):
  - `coverage.provider: 'v8'` (recommended: fast, accurate)
  - `coverage.reporter: ['text', 'html', 'json']`
  - `coverage.include: ['resources/js/**/*.{ts,tsx,js,vue}']`
  - `coverage.exclude: ['**/*.d.ts', '**/*.spec.ts', '**/*.test.ts', '**/types/**', '**/index.ts', '**/__tests__/**']`
  - `coverage.all: true` (include all source files, even untested ones)
  - `coverage.thresholds: { lines: 85, functions: 85, branches: 80, statements: 85 }` (enforce minimum coverage)
  - `coverage.perFile: true` (enforce thresholds per file, not just globally)
  - `coverage.thresholdAutoUpdate: false` (prevent automatic threshold lowering)
- Watch options:
  - `watch: { include: ['resources/js/**', 'tests/frontend/**'] }` (optimize file watching)
- Use `vitest run` for CI and `vitest` (or `vitest watch`) for development.

## 4) Folder layout
- `tests/frontend/` for JS tests
  - `tests/frontend/setup.ts`
  - `tests/frontend/composables/`
  - `tests/frontend/components/`

## 5) Test scope (from FRONTEND_TESTING.md)
- Start with composables:
  - `useAutoSave` critical reactivity regression tests.
- Components:
  - `MarkdownEditor` (image upload, drag/drop, table insert, preview).
- Integration:
  - Create/Edit pages auto-save integration.
- CSS table rendering snapshots (optional).

## 6) Mocking guidance (Vitest best practices)
- Use `vi.mock` for modules; remember mocks are hoisted.
- Use `vi.spyOn` for partial mocks (node environment only).
- Reset timers and mocked dates: `vi.useRealTimers()` after `vi.useFakeTimers()`.
- Use `vi.stubEnv` and enable `unstubEnvs` for env test isolation.
- Clear mocks between tests via config or `afterEach(() => vi.restoreAllMocks())`.

## 7) Scripts
- Add scripts:
  - `test`: `vitest`
  - `test:run`: `vitest run`
  - `test:ui`: `vitest --ui`
  - `test:coverage`: `vitest run --coverage`

## 8) CI
- Run `npm run test:run` and `npm run test:coverage`.
- Store HTML coverage artifact (if needed).

## 9) Milestones
1. Config + setup file + first composable tests.
2. Component tests for Markdown editor.
3. Integration tests for Create/Edit flows.
4. Coverage thresholds and CI gating.

## References
- Vitest docs: https://vitest.dev/
- Coverage guide: https://vitest.dev/guide/coverage
- Mocking guide: https://vitest.dev/guide/mocking
- Config guide: https://vitest.dev/config/

## 10) Vitest Best Practices (Exhaustive)

### Test Organization
- **Naming convention**: Use `.test.ts` or `.spec.ts` suffix for test files
- **File location**: Mirror source structure in `tests/frontend/` (e.g., `resources/js/composables/useAutoSave.ts` → `tests/frontend/composables/useAutoSave.test.ts`)
- **Descriptive names**: Use clear `describe()` blocks for features and `it()` for specific behaviors
- **Arrange-Act-Assert**: Structure tests with clear setup, execution, and assertion phases
- **One assertion concept per test**: Focus each test on a single behavior (multiple related assertions OK)
- **Test file structure**: Group related tests with `describe()`, use nested `describe()` for sub-features

### Test Isolation & Cleanup
- **Reset state between tests**: Use `beforeEach()` to reset state, `afterEach()` to cleanup
- **Mock cleanup**: Enable `mockReset: true` or `restoreMocks: true` in config, or manually call `vi.restoreAllMocks()` in `afterEach()`
- **Timer cleanup**: Always call `vi.useRealTimers()` after using `vi.useFakeTimers()`
- **Environment variables**: Use `vi.stubEnv()` with `unstubEnvs: true` config for automatic cleanup
- **Global cleanup**: Use `vi.unstubAllGlobals()` with `unstubGlobals: true` config
- **localStorage/sessionStorage**: Clear storage in `beforeEach()` to prevent test pollution
- **Event listeners**: Remove listeners added during tests to prevent memory leaks
- **DOM cleanup**: Let testing library handle cleanup automatically; avoid manual DOM manipulation

### Mocking Best Practices
- **Module mocking**: Use `vi.mock()` for full module mocks (hoisted to top)
- **Partial mocking**: Use `vi.spyOn()` for individual function mocks
- **Factory functions**: Use `vi.mock('module', () => ({ ... }))` for controlled mock implementation
- **Mock return values**: Use `vi.fn().mockReturnValue()` or `mockResolvedValue()` for async
- **Mock implementation**: Use `mockImplementation()` for complex behavior
- **Mock once**: Use `mockReturnValueOnce()` for sequential return values
- **Verify calls**: Use `expect(mock).toHaveBeenCalledWith(args)` for call verification
- **Call count**: Use `expect(mock).toHaveBeenCalledTimes(n)` for call frequency
- **Reset mocks**: Call `vi.clearAllMocks()` between tests if not using config option
- **Avoid over-mocking**: Mock only external dependencies; test real implementation when possible
- **Mock fetch/axios**: Mock at the global level or use MSW for HTTP mocking
- **Date mocking**: Use `vi.setSystemTime()` instead of mocking Date directly
- **Import mocking**: Use `vi.importActual()` to import real implementation alongside mocks

### Async Testing
- **Use async/await**: Prefer `async/await` over `.then()` chains for clarity
- **Wait for updates**: Use `await nextTick()` for Vue reactivity, `await vi.waitFor()` for conditions
- **Timeout configuration**: Set reasonable timeouts for `waitFor()` (default 1000ms)
- **Avoid arbitrary delays**: Don't use `setTimeout()` in tests; use fake timers or `waitFor()`
- **Test loading states**: Verify loading indicators appear before async operations complete
- **Test error states**: Mock failures to test error handling paths
- **Flush promises**: Use `await flushPromises()` from `@vue/test-utils` to flush promise queue

### Fake Timers
- **Activate fake timers**: Call `vi.useFakeTimers()` in `beforeEach()`
- **Advance time**: Use `vi.advanceTimersByTime(ms)` to move time forward
- **Run all timers**: Use `vi.runAllTimers()` to execute all pending timers
- **Run pending timers**: Use `vi.runOnlyPendingTimers()` for single timer iteration
- **Restore real timers**: Always call `vi.useRealTimers()` in `afterEach()`
- **Date mocking**: Use `vi.setSystemTime(new Date('2024-01-01'))` for fixed dates
- **Clear timers**: Verify timers are cleared properly in component lifecycle

### Vue Component Testing
- **Mount strategy**: Use `mount()` for full component, `shallowMount()` to stub children
- **Props testing**: Test component behavior with various prop combinations
- **Event emission**: Verify events with `wrapper.emitted('eventName')`
- **Slot testing**: Test slot rendering with `wrapper.vm.$slots`
- **v-model**: Test two-way binding with props and events
- **Provide/inject**: Use `global.provide` in mount options to test injection
- **Router mocking**: Mock `useRoute()` and `useRouter()` from vue-router
- **Store mocking**: Mock Pinia stores or Vuex with `vi.mock()`
- **Composables**: Test composables in isolation before integration tests
- **Lifecycle hooks**: Verify mounted/unmounted behavior and cleanup
- **Ref exposure**: Test `defineExpose()` values via `wrapper.vm`
- **Teleport**: Use `attachTo: document.body` in mount options for teleported content

### Composables Testing
- **Test in isolation**: Test composables standalone before component integration
- **Use real Vue reactivity**: Wrap composable calls in component context or use `withSetup()`
- **Test reactive state**: Verify ref/computed updates trigger correctly
- **Test lifecycle**: Verify `onMounted()`, `onUnmounted()` hooks work correctly
- **Test watchers**: Verify `watch()` triggers on correct dependencies
- **Test side effects**: Verify external API calls, storage operations, etc.
- **Test cleanup**: Verify cleanup functions run (e.g., clear intervals, remove listeners)

### Coverage Best Practices
- **Use v8 provider**: Default and recommended coverage provider
- **Set coverage thresholds**: Define minimum coverage percentages in config
- **Include/exclude patterns**: Explicitly define what should be covered
- **Ignore generated code**: Exclude `*.d.ts`, generated files, vendor code
- **Branch coverage**: Aim for high branch coverage, not just line coverage (80%+ recommended)
- **Uncovered code**: Review uncovered lines for missing edge cases
- **Coverage in CI**: Fail CI builds if coverage drops below threshold
- **Coverage reports**: Generate HTML reports for detailed analysis
- **Per-file thresholds**: Use `perFile: true` to enforce thresholds on individual files
- **Don't chase 100%**: Focus on testing critical paths; trivial code may not need coverage
- **Achieving 90%+ coverage**:
  - Start with composables (pure logic, easy to test)
  - Test shared components thoroughly (high reuse = high ROI)
  - Cover all admin workflows (business critical)
  - Test error states and edge cases (often missed)
  - Use coverage reports to find untested branches
  - Add integration tests for complex user flows

### Assertions & Matchers
- **Use specific matchers**: Prefer `toBe()` over `toEqual()` for primitives
- **Object comparison**: Use `toEqual()` for deep object comparison
- **Partial matching**: Use `toMatchObject()` for subset comparison
- **Array assertions**: Use `toContain()`, `toHaveLength()` for arrays
- **String assertions**: Use `toMatch()`, `toContain()` for string patterns
- **Error assertions**: Use `toThrow()`, `toThrowError()` for error testing
- **Truthy/falsy**: Use `toBeTruthy()`, `toBeFalsy()` for boolean coercion
- **Null/undefined**: Use `toBeNull()`, `toBeUndefined()` for explicit checks
- **Custom matchers**: Create custom matchers with `expect.extend()` for reusable assertions
- **Snapshot testing**: Use sparingly; prefer explicit assertions
- **Negative assertions**: Use `.not` modifier for inverse assertions

### Performance & Optimization
- **Run tests in parallel**: Default behavior; use `--no-threads` to disable if needed
- **Use `happy-dom`**: Faster than `jsdom` for most Vue tests
- **Avoid unnecessary mounts**: Test logic in isolation when possible
- **Cache test fixtures**: Reuse test data across tests to reduce setup time
- **Watch mode**: Use `vitest` (watch) during development, `vitest run` in CI
- **Filter tests**: Use `test.only()` during development, remove before commit
- **Skip expensive tests**: Use `test.skip()` for slow tests that aren't always needed
- **Benchmark tests**: Use `bench()` from Vitest for performance testing

### Error Handling & Debugging
- **Descriptive error messages**: Write clear test descriptions that explain failures
- **Debug with UI**: Use `vitest --ui` for interactive debugging
- **Console logs**: Use `console.log()` in tests for debugging (remove after)
- **Test in isolation**: Use `test.only()` to run single test for debugging
- **Check test output**: Read full error messages and stack traces
- **Use debugger**: Add `debugger` statement in tests or source code
- **Retry flaky tests**: Use `test.retry()` for inherently flaky tests (use sparingly)
- **Test timeouts**: Increase timeout for slow tests: `test('...', { timeout: 10000 })`

### TypeScript Integration
- **Type your tests**: Import types for proper IDE support and type checking
- **Mock types**: Use `vi.mocked()` helper for typed mocks
- **Generic helpers**: Type test helpers with generics for reusability
- **Avoid `any`**: Use proper types even in tests
- **Test type errors**: Use `@ts-expect-error` to test invalid types

### CI/CD Integration
- **Run in CI**: Use `vitest run` for non-interactive CI execution
- **Coverage in CI**: Generate and upload coverage reports
- **Fail on coverage drop**: Set `coverage.thresholds` to enforce minimum coverage
- **Cache dependencies**: Cache `node_modules` for faster CI runs
- **Parallel CI**: Split tests across multiple CI jobs if needed
- **Reporter configuration**: Use `json` or `junit` reporter for CI tools

### Accessibility Testing
- **Use `@testing-library/jest-dom`**: Add for accessibility matchers
- **Test ARIA attributes**: Verify `aria-label`, `aria-describedby`, etc.
- **Keyboard navigation**: Test tab order and keyboard interactions
- **Focus management**: Verify focus moves correctly in modals, forms
- **Screen reader text**: Verify `sr-only` content exists and is correct

### General Testing Philosophy
- **Test behavior, not implementation**: Focus on what component does, not how
- **User-centric tests**: Test from user's perspective using Testing Library queries
- **Avoid testing framework details**: Don't test Vue internals (e.g., `$refs`)
- **Test edge cases**: Cover error states, empty states, boundary conditions
- **Maintainable tests**: Write tests that don't break on refactoring
- **Fast feedback**: Keep tests fast; slow tests won't be run
- **Document complex tests**: Add comments explaining non-obvious test logic
- **Test-driven development**: Write tests first when adding new features
- **Refactor with confidence**: Use tests as safety net for refactoring


---

# Comprehensive Frontend Coverage Plan (App‑wide)

This plan reflects current FE behavior and expands beyond auto-save. Use it as a roadmap for adding Vitest coverage.

## 1) Core Pages (Auth & Public)
- [x] Login: prefilled email, focus handling, error rendering, remember‑me toggles (26 tests)
- [x] Register: validation errors, success flow, MFA setup notice, password requirements (29 tests)
- [x] TwoFactorChallenge: PIN input (6 digits), recovery code mode toggle, accessibility, security features (38 tests)
- [ ] Invitation acceptance: password rules, error states, success redirect
- [ ] Forgot/Reset Password: validation errors, success status message
- [ ] Verify Email: status messages, resend flow
- [ ] Public blog list: filters (tags/authors/search), pagination, empty state
- [ ] Blog post view: markdown render, comments/likes/login prompts
- [ ] Author profile: published posts, empty state

## 2) Admin Blog Management
- [x] Create/Edit blog post: auto-save integration, validation, tags, featured image (46 + 45 = 91 tests)
- [ ] Drafts list: pagination, published status, empty state
- [x] Image upload (markdown): success path, error path, rate limit feedback (covered in MarkdownEditor: 52 tests)
- [x] Markdown editor: preview, tables, paste/drag image handling, progress UI (52 tests)

## 3) Settings
- [ ] Profile: update flow, validation errors, avatar display
- [ ] Password: validation, error handling
- [ ] Appearance: theme toggle state
- [ ] Two‑factor: enable/confirm flow, recovery codes

## 4) Shared Components
- [ ] Input/Checkbox/Button: v-model wiring, disabled/processing state
- [ ] Spinner/Alert/ErrorDisplay: conditional rendering and props
- [ ] Sidebar/AppShell: default open state from page props
- [ ] User dropdown/menu: role‑specific items, logout action

## 5) Composables & Utils
- [ ] useAutoSave: reactive data snapshot, TTL expiry, clear/restore
- [ ] useMarkdown: table rendering, inline markdown in cells
- [ ] Blog utils: date/reading time formatting

## 6) Inertia & Routing Integration
- [ ] usePage props typing: auth, flash, sidebar state
- [ ] route helpers: correct URLs for login/register/password/verification
- [ ] form submission: resetOnSuccess behavior

## 7) Accessibility & UX
- [ ] Focus management (login, modals)
- [ ] ARIA labels for key controls
- [ ] Keyboard shortcuts (markdown editor)

## 8) Negative/Edge Cases
- [ ] Rate limiting responses
- [ ] Empty states across lists
- [ ] Unauthorized access redirects
- [ ] Network errors for uploads

## 9) Coverage Targets

### Composables & Utilities (95-100%)
- [x] 100% for `useAutoSave` - Critical data persistence logic (46 tests)
- [x] 100% for `useMarkdown` - Content rendering security (48 tests)
- [x] 95%+ for utility functions (date/time formatting, validators) (useBlogUtils 26, useInitials 17)
- [x] useAppearance - Theme management (31 tests)
- [x] useTwoFactorAuth - 2FA flows (31 tests)
- [x] useBlogPostForm - Form validation (59 tests)
- [x] useClickOutside - Click detection (9 tests)
- [x] useSearchState - Search state (8 tests)
- **Rationale**: Pure logic, no UI complexity, high impact if buggy

### Admin Features (90-95%)
- [x] 95%+ for MarkdownEditor component - Core editing experience (52 tests)
- [x] MarkdownRender component - Content rendering (31 tests)
- [x] 95%+ for blog post Create/Edit pages - Primary admin workflow (46 + 45 = 91 tests)
- [x] 90%+ for image upload/drag-drop - Data loss prevention (covered in MarkdownEditor tests)
- [x] 90%+ for draft auto-save integration - User work preservation (covered in useAutoSave tests)
- [ ] 90%+ for admin user management - Security critical
- **Rationale**: Revenue/productivity critical paths, high user impact

### Shared Components (85-90%)
- [x] 90%+ for form components (Input, Checkbox, Button) - Used everywhere (111 tests)
- [x] 85%+ for layout components (AppShell 23 tests) - Core navigation
- [x] 85%+ for feedback components (AlertError 21, ErrorDisplay 23) - Error handling
- **Rationale**: High reuse factor, bugs affect entire application

### Auth & Security (90-95%)
- [x] 95%+ for Login/Register flows - Security critical (26 + 29 = 55 tests)
- [x] 95%+ for TwoFactorChallenge - Authentication critical (38 tests)
- [ ] 90%+ for invitation acceptance - Security sensitive
- [ ] 85%+ for password reset - Account recovery
- [ ] 85%+ for email verification - Account security
- **Rationale**: Security vulnerabilities have severe consequences

### Public Pages (80-85%)
- [ ] 85%+ for blog post view/rendering - Primary user experience
- [ ] 80%+ for blog list/filters/search - Content discovery
- [ ] 80%+ for comments/likes - User engagement
- [ ] 75%+ for author profiles - Secondary features
- **Rationale**: User-facing, SEO-critical, impacts all visitors

### Settings & Profile (85-90%)
- [ ] 90%+ for profile updates - User data integrity
- [ ] 90%+ for password changes - Security critical
- [ ] 85%+ for appearance/theme - User preference persistence
- [ ] 85%+ for 2FA management - Security feature
- **Rationale**: Personal data management, security settings

### Integration & E2E (Target Scenarios)
- [ ] Complete auth flows (register → verify → 2FA → login)
- [ ] Complete blog post lifecycle (create → draft → publish → edit)
- [ ] Auto-save → browser refresh → restore
- [ ] Image upload → preview → publish → view
- **Rationale**: Catch integration bugs unit tests miss

### Overall Project Targets
- **Minimum acceptable**: 85% overall coverage
- **Target goal**: 90% overall coverage
- **Stretch goal**: 95% overall coverage
- **Critical paths**: 95%+ coverage required

### Implementation Strategy
1. **Phase 1**: Composables (easiest, highest ROI)
2. **Phase 2**: Shared components (wide impact)
3. **Phase 3**: Admin features (business critical)
4. **Phase 4**: Auth flows (security critical)
5. **Phase 5**: Public pages (user-facing)
6. **Phase 6**: Integration tests (E2E scenarios)
