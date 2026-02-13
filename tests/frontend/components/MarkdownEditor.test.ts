import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { nextTick } from 'vue';
import MarkdownEditor from '@/components/MarkdownEditor.vue';

// Mock the useMarkdown composable
vi.mock('@/composables/useMarkdown', () => ({
    useMarkdown: () => ({
        render: vi.fn((markdown: string) => {
            if (markdown.includes('**error**')) {
                return '<div class="text-destructive">Preview Error</div>';
            }
            return `<p>${markdown}</p>`;
        }),
    }),
}));

// Mock fetch for image upload tests
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('MarkdownEditor', () => {
    beforeEach(() => {
        // Reset mocks before each test
        mockFetch.mockReset();
        
        // Mock CSRF token
        const metaTag = document.createElement('meta');
        metaTag.name = 'csrf-token';
        metaTag.content = 'test-token';
        document.head.appendChild(metaTag);
    });

    afterEach(() => {
        // Clean up
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.remove();
        }
    });

    describe('rendering', () => {
        it('should render with default props', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.find('textarea').exists()).toBe(true);
            expect(wrapper.text()).toContain('Content');
        });

        it('should render custom label', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                    label: 'Custom Label',
                },
            });

            expect(wrapper.text()).toContain('Custom Label');
        });

        it('should render custom placeholder', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                    placeholder: 'Custom placeholder...',
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('placeholder')).toBe('Custom placeholder...');
        });

        it('should render with custom rows', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                    rows: 20,
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('rows')).toBe('20');
        });

        it('should render Write and Preview tabs', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('Write');
            expect(wrapper.text()).toContain('Preview');
        });

        it('should show toolbar in write mode', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.find('[role="toolbar"]').exists()).toBe(true);
        });

        it('should show character count', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'Hello World',
                },
            });

            expect(wrapper.text()).toContain('11 / 50,000 characters');
        });

        it('should show word count', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'Hello World Test',
                },
            });

            expect(wrapper.text()).toContain('3 words');
        });

        it('should show singular word for single word', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'Hello',
                },
            });

            expect(wrapper.text()).toContain('1 word');
        });
    });

    describe('tabs', () => {
        it('should start in write mode', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const writeButton = wrapper.findAll('button').find(btn => btn.text() === 'Write');
            expect(writeButton?.classes()).toContain('border-primary');
        });

        it('should switch to preview mode', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '# Hello',
                },
            });

            const previewButton = wrapper.findAll('button').find(btn => btn.text() === 'Preview');
            await previewButton?.trigger('click');

            expect(previewButton?.classes()).toContain('border-primary');
            expect(wrapper.find('[role="toolbar"]').exists()).toBe(false);
        });

        it('should switch back to write mode', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            // Switch to preview
            const previewButton = wrapper.findAll('button').find(btn => btn.text() === 'Preview');
            await previewButton?.trigger('click');

            // Switch back to write
            const writeButton = wrapper.findAll('button').find(btn => btn.text() === 'Write');
            await writeButton?.trigger('click');

            expect(writeButton?.classes()).toContain('border-primary');
            expect(wrapper.find('[role="toolbar"]').exists()).toBe(true);
        });

        it('should render markdown in preview mode', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '# Hello World',
                },
            });

            const previewButton = wrapper.findAll('button').find(btn => btn.text() === 'Preview');
            await previewButton?.trigger('click');
            await flushPromises();

            expect(wrapper.html()).toContain('<p># Hello World</p>');
        });
    });

    describe('v-model', () => {
        it('should update value on input', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                    'onUpdate:modelValue': (e: string) => wrapper.setProps({ modelValue: e }),
                },
            });

            const textarea = wrapper.find('textarea');
            await textarea.setValue('New content');

            expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['New content']);
        });

        it('should update textarea when modelValue prop changes', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'Initial',
                },
            });

            await wrapper.setProps({ modelValue: 'Updated' });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('Updated');
        });
    });

    describe('toolbar buttons', () => {
        it('should render all toolbar buttons', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const toolbar = wrapper.find('[role="toolbar"]');
            const buttons = toolbar.findAll('button[data-toolbar-button]');
            
            expect(buttons.length).toBeGreaterThan(0);
        });

        it('should have H1, H2, H3 buttons', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.html()).toContain('H1');
            expect(wrapper.html()).toContain('H2');
            expect(wrapper.html()).toContain('H3');
        });

        it('should have bold button', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const buttons = wrapper.findAll('button[data-toolbar-button]');
            const boldButton = buttons.find(btn => btn.text().includes('B'));
            expect(boldButton?.exists()).toBe(true);
        });

        it('should have italic button', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const buttons = wrapper.findAll('button[data-toolbar-button]');
            const italicButton = buttons.find(btn => btn.html().includes('italic'));
            expect(italicButton?.exists()).toBe(true);
        });

        it('should have link button', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('Link');
        });

        it('should have list buttons', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('List');
        });

        it('should have table button', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('Table');
        });
    });

    describe('character limits', () => {
        it('should have maxlength attribute', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('maxlength')).toBe('50000');
        });

        it('should show warning color when near limit', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'a'.repeat(45001), // 90.002%
                },
            });

            expect(wrapper.html()).toContain('text-amber-600');
        });

        it('should show error color when very near limit', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'a'.repeat(47501), // 95.002%
                },
            });

            expect(wrapper.html()).toContain('text-destructive');
        });
    });

    describe('word count', () => {
        it('should count words correctly', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'One two three four five',
                },
            });

            expect(wrapper.text()).toContain('5 words');
        });

        it('should handle multiple spaces', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'One    two     three',
                },
            });

            expect(wrapper.text()).toContain('3 words');
        });

        it('should handle newlines', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'One\ntwo\nthree',
                },
            });

            expect(wrapper.text()).toContain('3 words');
        });

        it('should return 0 for empty string', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('0 words');
        });

        it('should return 0 for whitespace only', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '   \n\t  ',
                },
            });

            expect(wrapper.text()).toContain('0 words');
        });
    });

    describe('image upload', () => {
        it('should show upload progress indicator', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            // Simulate image upload
            const file = new File(['fake image'], 'test.jpg', { type: 'image/jpeg' });
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                headers: {
                    get: () => 'application/json',
                },
                json: async () => ({ url: '/storage/test.jpg' }),
            });

            const textarea = wrapper.find('textarea');
            const pasteEvent = new ClipboardEvent('paste', {
                clipboardData: new DataTransfer(),
            });
            
            Object.defineProperty(pasteEvent, 'clipboardData', {
                value: {
                    items: [{
                        type: 'image/jpeg',
                        getAsFile: () => file,
                    }],
                },
            });

            textarea.element.dispatchEvent(pasteEvent);
            await nextTick();

            expect(wrapper.text()).toContain('Uploading');
        });

        it('should handle drag over', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const textarea = wrapper.find('textarea');
            await textarea.trigger('dragover');

            expect(wrapper.html()).toContain('Drop image to upload');
        });

        it('should handle drag leave', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const textarea = wrapper.find('textarea');
            await textarea.trigger('dragover');
            await textarea.trigger('dragleave');

            expect(wrapper.html()).not.toContain('Drop image to upload');
        });
    });

    describe('keyboard shortcuts', () => {
        it('should have keyboard shortcuts help section', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('Keyboard Shortcuts');
        });

        it('should show tables help section', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('Tables');
        });

        it('should show image upload help section', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('Image Upload');
        });

        it('should show code syntax highlighting help', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('Code Syntax Highlighting');
        });
    });

    describe('preview mode', () => {
        it('should show empty state when no content', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const previewButton = wrapper.findAll('button').find(btn => btn.text() === 'Preview');
            await previewButton?.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('Start typing to see your markdown preview');
        });

        it('should not show toolbar in preview mode', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'test',
                },
            });

            const previewButton = wrapper.findAll('button').find(btn => btn.text() === 'Preview');
            await previewButton?.trigger('click');

            expect(wrapper.find('[role="toolbar"]').exists()).toBe(false);
        });

        it('should show prose styling in preview', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: 'test',
                },
            });

            const previewButton = wrapper.findAll('button').find(btn => btn.text() === 'Preview');
            await previewButton?.trigger('click');
            await flushPromises();

            expect(wrapper.html()).toContain('prose');
        });
    });

    describe('accessibility', () => {
        it('should have aria-label for toolbar', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const toolbar = wrapper.find('[role="toolbar"]');
            expect(toolbar.attributes('aria-label')).toBe('Markdown formatting toolbar');
        });

        it('should have title attributes on toolbar buttons', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const buttons = wrapper.findAll('button[data-toolbar-button]');
            buttons.forEach(button => {
                expect(button.attributes('title')).toBeTruthy();
            });
        });

        it('should have aria-label on toolbar buttons', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const buttons = wrapper.findAll('button[data-toolbar-button]');
            buttons.forEach(button => {
                expect(button.attributes('aria-label')).toBeTruthy();
            });
        });
    });

    describe('reactivity', () => {
        it('should update character count when typing', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                    'onUpdate:modelValue': (e: string) => wrapper.setProps({ modelValue: e }),
                },
            });

            await wrapper.setProps({ modelValue: 'Hello' });

            expect(wrapper.text()).toContain('5 / 50,000 characters');
        });

        it('should update word count when typing', async () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                    'onUpdate:modelValue': (e: string) => wrapper.setProps({ modelValue: e }),
                },
            });

            await wrapper.setProps({ modelValue: 'Hello World' });

            expect(wrapper.text()).toContain('2 words');
        });
    });

    describe('edge cases', () => {
        it('should handle empty modelValue', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.find('textarea').exists()).toBe(true);
            expect(wrapper.text()).toContain('0 words');
        });

        it('should handle very long content', () => {
            const longContent = 'a'.repeat(50000);
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: longContent,
                },
            });

            expect(wrapper.text()).toContain('50,000 / 50,000 characters');
        });

        it('should handle unicode characters', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '你好 世界 🎉',
                },
            });

            expect(wrapper.text()).toContain('3 words');
        });

        it('should handle special markdown characters', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '**bold** *italic* `code`',
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('**bold** *italic* `code`');
        });

        it('should handle newlines and formatting', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '# Heading\n\nParagraph\n\n- List item',
                },
            });

            expect(wrapper.text()).toContain('6 words');
        });
    });

    describe('help sections', () => {
        it('should have expandable details elements', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            const details = wrapper.findAll('details');
            expect(details.length).toBeGreaterThan(0);
        });

        it('should show markdown support message', () => {
            const wrapper = mount(MarkdownEditor, {
                props: {
                    modelValue: '',
                },
            });

            expect(wrapper.text()).toContain('Supports Markdown');
        });
    });
});
