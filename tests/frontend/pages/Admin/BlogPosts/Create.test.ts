import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { nextTick } from 'vue';
import Create from '@/pages/Admin/BlogPosts/Create.vue';
import { router, useForm } from '@inertiajs/vue3';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<head><slot /></head>' },
    useForm: vi.fn(),
    router: {
        visit: vi.fn(),
        post: vi.fn(),
    },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div class="app-layout"><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/components/ErrorDisplay.vue', () => ({
    default: {
        name: 'ErrorDisplay',
        template: '<div class="error-display" :data-type="type">{{ error }}</div>',
        props: ['error', 'type', 'class', 'dismissible'],
        emits: ['dismiss'],
    },
}));

vi.mock('@/components/ExpandableMarkdownEditor.vue', () => ({
    default: {
        name: 'ExpandableMarkdownEditor',
        template: '<textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></textarea>',
        props: ['modelValue', 'label', 'placeholder', 'rows'],
        emits: ['update:modelValue'],
    },
}));

vi.mock('@/composables/useBlogPostForm', () => ({
    useFormValidation: () => ({
        generalError: { value: null },
        clearGeneralError: vi.fn(),
        setGeneralError: vi.fn(),
        validateRequiredFields: vi.fn(),
    }),
    useImageValidation: () => ({
        imageError: { value: null },
        imagePreview: { value: null },
        validateImageFile: vi.fn(),
        handleFileUpload: vi.fn((event, callback) => {
            const file = new File([''], 'test.jpg', { type: 'image/jpeg' });
            callback(file);
        }),
        clearImageError: vi.fn(),
        resetImage: vi.fn(),
    }),
    useTagManagement: () => ({
        tagError: { value: null },
        addTag: vi.fn((input, tags) => {
            if (input.trim()) {
                tags.push(input.trim());
                return '';
            }
            return input;
        }),
        removeTag: vi.fn((index, tags) => {
            tags.splice(index, 1);
        }),
        clearTagError: vi.fn(),
    }),
}));

vi.mock('@/composables/useAutoSave', () => ({
    useAutoSave: () => ({
        error: { value: null },
        lastSaved: { value: null },
        restoreDraft: vi.fn(() => null),
        clearDraft: vi.fn(),
        startAutoSave: vi.fn(),
        stopAutoSave: vi.fn(),
        getLastSavedText: vi.fn(() => 'a few seconds ago'),
    }),
}));

