# Frontend Testing Documentation

## Overview

This document outlines frontend testing recommendations for the blog admin panel, specifically for the auto-save functionality implemented in the markdown editor.

## Current Testing Coverage

### ✅ Backend Tests (Pest PHP)

The following aspects are covered by PHP tests:

1. **Rate Limiting Tests** (`tests/Feature/Admin/BlogPostRateLimitTest.php`)
   - POST/PUT request throttling (10 requests per minute)
   - Per-user rate limiting isolation
   - Verification that GET/DELETE requests are not rate-limited

2. **Auto-Save Logic Tests** (`tests/Unit/Composables/AutoSaveTest.php`)
   - Storage key generation and uniqueness
   - Draft expiration logic (7-day threshold)
   - Interval configuration (30 seconds)
   - Data structure validation
   - Content detection algorithms
   - Time formatting logic

### ⚠️ Missing Frontend Tests (Requires JavaScript Testing Framework)

The following aspects require a JavaScript testing framework (Vitest recommended) to test properly:

1. **Auto-Save Composable** (`resources/js/composables/useAutoSave.ts`)
   - localStorage read/write operations
   - Draft recovery on mount
   - Automatic save interval triggering
   - Draft clearing on form submission
   - Timestamp management
   - Reactive state updates (`lastSavedText`, `lastSavedAt`)

2. **Component Integration**
   - Create.vue auto-save integration
   - Edit.vue auto-save integration with post-specific keys
   - Draft recovery banner display logic
   - User interactions (Restore/Dismiss buttons)

3. **Markdown Editor Component** (`resources/js/components/MarkdownEditor.vue`)
   - Tab switching behavior (Write/Preview)
   - Validation warning lifecycle
   - Dark mode class application
   - Keyboard shortcuts

## Recommended Setup for Frontend Testing

### Step 1: Install Testing Dependencies

```bash
npm install -D vitest @vue/test-utils jsdom @testing-library/vue @testing-library/user-event happy-dom
```

### Step 2: Create Vitest Configuration

Create `vitest.config.ts` in the project root:

```typescript
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'happy-dom',
    setupFiles: ['./tests/frontend/setup.ts'],
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
    },
  },
});
```

### Step 3: Create Test Setup File

Create `tests/frontend/setup.ts`:

```typescript
import { vi } from 'vitest';

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {};

  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString();
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    clear: () => {
      store = {};
    },
  };
})();

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
});

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn(),
    put: vi.fn(),
    get: vi.fn(),
  },
  usePage: () => ({
    props: {
      auth: {
        user: { id: 1, name: 'Test User' },
      },
    },
  }),
}));
```

### Step 4: Example Test File

Create `tests/frontend/composables/useAutoSave.test.ts`:

