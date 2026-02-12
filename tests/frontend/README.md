# Frontend Tests

This directory contains frontend tests using Vitest, @vue/test-utils, and Testing Library.

## Structure

```
tests/frontend/
├── setup.ts              # Test environment setup (localStorage, Inertia mocks, etc.)
├── setup.test.ts         # Verify test setup works correctly
├── composables/          # Tests for Vue composables
└── components/           # Tests for Vue components
```

## Running Tests

```bash
# Run tests in watch mode (development)
npm test

# Run tests once (CI)
npm run test:run

# Run tests with UI
npm run test:ui

# Run tests with coverage report
npm run test:coverage
```

## Coverage Targets

- **Composables & Utils**: 95-100%
- **Admin Features**: 90-95%
- **Auth & Security**: 90-95%
- **Shared Components**: 85-90%
- **Public Pages**: 80-85%
- **Overall Project**: 85% minimum, 90% target

## Writing Tests

### Composables

Test composables in isolation:

```typescript
import { describe, it, expect, beforeEach } from 'vitest';
import { useYourComposable } from '@/composables/useYourComposable';
import { ref } from 'vue';

describe('useYourComposable', () => {
    beforeEach(() => {
        // Reset state
    });

    it('should do something', () => {
        const result = useYourComposable();
        expect(result).toBeDefined();
    });
});
```

### Components

Test components using @vue/test-utils:

```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import YourComponent from '@/components/YourComponent.vue';

describe('YourComponent', () => {
    it('should render correctly', () => {
        const wrapper = mount(YourComponent, {
            props: {
                // your props
            },
        });

        expect(wrapper.text()).toContain('Expected text');
    });

    it('should emit events', async () => {
        const wrapper = mount(YourComponent);
        
        await wrapper.find('button').trigger('click');
        
        expect(wrapper.emitted('eventName')).toBeTruthy();
    });
});
```

## Best Practices

- Use `describe()` blocks to group related tests
- Use descriptive test names that explain the behavior
- Follow Arrange-Act-Assert pattern
- Clean up after each test with `beforeEach()` / `afterEach()`
- Test behavior, not implementation
- Mock external dependencies
- Use `vi.useFakeTimers()` for time-dependent tests
- Always restore mocks with `vi.restoreAllMocks()`

## Mocking

### Inertia Router

Already mocked in `setup.ts`. Use like:

```typescript
import { router } from '@inertiajs/vue3';
import { vi } from 'vitest';

// Verify router was called
expect(router.post).toHaveBeenCalledWith('/url', { data: 'value' });
```

### localStorage

Already mocked in `setup.ts`. Use normally:

```typescript
localStorage.setItem('key', 'value');
expect(localStorage.getItem('key')).toBe('value');
```

### Fetch/API calls

Mock globally or per-test:

```typescript
global.fetch = vi.fn().mockResolvedValue({
    ok: true,
    json: async () => ({ success: true }),
});
```

## Debugging

- Use `test.only()` to run a single test
- Use `test.skip()` to skip tests
- Add `console.log()` in tests (they're not suppressed)
- Run with UI: `npm run test:ui` for interactive debugging
- Use debugger: Add `debugger` statement in test or source

## CI Integration

Tests run automatically in CI via `npm run test:run`.
Coverage reports are generated and coverage thresholds are enforced.

## Documentation

See [FRONTEND_TESTING.md](../FRONTEND_TESTING.md) for detailed testing examples.
See [../docs/VITEST_PLAN.md](../docs/VITEST_PLAN.md) for comprehensive Vitest best practices.
