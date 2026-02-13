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

3. **Image Upload Tests** (`tests/Feature/Admin/BlogPostImageUploadTest.php`)
   - Image upload with valid formats (jpeg, png, gif, webp)
   - File validation (size limits, file types)
   - Authorization and rate limiting (20 uploads/minute)
   - Storage in blog-images directory
   - Unique filename generation
   - Error handling for upload failures

### ⚠️ Missing Frontend Tests (Requires JavaScript Testing Framework)

The following aspects require a JavaScript testing framework (Vitest recommended) to test properly:

1. **Auto-Save Composable** (`resources/js/composables/useAutoSave.ts`)
   - localStorage read/write operations
   - Draft recovery on mount
   - Automatic save interval triggering
   - Draft clearing on form submission
   - Timestamp management
   - Reactive state updates (`lastSavedText`, `lastSavedAt`)
   - **CRITICAL: Form data reactivity** - Ensure current form state is saved, not initial snapshot
     - Fixed bug: Composable now accepts `ComputedRef` or `Ref` for reactive form data
     - Previously saved only the initial form state due to passing `.value` instead of ref
     - Must test that changes to form fields are captured in auto-save

2. **Component Integration**
   - Create.vue auto-save integration
   - Edit.vue auto-save integration with post-specific keys
   - Draft recovery banner display logic
   - User interactions (Restore/Dismiss buttons)
   - **Restore draft applies all saved field values correctly**
   - Draft data persists across page refreshes

3. **Markdown Editor Component** (`resources/js/components/MarkdownEditor.vue`)
   - Tab switching behavior (Write/Preview)
   - Validation warning lifecycle
   - Dark mode class application
   - Keyboard shortcuts

4. **Image Paste/Upload Feature** (`resources/js/components/MarkdownEditor.vue`)
   - Paste event handling for images from clipboard
   - Drag-and-drop event handling (dragover, drop, dragleave)
   - File upload to backend API
   - Progress indicator display
   - Error message display
   - Markdown syntax insertion after upload
   - Cursor positioning after image insertion
   - Drag overlay visual feedback
   - File type and size validation on client-side

5. **Table Insert Feature** (`resources/js/components/MarkdownEditor.vue`)
   - Ctrl+Shift+T keyboard shortcut triggers table insertion
   - Table button click inserts 3x3 table template
   - Template contains proper markdown table syntax
   - Cursor positioned at first header cell after insertion
   - Table template includes header row, separator, and data rows
   - Inserted table has proper line breaks around it
   - Table renders correctly in preview mode
   - Handles insertion at any cursor position in content

6. **Table CSS Styling** (`resources/css/app.css`)
   - Table borders render correctly (border-collapse, 1px solid)
   - Table headers have distinct background color
   - Zebra striping on alternating tbody rows
   - Hover effects on table rows
   - Proper spacing and padding on th/td elements
   - Tables inherit prose typography styles
   - CSS custom properties for theming (--color-muted, --color-border, --color-accent)
   - Responsive table rendering

7. **TODO: Markdown Table Rendering** (`resources/js/composables/useMarkdown.ts`)
   - Removed useless PHP test (`tests/Unit/Composables/MarkdownTableHtmlRenderingTest.php`) that only asserted strings exist
   - Need proper frontend tests that validate markdown-it actually converts table syntax to HTML
   - Test table structure: `<table>`, `<thead>`, `<tbody>`, `<th>`, `<td>` elements
   - Test inline markdown in table cells (bold, italic, code, links)
   - Test special characters preservation in cells
   - Test multiple tables in same content
   - See example tests at bottom of this document

## Recommended Setup for Frontend Testing

### Step 1: Install Testing Dependencies

```bash
npm install -D vitest @vue/test-utils jsdom @testing-library/vue @testing-library/user-event happy-dom
```

### Step 2: Update Vite Configuration

**IMPORTANT**: Add test configuration to your existing `vite.config.ts` (unified config approach):

