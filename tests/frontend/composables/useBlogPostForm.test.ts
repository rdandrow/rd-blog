import { describe, expect, it, beforeEach, vi } from 'vitest';
import { 
    useImageValidation, 
    useFormValidation, 
    useTagManagement 
} from '@/composables/useBlogPostForm';

describe('useBlogPostForm', () => {
    describe('useImageValidation', () => {
        beforeEach(() => {
            vi.clearAllMocks();
        });

        describe('initial state', () => {
            it('should initialize with null errors and preview', () => {
                const { imageError, imagePreview } = useImageValidation();
                
                expect(imageError.value).toBe(null);
                expect(imagePreview.value).toBe(null);
            });
        });

        describe('validateImageFile', () => {
            it('should accept valid JPEG image', () => {
                const { validateImageFile } = useImageValidation();
                const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
                
                expect(() => validateImageFile(file)).not.toThrow();
            });

            it('should accept valid PNG image', () => {
                const { validateImageFile } = useImageValidation();
                const file = new File(['content'], 'test.png', { type: 'image/png' });
                
                expect(() => validateImageFile(file)).not.toThrow();
            });

            it('should accept valid GIF image', () => {
                const { validateImageFile } = useImageValidation();
                const file = new File(['content'], 'test.gif', { type: 'image/gif' });
                
                expect(() => validateImageFile(file)).not.toThrow();
            });

            it('should accept valid WebP image', () => {
                const { validateImageFile } = useImageValidation();
                const file = new File(['content'], 'test.webp', { type: 'image/webp' });
                
                expect(() => validateImageFile(file)).not.toThrow();
            });

            it('should reject invalid file type', () => {
                const { validateImageFile } = useImageValidation();
                const file = new File(['content'], 'test.pdf', { type: 'application/pdf' });
                
                expect(() => validateImageFile(file)).toThrow('Invalid file type');
            });

            it('should reject file larger than 2MB', () => {
                const { validateImageFile } = useImageValidation();
                // Create a file larger than 2MB (2 * 1024 * 1024 bytes)
                const largeContent = new Array(2 * 1024 * 1024 + 1).fill('a').join('');
                const file = new File([largeContent], 'large.jpg', { type: 'image/jpeg' });
                
                expect(() => validateImageFile(file)).toThrow('File size too large');
            });

            it('should accept file exactly at 2MB limit', () => {
                const { validateImageFile } = useImageValidation();
                // Create a file exactly 2MB
                const content = new Array(2 * 1024 * 1024).fill('a').join('');
                const file = new File([content], 'exact.jpg', { type: 'image/jpeg' });
                
                expect(() => validateImageFile(file)).not.toThrow();
            });
        });

        describe('handleFileUpload', () => {
            it('should handle successful file upload', async () => {
                const { handleFileUpload, imageError } = useImageValidation();
                const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
                const mockOnSuccess = vi.fn();
                
                const event = {
                    target: { files: [file], value: '' },
                } as any;
                
                handleFileUpload(event, mockOnSuccess);
                
                // Wait for FileReader to process (needs more time in test environment)
                await new Promise((resolve) => setTimeout(resolve, 50));
                
                expect(mockOnSuccess).toHaveBeenCalled();
                expect(imageError.value).toBe(null);
            });

            it('should handle FileReader error', async () => {
                const { handleFileUpload, imageError } = useImageValidation();
                
                // Create a mock file that will cause an error
                const file = new File([''], 'test.jpg', { type: 'image/jpeg' });
                const mockOnSuccess = vi.fn();
                
                // Mock FileReader to simulate error
                const originalFileReader = global.FileReader;
                global.FileReader = class MockFileReader {
                    readAsDataURL = vi.fn(function(this: any) {
                        // Simulate error after a tick
                        setTimeout(() => {
                            if (this.onerror) this.onerror(new Event('error'));
                        }, 0);
                    });
                    onload = null;
                    onerror = null;
                } as any;
                
                const event = {
                    target: { files: [file], value: 'test.jpg' },
                } as any;
                
                handleFileUpload(event, mockOnSuccess);
                
                // Wait for error to be triggered
                await new Promise((resolve) => setTimeout(resolve, 10));
                
                expect(imageError.value).toBe('Error reading the selected file. Please try again.');
                expect(event.target.value).toBe('');
                expect(mockOnSuccess).not.toHaveBeenCalled();
                
                global.FileReader = originalFileReader;
            });

            it('should handle invalid file type', () => {
                const { handleFileUpload, imageError, imagePreview } = useImageValidation();
                const file = new File(['content'], 'test.pdf', { type: 'application/pdf' });
                const mockOnSuccess = vi.fn();
                
                const event = {
                    target: { files: [file], value: 'test.pdf' },
                } as any;
                
                handleFileUpload(event, mockOnSuccess);
                
                expect(imageError.value).toContain('Invalid file type');
                expect(imagePreview.value).toBe(null);
                expect(event.target.value).toBe('');
                expect(mockOnSuccess).not.toHaveBeenCalled();
            });

            it('should handle missing file', () => {
                const { handleFileUpload, imageError } = useImageValidation();
                const mockOnSuccess = vi.fn();
                
                const event = {
                    target: { files: [], value: '' },
                } as any;
                
                handleFileUpload(event, mockOnSuccess);
                
                expect(imageError.value).toBe(null);
                expect(mockOnSuccess).not.toHaveBeenCalled();
            });

            it('should clear previous errors on new upload', async () => {
                const { handleFileUpload, imageError } = useImageValidation();
                const mockOnSuccess = vi.fn();
                
                // First upload with invalid file
                const invalidFile = new File(['content'], 'test.pdf', { type: 'application/pdf' });
                const event1 = {
                    target: { files: [invalidFile], value: 'test.pdf' },
                } as any;
                
                handleFileUpload(event1, mockOnSuccess);
                expect(imageError.value).toContain('Invalid file type');
                
                // Second upload with valid file
                const validFile = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
                const event2 = {
                    target: { files: [validFile], value: '' },
                } as any;
                
                handleFileUpload(event2, mockOnSuccess);
                
                // Error should be cleared immediately when starting new upload
                expect(imageError.value).toBe(null);
                
                // Wait for file to be read
                await new Promise((resolve) => setTimeout(resolve, 10));
            });

            it('should handle preview creation error', async () => {
                const { handleFileUpload, imageError } = useImageValidation();
                const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
                const mockOnSuccess = vi.fn(() => {
                    throw new Error('Preview error');
                });
                
                const event = {
                    target: { files: [file], value: '' },
                } as any;
                
                handleFileUpload(event, mockOnSuccess);
                
                // Wait for FileReader
                await new Promise((resolve) => setTimeout(resolve, 10));
                
                // Error should be set when onSuccess throws
                expect(imageError.value).toContain('Error creating image preview');
            });

            it('should handle non-Error throw in FileReader error handler', async () => {
                const { handleFileUpload, imageError } = useImageValidation();
                const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
                const mockOnSuccess = vi.fn();
                
                const originalFileReader = global.FileReader;
                global.FileReader = class MockFileReader {
                    onload: any = null;
                    onerror: any = null;
                    
                    readAsDataURL() {
                        // Simulate FileReader error with non-Error object
                        setTimeout(() => {
                            if (this.onerror) {
                                this.onerror('String error' as any);
                            }
                        }, 0);
                    }
                    addEventListener() {}
                    removeEventListener() {}
                } as any;

                const event = {
                    target: { files: [file], value: '' },
                } as any;
                
                handleFileUpload(event, mockOnSuccess);
                
                // Wait for FileReader error
                await new Promise((resolve) => setTimeout(resolve, 10));
                
                // The actual error message from FileReader error handler
                expect(imageError.value).toBe('Error reading the selected file. Please try again.');
                
                global.FileReader = originalFileReader;
            });
        });

        describe('clearImageError', () => {
            it('should clear image error', () => {
                const { handleFileUpload, imageError, clearImageError } = useImageValidation();
                const file = new File(['content'], 'test.pdf', { type: 'application/pdf' });
                const mockOnSuccess = vi.fn();
                
                const event = {
                    target: { files: [file], value: 'test.pdf' },
                } as any;
                
                handleFileUpload(event, mockOnSuccess);
                expect(imageError.value).toContain('Invalid file type');
                
                clearImageError();
                expect(imageError.value).toBe(null);
            });
        });

        describe('resetImage', () => {
            it('should reset image preview and error', () => {
                const { imagePreview, imageError, resetImage } = useImageValidation();
                
                // Set some values
                imagePreview.value = 'data:image/jpeg;base64,abc123';
                imageError.value = 'Some error';
                
                resetImage();
                
                expect(imagePreview.value).toBe(null);
                expect(imageError.value).toBe(null);
            });
        });
    });

    describe('useFormValidation', () => {
        describe('initial state', () => {
            it('should initialize with null error', () => {
                const { generalError } = useFormValidation();
                
                expect(generalError.value).toBe(null);
            });
        });

        describe('validateRequiredFields', () => {
            it('should accept valid form data', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: 'Test Title',
                    excerpt: 'Test Excerpt',
                    content: 'Test Content',
                };
                
                expect(() => validateRequiredFields(form)).not.toThrow();
            });

            it('should reject missing title', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: '',
                    excerpt: 'Test Excerpt',
                    content: 'Test Content',
                };
                
                expect(() => validateRequiredFields(form)).toThrow('Title is required');
            });

            it('should reject whitespace-only title', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: '   ',
                    excerpt: 'Test Excerpt',
                    content: 'Test Content',
                };
                
                expect(() => validateRequiredFields(form)).toThrow('Title is required');
            });

            it('should reject missing excerpt', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: 'Test Title',
                    excerpt: '',
                    content: 'Test Content',
                };
                
                expect(() => validateRequiredFields(form)).toThrow('Excerpt is required');
            });

            it('should reject whitespace-only excerpt', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: 'Test Title',
                    excerpt: '   ',
                    content: 'Test Content',
                };
                
                expect(() => validateRequiredFields(form)).toThrow('Excerpt is required');
            });

            it('should reject missing content', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: 'Test Title',
                    excerpt: 'Test Excerpt',
                    content: '',
                };
                
                expect(() => validateRequiredFields(form)).toThrow('Content is required');
            });

            it('should reject whitespace-only content', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: 'Test Title',
                    excerpt: 'Test Excerpt',
                    content: '   ',
                };
                
                expect(() => validateRequiredFields(form)).toThrow('Content is required');
            });

            it('should handle nested form.data structure', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    data: {
                        title: 'Test Title',
                        excerpt: 'Test Excerpt',
                        content: 'Test Content',
                    },
                };
                
                expect(() => validateRequiredFields(form)).not.toThrow();
            });

            it('should prioritize top-level fields over nested data', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: 'Top Level Title',
                    excerpt: 'Top Level Excerpt',
                    content: 'Top Level Content',
                    data: {
                        title: 'Nested Title',
                        excerpt: 'Nested Excerpt',
                        content: 'Nested Content',
                    },
                };
                
                expect(() => validateRequiredFields(form)).not.toThrow();
            });

            it('should handle null values', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: null,
                    excerpt: null,
                    content: null,
                };
                
                expect(() => validateRequiredFields(form)).toThrow('Title is required');
            });

            it('should handle undefined values', () => {
                const { validateRequiredFields } = useFormValidation();
                const form = {
                    title: undefined,
                    excerpt: undefined,
                    content: undefined,
                };
                
                expect(() => validateRequiredFields(form)).toThrow('Title is required');
            });
        });

        describe('setGeneralError', () => {
            it('should set general error', () => {
                const { generalError, setGeneralError } = useFormValidation();
                
                setGeneralError('Test error message');
                
                expect(generalError.value).toBe('Test error message');
            });

            it('should overwrite previous error', () => {
                const { generalError, setGeneralError } = useFormValidation();
                
                setGeneralError('First error');
                expect(generalError.value).toBe('First error');
                
                setGeneralError('Second error');
                expect(generalError.value).toBe('Second error');
            });
        });

        describe('clearGeneralError', () => {
            it('should clear general error', () => {
                const { generalError, setGeneralError, clearGeneralError } = useFormValidation();
                
                setGeneralError('Test error');
                expect(generalError.value).toBe('Test error');
                
                clearGeneralError();
                expect(generalError.value).toBe(null);
            });
        });
    });

    describe('useTagManagement', () => {
        describe('initial state', () => {
            it('should initialize with null error', () => {
                const { tagError } = useTagManagement();
                
                expect(tagError.value).toBe(null);
            });
        });

        describe('validateTag', () => {
            it('should accept valid tag', () => {
                const { validateTag } = useTagManagement();
                
                expect(() => validateTag('validtag', [])).not.toThrow();
            });

            it('should reject empty tag', () => {
                const { validateTag } = useTagManagement();
                
                expect(() => validateTag('', [])).toThrow('Tag cannot be empty');
            });

            it('should reject tag longer than 50 characters', () => {
                const { validateTag } = useTagManagement();
                const longTag = 'a'.repeat(51);
                
                expect(() => validateTag(longTag, [])).toThrow('Tag must be 50 characters or less');
            });

            it('should accept tag exactly 50 characters', () => {
                const { validateTag } = useTagManagement();
                const tag = 'a'.repeat(50);
                
                expect(() => validateTag(tag, [])).not.toThrow();
            });

            it('should reject duplicate tag', () => {
                const { validateTag } = useTagManagement();
                const existingTags = ['javascript', 'typescript', 'vue'];
                
                expect(() => validateTag('javascript', existingTags)).toThrow('Tag already exists');
            });

            it('should reject tag when maximum limit reached', () => {
                const { validateTag } = useTagManagement();
                const existingTags = Array(10).fill(0).map((_, i) => `tag${i}`);
                
                expect(() => validateTag('newtag', existingTags)).toThrow('Maximum of 10 tags allowed');
            });

            it('should allow 10th tag', () => {
                const { validateTag } = useTagManagement();
                const existingTags = Array(9).fill(0).map((_, i) => `tag${i}`);
                
                expect(() => validateTag('tag9', existingTags)).not.toThrow();
            });
        });

        describe('addTag', () => {
            it('should add valid tag and clear input', () => {
                const { addTag } = useTagManagement();
                const tags: string[] = [];
                
                const result = addTag('javascript', tags);
                
                expect(tags).toEqual(['javascript']);
                expect(result).toBe('');
            });

            it('should trim whitespace from tag', () => {
                const { addTag } = useTagManagement();
                const tags: string[] = [];
                
                const result = addTag('  javascript  ', tags);
                
                expect(tags).toEqual(['javascript']);
                expect(result).toBe('');
            });

            it('should set error for empty tag after trim', () => {
                const { addTag, tagError } = useTagManagement();
                const tags: string[] = [];
                
                const result = addTag('   ', tags);
                
                expect(tags).toEqual([]);
                expect(tagError.value).toBe('Tag cannot be empty');
                expect(result).toBe('   ');
            });

            it('should set error for duplicate tag', () => {
                const { addTag, tagError } = useTagManagement();
                const tags = ['javascript'];
                
                const result = addTag('javascript', tags);
                
                expect(tags).toEqual(['javascript']);
                expect(tagError.value).toBe('Tag already exists');
                expect(result).toBe('javascript');
            });

            it('should set error for long tag', () => {
                const { addTag, tagError } = useTagManagement();
                const tags: string[] = [];
                const longTag = 'a'.repeat(51);
                
                const result = addTag(longTag, tags);
                
                expect(tags).toEqual([]);
                expect(tagError.value).toBe('Tag must be 50 characters or less');
                expect(result).toBe(longTag);
            });

            it('should set error when maximum tags reached', () => {
                const { addTag, tagError } = useTagManagement();
                const tags = Array(10).fill(0).map((_, i) => `tag${i}`);
                
                const result = addTag('newtag', tags);
                
                expect(tags).toHaveLength(10);
                expect(tagError.value).toBe('Maximum of 10 tags allowed');
                expect(result).toBe('newtag');
            });

            it('should clear previous error on successful add', () => {
                const { addTag, tagError } = useTagManagement();
                const tags: string[] = [];
                
                // First add fails
                addTag('', tags);
                expect(tagError.value).toBe('Tag cannot be empty');
                
                // Second add succeeds
                addTag('javascript', tags);
                expect(tagError.value).toBe(null);
            });

            it('should add multiple tags', () => {
                const { addTag } = useTagManagement();
                const tags: string[] = [];
                
                addTag('javascript', tags);
                addTag('typescript', tags);
                addTag('vue', tags);
                
                expect(tags).toEqual(['javascript', 'typescript', 'vue']);
            });
        });

        describe('removeTag', () => {
            it('should remove tag at valid index', () => {
                const { removeTag } = useTagManagement();
                const tags = ['javascript', 'typescript', 'vue'];
                
                removeTag(1, tags);
                
                expect(tags).toEqual(['javascript', 'vue']);
            });

            it('should remove first tag', () => {
                const { removeTag } = useTagManagement();
                const tags = ['javascript', 'typescript', 'vue'];
                
                removeTag(0, tags);
                
                expect(tags).toEqual(['typescript', 'vue']);
            });

            it('should remove last tag', () => {
                const { removeTag } = useTagManagement();
                const tags = ['javascript', 'typescript', 'vue'];
                
                removeTag(2, tags);
                
                expect(tags).toEqual(['javascript', 'typescript']);
            });

            it('should set error for negative index', () => {
                const { removeTag, tagError } = useTagManagement();
                const tags = ['javascript', 'typescript', 'vue'];
                
                removeTag(-1, tags);
                
                expect(tags).toEqual(['javascript', 'typescript', 'vue']);
                expect(tagError.value).toBe('Invalid tag index');
            });

            it('should set error for index >= array length', () => {
                const { removeTag, tagError } = useTagManagement();
                const tags = ['javascript', 'typescript', 'vue'];
                
                removeTag(3, tags);
                
                expect(tags).toEqual(['javascript', 'typescript', 'vue']);
                expect(tagError.value).toBe('Invalid tag index');
            });

            it('should set error for index on empty array', () => {
                const { removeTag, tagError } = useTagManagement();
                const tags: string[] = [];
                
                removeTag(0, tags);
                
                expect(tags).toEqual([]);
                expect(tagError.value).toBe('Invalid tag index');
            });

            it('should clear previous error on successful remove', () => {
                const { removeTag, tagError } = useTagManagement();
                const tags = ['javascript', 'typescript'];
                
                // First remove fails
                removeTag(5, tags);
                expect(tagError.value).toBe('Invalid tag index');
                
                // Second remove succeeds
                removeTag(0, tags);
                expect(tagError.value).toBe(null);
            });
        });

        describe('clearTagError', () => {
            it('should clear tag error', () => {
                const { addTag, tagError, clearTagError } = useTagManagement();
                const tags: string[] = [];
                
                addTag('', tags);
                expect(tagError.value).toBe('Tag cannot be empty');
                
                clearTagError();
                expect(tagError.value).toBe(null);
            });
        });

        describe('reactivity', () => {
            it('should have reactive tagError', () => {
                const { addTag, tagError } = useTagManagement();
                const tags: string[] = [];
                
                const values: (string | null)[] = [];
                values.push(tagError.value);
                
                addTag('', tags);
                values.push(tagError.value);
                
                addTag('javascript', tags);
                values.push(tagError.value);
                
                expect(values).toEqual([null, 'Tag cannot be empty', null]);
            });
        });

        describe('error handling edge cases', () => {
            it('should handle non-Error throw in addTag', () => {
                const { addTag, tagError } = useTagManagement();
                const tagsProxy = new Proxy([] as string[], {
                    get(target, prop) {
                        if (prop === 'push') {
                            return () => {
                                throw 'String error thrown'; // Non-Error throw
                            };
                        }
                        return Reflect.get(target, prop);
                    }
                });

                const result = addTag('test', tagsProxy as string[]);

                expect(tagError.value).toBe('An error occurred while adding the tag.');
                expect(result).toBe('test');
            });

            it('should handle non-Error throw in removeTag', () => {
                const { removeTag, tagError } = useTagManagement();
                const tagsProxy = new Proxy(['tag1'] as string[], {
                    get(target, prop) {
                        if (prop === 'splice') {
                            return () => {
                                throw 'String error thrown'; // Non-Error throw
                            };
                        }
                        return Reflect.get(target, prop);
                    }
                });

                removeTag(0, tagsProxy as string[]);

                expect(tagError.value).toBe('An error occurred while removing the tag.');
            });
        });
    });
});