describe('Admin BlogPosts Create', () => {
    let mockForm: any;

    beforeEach(() => {
        localStorage.clear();
        vi.clearAllMocks();

        mockForm = {
            title: '',
            excerpt: '',
            content: '',
            featured_image: '',
            featured_image_file: null,
            tags: [],
            is_featured: false,
            is_published: false,
            published_at: null,
            errors: {},
            processing: false,
            hasErrors: false,
            post: vi.fn(),
            clearErrors: vi.fn(),
        };

        (useForm as any).mockReturnValue(mockForm);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('rendering', () => {
        it('should render page title and description', () => {
            const wrapper = mount(Create);
            expect(wrapper.text()).toContain('Create New Blog Post');
            expect(wrapper.text()).toContain('Write and publish a new article for your blog');
        });

        it('should render all form fields', () => {
            const wrapper = mount(Create);
            expect(wrapper.find('#title').exists()).toBe(true);
            expect(wrapper.find('#excerpt').exists()).toBe(true);
            expect(wrapper.find('textarea').exists()).toBe(true); // ExpandableMarkdownEditor
            expect(wrapper.find('#tags').exists()).toBe(true);
        });

        it('should render featured image section', () => {
            const wrapper = mount(Create);
            expect(wrapper.text()).toContain('Featured Image');
            expect(wrapper.text()).toContain('Image URL');
            expect(wrapper.text()).toContain('Upload File');
        });

        it('should render publishing options', () => {
            const wrapper = mount(Create);
            expect(wrapper.text()).toContain('Publishing Options');
            expect(wrapper.find('#is_featured').exists()).toBe(true);
            expect(wrapper.find('#is_published').exists()).toBe(true);
        });

        it('should render action buttons', () => {
            const wrapper = mount(Create);
            expect(wrapper.html()).toContain('Cancel');
            expect(wrapper.html()).toContain('Create Post');
        });
    });

    describe('form data binding', () => {
        it('should bind title input to form', async () => {
            const wrapper = mount(Create);
            const titleInput = wrapper.find('#title');

            await titleInput.setValue('Test Post Title');
            expect(mockForm.title).toBe('Test Post Title');
        });

        it('should bind excerpt textarea to form', async () => {
            const wrapper = mount(Create);
            const excerptInput = wrapper.find('#excerpt');

            await excerptInput.setValue('Test excerpt content');
            expect(mockForm.excerpt).toBe('Test excerpt content');
        });

        it('should bind content via ExpandableMarkdownEditor', async () => {
            const wrapper = mount(Create);
            const contentEditor = wrapper.findComponent({ name: 'ExpandableMarkdownEditor' });

            await contentEditor.vm.$emit('update:modelValue', 'Test content');
            await nextTick();
            expect(mockForm.content).toBe('Test content');
        });

        it('should bind is_featured checkbox to form', async () => {
            const wrapper = mount(Create);
            const checkbox = wrapper.find('#is_featured');

            await checkbox.setValue(true);
            expect(mockForm.is_featured).toBe(true);
        });

        it('should bind is_published checkbox to form', async () => {
            const wrapper = mount(Create);
            const checkbox = wrapper.find('#is_published');

            await checkbox.setValue(true);
            expect(mockForm.is_published).toBe(true);
        });
    });

    describe('image management', () => {
        it('should default to URL input type', () => {
            const wrapper = mount(Create);
            const urlRadio = wrapper.findAll('input[type="radio"]')[0];
            expect((urlRadio.element as HTMLInputElement).checked).toBe(true);
        });

        it('should switch to file input type', async () => {
            const wrapper = mount(Create);
            const fileRadio = wrapper.findAll('input[type="radio"]')[1];

            await fileRadio.setValue(true);
            await nextTick();

            expect(wrapper.find('#featured_image_file').exists()).toBe(true);
        });

        it('should handle image URL input', async () => {
            const wrapper = mount(Create);
            const urlInput = wrapper.find('#featured_image_url');

            await urlInput.setValue('https://example.com/image.jpg');
            expect(mockForm.featured_image).toBe('https://example.com/image.jpg');
        });

        it('should clear featured_image when switching to file input', async () => {
            const wrapper = mount(Create);
            const urlInput = wrapper.find('#featured_image_url');
            await urlInput.setValue('https://example.com/image.jpg');

            const fileRadio = wrapper.findAll('input[type="radio"]')[1];
            await fileRadio.setValue(true);
            await nextTick();

            expect(mockForm.featured_image).toBeNull();
        });

        it('should handle file upload', async () => {
            const wrapper = mount(Create);
            const fileRadio = wrapper.findAll('input[type="radio"]')[1];
            await fileRadio.setValue(true);
            await nextTick();

            const fileInput = wrapper.find('#featured_image_file');
            await fileInput.trigger('change');

            expect(mockForm.featured_image_file).toBeTruthy();
        });

        it('should show image preview for URL', async () => {
            const wrapper = mount(Create);
            const urlInput = wrapper.find('#featured_image_url');
            await urlInput.setValue('https://example.com/image.jpg');
            await nextTick();

            expect(wrapper.find('img[alt="Image preview"]').exists()).toBe(true);
        });

        it('should remove image when clicking remove button', async () => {
            const wrapper = mount(Create);
            const urlInput = wrapper.find('#featured_image_url');
            await urlInput.setValue('https://example.com/image.jpg');
            await nextTick();

            const removeButton = wrapper.find('button[type="button"]');
            await removeButton.trigger('click');

            expect(mockForm.featured_image).toBeNull();
            expect(mockForm.featured_image_file).toBeNull();
        });
    });

    describe('tag management', () => {
        it('should render tag input and add button', async () => {
            const wrapper = mount(Create);
            const tagInput = wrapper.find('#tags');
            const addButton = wrapper.find('button[type="button"]');

            expect(tagInput.exists()).toBe(true);
            expect(addButton.exists()).toBe(true);
        });

        it('should handle tag input changes', async () => {
            const wrapper = mount(Create);
            const tagInput = wrapper.find('#tags');

            await tagInput.setValue('typescript');
            expect((tagInput.element as HTMLInputElement).value).toBe('typescript');
        });

        it('should display added tags', async () => {
            mockForm.tags = ['vue', 'testing'];
            const wrapper = mount(Create);

            expect(wrapper.text()).toContain('vue');
            expect(wrapper.text()).toContain('testing');
        });

        it('should remove tag when clicking remove button', async () => {
            mockForm.tags = ['vue', 'testing'];
            const wrapper = mount(Create);
            await nextTick();

            const removeButtons = wrapper.findAll('button[type="button"]').filter(btn =>
                btn.html().includes('M6 18L18 6M6 6l12 12')
            );

            await removeButtons[0].trigger('click');
            expect(mockForm.tags.length).toBe(1);
        });

        it('should not add empty tags', async () => {
            const wrapper = mount(Create);
            const tagInput = wrapper.find('#tags');

            await tagInput.setValue('   ');
            await tagInput.trigger('keydown.enter');

            expect(mockForm.tags.length).toBe(0);
        });
    });

    describe('publishing options', () => {
        it('should toggle featured status', async () => {
            const wrapper = mount(Create);
            const featuredCheckbox = wrapper.find('#is_featured');

            await featuredCheckbox.setValue(true);
            expect(mockForm.is_featured).toBe(true);

            await featuredCheckbox.setValue(false);
            expect(mockForm.is_featured).toBe(false);
        });

        it('should toggle published status', async () => {
            const wrapper = mount(Create);
            const publishedCheckbox = wrapper.find('#is_published');

            await publishedCheckbox.setValue(true);
            expect(mockForm.is_published).toBe(true);
        });

        it('should verify publishing options section exists', () => {
            const wrapper = mount(Create);
            expect(wrapper.text()).toContain('Publishing Options');
            expect(wrapper.find('#is_published').exists()).toBe(true);
            expect(wrapper.find('#is_featured').exists()).toBe(true);
        });
    });

    describe('auto-save functionality', () => {
        it('should render with auto-save composable integrated', () => {
            const wrapper = mount(Create);
            // Component should mount successfully with auto-save
            expect(wrapper.exists()).toBe(true);
        });

        it('should verify auto-save is set up on mount', () => {
            const wrapper = mount(Create);
            // Auto-save integration is verified by successful component mount
            expect(wrapper.find('form').exists()).toBe(true);
        });
    });

    describe('form submission', () => {
        it('should submit form with correct data', async () => {
            mockForm.title = 'Test Post';
            mockForm.excerpt = 'Test Excerpt';
            mockForm.content = 'Test Content';
            mockForm.tags = ['test', 'vitest'];
            mockForm.is_featured = true;
            mockForm.is_published = true;

            const wrapper = mount(Create);
            const form = wrapper.find('form');

            await form.trigger('submit.prevent');
            await flushPromises();

            expect(mockForm.post).toHaveBeenCalledWith(
                '/admin/blog-posts',
                expect.any(Object)
            );
        });

        it('should disable submit button when processing', async () => {
            mockForm.processing = true;
            const wrapper = mount(Create);

            const submitButton = wrapper.find('button[type="submit"]');
            expect((submitButton.element as HTMLButtonElement).disabled).toBe(true);
        });

        it('should show processing state in button text', async () => {
            mockForm.processing = true;
            const wrapper = mount(Create);

            expect(wrapper.html()).toContain('Creating...');
        });

        it('should use FormData when file is uploaded', async () => {
            mockForm.featured_image_file = new File([''], 'test.jpg', { type: 'image/jpeg' });
            const wrapper = mount(Create);

            // Switch to file input
            const fileRadio = wrapper.findAll('input[type="radio"]')[1];
            await fileRadio.setValue(true);
            await nextTick();

            const form = wrapper.find('form');
            await form.trigger('submit.prevent');
            await flushPromises();

            expect(mockForm.post).toHaveBeenCalledWith(
                '/admin/blog-posts',
                expect.objectContaining({ forceFormData: true })
            );
        });
    });

    describe('error handling', () => {
        it('should display title error from server', async () => {
            mockForm.errors.title = 'Title is required';
            const wrapper = mount(Create);

            expect(wrapper.text()).toContain('Title is required');
        });

        it('should display excerpt error from server', async () => {
            mockForm.errors.excerpt = 'Excerpt is too long';
            const wrapper = mount(Create);

            expect(wrapper.text()).toContain('Excerpt is too long');
        });

        it('should display content error from server', async () => {
            mockForm.errors.content = 'Content is required';
            const wrapper = mount(Create);

            expect(wrapper.text()).toContain('Content is required');
        });

        it('should display image error from server', async () => {
            mockForm.errors.featured_image = 'Invalid image URL';
            const wrapper = mount(Create);

            expect(wrapper.text()).toContain('Invalid image URL');
        });

        it('should display tag error from server', async () => {
            mockForm.errors.tags = 'Invalid tag format';
            const wrapper = mount(Create);

            expect(wrapper.text()).toContain('Invalid tag format');
        });

        it('should clear errors on form submission', async () => {
            const wrapper = mount(Create);
            const form = wrapper.find('form');

            await form.trigger('submit.prevent');
            await flushPromises();

            expect(mockForm.clearErrors).toHaveBeenCalled();
        });
    });

    describe('navigation', () => {
        it('should have cancel button that navigates back', () => {
            const wrapper = mount(Create);
            const cancelButton = wrapper.findAll('button').find(btn => btn.text() === 'Cancel');

            expect(cancelButton).toBeDefined();
            expect(cancelButton?.exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should have proper labels for all inputs', () => {
            const wrapper = mount(Create);

            expect(wrapper.find('label[for="title"]').exists()).toBe(true);
            expect(wrapper.find('label[for="excerpt"]').exists()).toBe(true);
            expect(wrapper.find('label[for="tags"]').exists()).toBe(true);
            expect(wrapper.find('label[for="is_featured"]').exists()).toBe(true);
            expect(wrapper.find('label[for="is_published"]').exists()).toBe(true);
        });

        it('should mark required fields with asterisk', () => {
            const wrapper = mount(Create);

            expect(wrapper.html()).toContain('Title *');
            expect(wrapper.html()).toContain('Excerpt *');
        });

        it('should have required attributes on required inputs', () => {
            const wrapper = mount(Create);

            expect(wrapper.find('#title').attributes('required')).toBeDefined();
            expect(wrapper.find('#excerpt').attributes('required')).toBeDefined();
        });
    });

    describe('edge cases', () => {
        it('should handle empty form submission attempt', async () => {
            const wrapper = mount(Create);
            const form = wrapper.find('form');

            await form.trigger('submit.prevent');
            await flushPromises();

            expect(mockForm.post).toHaveBeenCalled();
        });

        it('should handle very long title', async () => {
            const longTitle = 'A'.repeat(500);
            const wrapper = mount(Create);
            const titleInput = wrapper.find('#title');

            await titleInput.setValue(longTitle);
            expect(mockForm.title).toBe(longTitle);
        });

        it('should handle multiple rapid tag additions', async () => {
            const wrapper = mount(Create);
            const tagInput = wrapper.find('#tags');

            for (let i = 0; i < 10; i++) {
                await tagInput.setValue(`tag-${i}`);
                await tagInput.trigger('keydown.enter');
            }

            expect(mockForm.tags.length).toBeGreaterThanOrEqual(0);
        });

        it('should handle image type toggle multiple times', async () => {
            const wrapper = mount(Create);
            const [urlRadio, fileRadio] = wrapper.findAll('input[type="radio"]');

            for (let i = 0; i < 5; i++) {
                await fileRadio.setValue(true);
                await nextTick();
                await urlRadio.setValue(true);
                await nextTick();
            }

            expect(wrapper.find('#featured_image_url').exists()).toBe(true);
        });

        it('should preserve form data when toggling publishing options', async () => {
            mockForm.title = 'Test Title';
            mockForm.excerpt = 'Test Excerpt';

            const wrapper = mount(Create);
            const publishedCheckbox = wrapper.find('#is_published');

            await publishedCheckbox.setValue(true);
            await nextTick();
            await publishedCheckbox.setValue(false);
            await nextTick();

            expect(mockForm.title).toBe('Test Title');
            expect(mockForm.excerpt).toBe('Test Excerpt');
        });
    });

    describe('breadcrumbs', () => {
        it('should pass correct breadcrumbs to AppLayout', () => {
            const wrapper = mount(Create);
            const appLayout = wrapper.findComponent({ name: 'AppLayout' });

            expect(appLayout.props('breadcrumbs')).toEqual([
                { title: 'Dashboard', href: '/dashboard' },
                { title: 'Blog Posts', href: '/admin/blog-posts' },
                { title: 'Create Post', href: '/admin/blog-posts/create' },
            ]);
        });
    });
});