```typescript
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { useAutoSave } from '@/composables/useAutoSave';
import { ref } from 'vue';

describe('useAutoSave', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should save draft to localStorage', () => {
    const formData = ref({
      title: 'Test Post',
      content: 'Test content',
      excerpt: 'Test excerpt',
    });

    const { saveDraft } = useAutoSave('test-key', formData);
    saveDraft();

    const stored = localStorage.getItem('test-key');
    expect(stored).toBeDefined();
    
    const parsed = JSON.parse(stored!);
    expect(parsed.data.title).toBe('Test Post');
    expect(parsed.timestamp).toBeDefined();
  });

  it('should restore draft from localStorage', () => {
    const draftData = {
      timestamp: Date.now(),
      data: {
        title: 'Restored Post',
        content: 'Restored content',
        excerpt: 'Restored excerpt',
      },
    };

    localStorage.setItem('test-key', JSON.stringify(draftData));

    const formData = ref({
      title: '',
      content: '',
      excerpt: '',
    });

    const { checkForDraft } = useAutoSave('test-key', formData);
    const hasDraft = checkForDraft();

    expect(hasDraft).toBe(true);
  });

  it('should auto-save at 30 second intervals', () => {
    const formData = ref({
      title: 'Auto-saved Post',
      content: 'Content',
      excerpt: 'Excerpt',
    });

    const { startAutoSave, saveDraft } = useAutoSave('test-key', formData);
    
    // Mock saveDraft to track calls
    const saveSpy = vi.fn(saveDraft);
    
    startAutoSave();

    // Fast-forward 30 seconds
    vi.advanceTimersByTime(30000);
    
    // Should have saved once
    expect(localStorage.getItem('test-key')).toBeDefined();
  });

  it('should expire drafts older than 7 days', () => {
    const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
    const eightDaysAgo = Date.now() - (8 * 24 * 60 * 60 * 1000);

    // Seven-day-old draft (should be valid)
    localStorage.setItem('valid-draft', JSON.stringify({
      timestamp: sevenDaysAgo,
      data: { title: 'Valid' },
    }));

    // Eight-day-old draft (should be expired)
    localStorage.setItem('expired-draft', JSON.stringify({
      timestamp: eightDaysAgo,
      data: { title: 'Expired' },
    }));

    const formData = ref({});
    
    const { checkForDraft: checkValid } = useAutoSave('valid-draft', formData);
    const { checkForDraft: checkExpired } = useAutoSave('expired-draft', formData);

    expect(checkValid()).toBe(true);
    expect(checkExpired()).toBe(false);
  });

  it('should clear draft on demand', () => {
    localStorage.setItem('test-key', JSON.stringify({
      timestamp: Date.now(),
      data: { title: 'Test' },
    }));

    const formData = ref({});
    const { clearDraft } = useAutoSave('test-key', formData);
    
    clearDraft();

    expect(localStorage.getItem('test-key')).toBeNull();
  });
});
```

### Step 5: Update package.json Scripts

Add to `scripts` in `package.json`:

```json
{
  "scripts": {
    "test": "vitest",
    "test:ui": "vitest --ui",
    "test:coverage": "vitest --coverage"
  }
}
```

### Step 6: Run Frontend Tests

```bash
npm test                  # Run tests in watch mode
npm run test:ui          # Run tests with UI
npm run test:coverage    # Run tests with coverage report
```

## Test Coverage Goals

Once frontend testing is set up, aim for:

- **Auto-Save Composable**: 100% coverage (all functions and branches)
- **Component Integration**: 80%+ coverage (critical paths)
- **Markdown Editor**: 70%+ coverage (core functionality)

## Integration with CI/CD

Add to `.github/workflows/test.yml` (if using GitHub Actions):

```yaml
- name: Run Frontend Tests
  run: npm test -- --run

- name: Generate Coverage Report
  run: npm run test:coverage
```

## Additional Testing Considerations

### E2E Testing

For comprehensive testing including browser interactions:

```bash
npm install -D @playwright/test
```

Example E2E test for auto-save:

```typescript
import { test, expect } from '@playwright/test';

test('auto-save persists data after browser refresh', async ({ page }) => {
  await page.goto('/admin/blog-posts/create');
  
  // Type content
  await page.fill('input[name="title"]', 'Auto-saved Title');
  
  // Wait for auto-save (30 seconds)
  await page.waitForTimeout(31000);
  
  // Refresh page
  await page.reload();
  
  // Verify draft recovery banner appears
  await expect(page.getByText('You have an unsaved draft')).toBeVisible();
  
  // Click restore
  await page.click('button:has-text("Restore Draft")');
  
  // Verify content restored
  await expect(page.locator('input[name="title"]')).toHaveValue('Auto-saved Title');
});
```

## Conclusion

While backend PHP tests provide good coverage for server-side logic and business rules, frontend JavaScript testing is essential for:

1. **User Experience**: Ensuring UI interactions work as expected
2. **localStorage Operations**: Validating draft persistence
3. **Reactive State**: Testing Vue component reactivity
4. **Integration**: Verifying composable integration with components

The recommended setup above provides a solid foundation for comprehensive frontend testing of the auto-save functionality and other Vue/TypeScript code.