```typescript
/// <reference types="vitest" />
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  
  // Add test configuration block
  test: {
    globals: true,
    environment: 'happy-dom',
    setupFiles: ['./tests/frontend/setup.ts'],
    restoreMocks: true,
    clearMocks: true,
    unstubEnvs: true,
    unstubGlobals: true,
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'json'],
      include: ['resources/js/**/*.{ts,tsx,js,vue}'],
      exclude: [
        '**/*.d.ts',
        '**/*.spec.ts',
        '**/*.test.ts',
        '**/types/**',
        '**/index.ts',
      ],
      all: true,
      thresholds: {
        lines: 85,
        functions: 85,
        branches: 80,
        statements: 85,
      },
      // Per-file thresholds for critical files (optional)
      perFile: true,
      thresholdAutoUpdate: false,
    },
  },
  
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
    },
  },
});
```

**Note**: If your existing `vite.config.ts` uses a different structure, merge the `test` block into it.

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

### Example Test File: Image Upload Functionality

Create `tests/frontend/components/MarkdownEditor.test.ts`:

```typescript
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import MarkdownEditor from '@/components/MarkdownEditor.vue';
import { nextTick } from 'vue';

describe('MarkdownEditor - Image Upload', () => {
  let fetchMock: any;

  beforeEach(() => {
    // Mock fetch for image uploads
    fetchMock = vi.fn();
    global.fetch = fetchMock;
    
    // Mock CSRF token
    document.head.innerHTML = '<meta name="csrf-token" content="test-token">';
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should upload pasted image from clipboard', async () => {
    fetchMock.mockResolvedValue({
      ok: true,
      json: async () => ({
        success: true,
        url: 'http://example.com/storage/blog-images/test.jpg',
        path: 'blog-images/test.jpg',
      }),
    });

    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    const textarea = wrapper.find('textarea');
    
    // Create a mock clipboard event with an image
    const file = new File(['image'], 'test.png', { type: 'image/png' });
    const dataTransfer = {
      items: [{
        type: 'image/png',
        getAsFile: () => file,
      }],
    };

    const pasteEvent = new ClipboardEvent('paste', {
      clipboardData: dataTransfer as any,
    });

    await textarea.element.dispatchEvent(pasteEvent);
    await nextTick();

    // Wait for upload to complete
    await vi.waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(
        '/admin/blog-posts/upload-image',
        expect.objectContaining({
          method: 'POST',
        })
      );
    });

    // Check that markdown was inserted
    await vi.waitFor(() => {
      expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toContain('![test](');
    });
  });

  it('should upload dropped image file', async () => {
    fetchMock.mockResolvedValue({
      ok: true,
      json: async () => ({
        success: true,
        url: 'http://example.com/storage/blog-images/dropped.jpg',
        path: 'blog-images/dropped.jpg',
      }),
    });

    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    const textarea = wrapper.find('textarea');
    
    // Create a mock drop event with an image
    const file = new File(['image'], 'dropped.jpg', { type: 'image/jpeg' });
    const dataTransfer = {
      files: [file],
    };

    const dropEvent = new DragEvent('drop', {
      dataTransfer: dataTransfer as any,
    });

    await textarea.element.dispatchEvent(dropEvent);
    await nextTick();

    // Wait for upload to complete
    await vi.waitFor(() => {
      expect(fetchMock).toHaveBeenCalled();
      expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toContain('![dropped](');
    });
  });

  it('should show drag overlay when dragging file over textarea', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    const textarea = wrapper.find('textarea');
    
    await textarea.trigger('dragover', {
      dataTransfer: { files: [new File([''], 'test.jpg', { type: 'image/jpeg' })] },
    });

    await nextTick();

    // Check for drag overlay
    expect(wrapper.html()).toContain('Drop image to upload');
  });

  it('should display upload progress indicator', async () => {
    fetchMock.mockImplementation(() => 
      new Promise(resolve => setTimeout(() => resolve({
        ok: true,
        json: async () => ({ success: true, url: 'test.jpg', path: 'test.jpg' }),
      }), 100))
    );

    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    const file = new File(['image'], 'test.png', { type: 'image/png' });
    const pasteEvent = new ClipboardEvent('paste', {
      clipboardData: {
        items: [{
          type: 'image/png',
          getAsFile: () => file,
        }],
      } as any,
    });

    const textarea = wrapper.find('textarea');
    await textarea.element.dispatchEvent(pasteEvent);
    await nextTick();

    // Should show upload progress
    expect(wrapper.text()).toContain('Uploading');
  });

  it('should display error for oversized images', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    // Create a file larger than 2MB
    const largeFile = new File(['x'.repeat(3 * 1024 * 1024)], 'large.jpg', { 
      type: 'image/jpeg' 
    });
    
    Object.defineProperty(largeFile, 'size', { value: 3 * 1024 * 1024 });

    const pasteEvent = new ClipboardEvent('paste', {
      clipboardData: {
        items: [{
          type: 'image/jpeg',
          getAsFile: () => largeFile,
        }],
      } as any,
    });

    const textarea = wrapper.find('textarea');
    await textarea.element.dispatchEvent(pasteEvent);
    await nextTick();

    // Should show error message
    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Image must be less than 2MB');
    });
  });

  it('should display error for non-image files', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    const textFile = new File(['text'], 'document.txt', { type: 'text/plain' });

    const pasteEvent = new ClipboardEvent('paste', {
      clipboardData: {
        items: [{
          type: 'text/plain',
          getAsFile: () => textFile,
        }],
      } as any,
    });

    const textarea = wrapper.find('textarea');
    await textarea.element.dispatchEvent(pasteEvent);
    await nextTick();

    // Should show error message
    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Only image files are allowed');
    });
  });

  it('should handle upload API errors gracefully', async () => {
    fetchMock.mockResolvedValue({
      ok: false,
      json: async () => ({
        error: 'Upload failed',
      }),
    });

    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    const file = new File(['image'], 'test.png', { type: 'image/png' });
    const pasteEvent = new ClipboardEvent('paste', {
      clipboardData: {
        items: [{
          type: 'image/png',
          getAsFile: () => file,
        }],
      } as any,
    });

    const textarea = wrapper.find('textarea');
    await textarea.element.dispatchEvent(pasteEvent);
    await nextTick();

    // Should show error message
    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Error');
    });
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

Once frontend testing is set up, aim for these improved targets:

### Critical Components & Composables
- **useAutoSave Composable**: 100% (data persistence, work preservation)
- **useMarkdown Composable**: 100% (security, content rendering)
- **MarkdownEditor Component**: 95%+ (core editing, image upload, tables)
- **Form Components**: 90%+ (Input, Checkbox, Button, Textarea, Label - used everywhere)
- **Utility Functions**: 95%+ (pure logic, high reuse)

### Admin Features
- **Blog Post Create/Edit Pages**: 95%+ (primary admin workflow)
- **Draft Auto-Save Integration**: 90%+ (prevent data loss)
- **Image Upload Flows**: 90%+ (paste, drag-drop, validation)
- **Admin User Management**: 90%+ (security critical)

### Auth & Security
- **Login/Register Flows**: 95%+ (security critical)
- **Invitation Acceptance**: 90%+ (security sensitive)
- **2FA Flows**: 90%+ (authentication critical)
- **Password Reset**: 85%+ (account recovery)

### Public Features
- **Blog Post View**: 85%+ (primary user experience)
- **Blog List/Filters**: 80%+ (content discovery)
- **Comments/Likes**: 80%+ (user engagement)

### Shared Components
- **Form Components**: 90%+ (Input 32 tests, Button 38 tests, Checkbox 41 tests, Textarea 42 tests, Label 37 tests) ✅
- **Layout Components**: 85%+ (AppShell 23 tests) ✅
- **Feedback Components**: 85%+ (AlertError 21 tests, ErrorDisplay 23 tests) ✅

### Overall Project Target
- **Minimum**: 85% overall coverage
- **Target**: 90% overall coverage  
- **Stretch**: 95% overall coverage
- **Critical paths**: 95%+ required

### Rationale for High Coverage
1. **Admin features** (90-95%): Revenue-critical, prevent data loss, high user impact
2. **Auth/security** (90-95%): Vulnerabilities have severe consequences
3. **Composables** (95-100%): Pure logic, no UI complexity, easy to achieve
4. **Shared components** (85-90%): High reuse, bugs affect entire app
5. **Public features** (80-85%): User-facing, SEO-critical, first impressions

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
### Example: Table Insert Feature Tests

```typescript
import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import MarkdownEditor from '@/components/MarkdownEditor.vue';

describe('MarkdownEditor - Table Insert', () => {
  it('should insert table template when Ctrl+Shift+T is pressed', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: 'Some existing content',
      },
    });

    const textarea = wrapper.find('textarea').element as HTMLTextAreaElement;
    
    // Set cursor position
    textarea.setSelectionRange(23, 23); // After "content"
    
    // Trigger keyboard shortcut
    await textarea.dispatchEvent(new KeyboardEvent('keydown', {
      key: 't',
      ctrlKey: true,
      shiftKey: true,
      bubbles: true,
    }));

    await nextTick();

    // Check that table was inserted
    const updatedValue = wrapper.emitted('update:modelValue')?.[0]?.[0] as string;
    expect(updatedValue).toContain('| Header 1 | Header 2 | Header 3 |');
    expect(updatedValue).toContain('|----------|----------|----------|');
    expect(updatedValue).toContain('| Cell 1   | Cell 2   | Cell 3   |');
    expect(updatedValue).toContain('Some existing content');
  });

  it('should insert table when table button is clicked', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    // Find and click the table button
    const tableButton = wrapper.find('button[aria-label*="Table"]');
    expect(tableButton.exists()).toBe(true);
    
    await tableButton.trigger('click');
    await nextTick();

    // Check that table template was inserted
    const updatedValue = wrapper.emitted('update:modelValue')?.[0]?.[0] as string;
    expect(updatedValue).toMatch(/\| Header 1 \| Header 2 \| Header 3 \|/);
    expect(updatedValue).toMatch(/\|[-\s|]+\|/); // Separator row
    expect(updatedValue).toContain('Cell 1');
  });

  it('should position cursor at first header cell after insertion', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: '',
      },
    });

    const textarea = wrapper.find('textarea').element as HTMLTextAreaElement;
    
    // Trigger table insertion
    const tableButton = wrapper.find('button[aria-label*="Table"]');
    await tableButton.trigger('click');
    await nextTick();

    // Wait a tick for cursor positioning
    await new Promise(resolve => setTimeout(resolve, 50));

    // Check selection
    const selectedText = textarea.value.substring(
      textarea.selectionStart,
      textarea.selectionEnd
    );
    expect(selectedText).toBe('Header 1');
  });

  it('should insert table at cursor position', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: 'Before\n\nAfter',
      },
    });

    const textarea = wrapper.find('textarea').element as HTMLTextAreaElement;
    
    // Position cursor between "Before" and "After"
    textarea.setSelectionRange(7, 7); // After "Before\n"
    
    // Insert table
    const tableButton = wrapper.find('button[aria-label*="Table"]');
    await tableButton.trigger('click');
    await nextTick();

    const updatedValue = wrapper.emitted('update:modelValue')?.[0]?.[0] as string;
    
    // Table should be between "Before" and "After"
    expect(updatedValue.indexOf('Before')).toBeLessThan(
      updatedValue.indexOf('| Header 1')
    );
    expect(updatedValue.indexOf('| Cell 4')).toBeLessThan(
      updatedValue.indexOf('After')
    );
  });

  it('should render table correctly in preview mode', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: `
| Name | Email | Role |
|------|-------|------|
| John | j@e.c | Admin |
| Jane | ja@e.c| User |
`,
      },
    });

    // Switch to preview mode
    const previewButton = wrapper.find('button:has-text("Preview")');
    await previewButton.trigger('click');
    await nextTick();

    const preview = wrapper.find('[v-html]');
    const html = preview.html();
    
    // Should render as HTML table
    expect(html).toContain('<table');
    expect(html).toContain('<thead');
    expect(html).toContain('<tbody');
    expect(html).toContain('<th>Name</th>');
    expect(html).toContain('<td>John</td>');
  });

  it('should handle table with special characters', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: `
| Name | Status |
|------|--------|
| Test | ✓ Done |
`,
      },
    });

    await wrapper.find('button:has-text("Preview")').trigger('click');
    await nextTick();

    const html = wrapper.find('[v-html]').html();
    expect(html).toContain('✓ Done');
  });

  it('should support inline markdown in table cells', async () => {
    const wrapper = mount(MarkdownEditor, {
      props: {
        modelValue: `
| Feature | Status |
|---------|--------|
| **Bold** | *Italic* |
| \`code\` | [link](url) |
`,
      },
    });

    await wrapper.find('button:has-text("Preview")').trigger('click');
    await nextTick();

    const html = wrapper.find('[v-html]').html();
    expect(html).toContain('<strong>Bold</strong>');
    expect(html).toContain('<em>Italic</em>');
    expect(html).toContain('<code>code</code>');
    expect(html).toContain('<a href="url">link</a>');
  });
});
```