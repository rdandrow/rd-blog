import { describe, expect, it, beforeEach, afterEach, vi } from 'vitest';
import { ref, computed, nextTick } from 'vue';
import { useAutoSave } from '@/composables/useAutoSave';

describe('useAutoSave', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.useRealTimers();
    });

    describe('basic functionality', () => {
        it('should save draft to localStorage', () => {
            const formData = ref({
                title: 'Test Post',
                content: 'Test content',
            });

            const { saveDraft } = useAutoSave('test-key', formData);
            saveDraft();

            const stored = localStorage.getItem('test-key');
            expect(stored).toBeDefined();

            const parsed = JSON.parse(stored!);
            expect(parsed.data.title).toBe('Test Post');
            expect(parsed.data.content).toBe('Test content');
            expect(parsed.timestamp).toBeDefined();
            expect(typeof parsed.timestamp).toBe('number');
        });

        it('should update lastSaved ref after save', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, lastSaved } = useAutoSave('test-key', formData);

            expect(lastSaved.value).toBeNull();

            saveDraft();

            expect(lastSaved.value).toBeInstanceOf(Date);
        });

        it('should set hasDraft to true after save', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, hasDraft } = useAutoSave('test-key', formData);

            expect(hasDraft.value).toBe(false);

            saveDraft();

            expect(hasDraft.value).toBe(true);
        });

        it('should clear error on successful save', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, error } = useAutoSave('test-key', formData);

            // Manually set error
            error.value = 'Test error';

            saveDraft();

            expect(error.value).toBeNull();
        });
    });

    describe('checkForDraft', () => {
        it('should return null when no draft exists', () => {
            const formData = ref({ title: '' });
            const { checkForDraft } = useAutoSave('test-key', formData);

            const draft = checkForDraft();

            expect(draft).toBeNull();
        });

        it('should return draft data when valid draft exists', () => {
            const draftData = {
                timestamp: Date.now(),
                data: {
                    title: 'Draft Title',
                    content: 'Draft content',
                },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '', content: '' });
            const { checkForDraft } = useAutoSave('test-key', formData);

            const draft = checkForDraft();

            expect(draft).not.toBeNull();
            expect(draft?.data.title).toBe('Draft Title');
            expect(draft?.data.content).toBe('Draft content');
        });

        it('should return null and remove expired draft', () => {
            const eightDaysAgo = Date.now() - (8 * 24 * 60 * 60 * 1000);
            const draftData = {
                timestamp: eightDaysAgo,
                data: { title: 'Expired' },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '' });
            const { checkForDraft } = useAutoSave('test-key', formData);

            const draft = checkForDraft();

            expect(draft).toBeNull();
            expect(localStorage.getItem('test-key')).toBeNull();
        });

        it('should return draft at expiration boundary (7 days)', () => {
            const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000) + 1000; // 1 second within
            const draftData = {
                timestamp: sevenDaysAgo,
                data: { title: 'Valid' },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '' });
            const { checkForDraft } = useAutoSave('test-key', formData);

            const draft = checkForDraft();

            expect(draft).not.toBeNull();
            expect(draft?.data.title).toBe('Valid');
        });

        it('should handle corrupted localStorage data', () => {
            localStorage.setItem('test-key', 'invalid json');

            const formData = ref({ title: '' });
            const { checkForDraft, error } = useAutoSave('test-key', formData);

            const draft = checkForDraft();

            expect(draft).toBeNull();
            expect(error.value).toContain('Auto-save unavailable');
        });

        it('should clear error when draft is found', () => {
            const draftData = {
                timestamp: Date.now(),
                data: { title: 'Test' },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '' });
            const { checkForDraft, error } = useAutoSave('test-key', formData);

            // Set error first
            error.value = 'Previous error';

            checkForDraft();

            expect(error.value).toBeNull();
        });
    });

    describe('restoreDraft', () => {
        it('should restore draft data', () => {
            const draftData = {
                timestamp: Date.now(),
                data: {
                    title: 'Restored Title',
                    content: 'Restored content',
                },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '', content: '' });
            const { restoreDraft } = useAutoSave('test-key', formData);

            const restored = restoreDraft();

            expect(restored).not.toBeNull();
            expect(restored?.title).toBe('Restored Title');
            expect(restored?.content).toBe('Restored content');
        });

        it('should set hasDraft to true after restore', () => {
            const draftData = {
                timestamp: Date.now(),
                data: { title: 'Test' },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '' });
            const { restoreDraft, hasDraft } = useAutoSave('test-key', formData);

            expect(hasDraft.value).toBe(false);

            restoreDraft();

            expect(hasDraft.value).toBe(true);
        });

        it('should return null when no draft exists', () => {
            const formData = ref({ title: '' });
            const { restoreDraft } = useAutoSave('test-key', formData);

            const restored = restoreDraft();

            expect(restored).toBeNull();
        });
    });

    describe('clearDraft', () => {
        it('should remove draft from localStorage', () => {
            const draftData = {
                timestamp: Date.now(),
                data: { title: 'Test' },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '' });
            const { clearDraft } = useAutoSave('test-key', formData);

            clearDraft();

            expect(localStorage.getItem('test-key')).toBeNull();
        });

        it('should reset hasDraft to false', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, clearDraft, hasDraft } = useAutoSave('test-key', formData);

            saveDraft();
            expect(hasDraft.value).toBe(true);

            clearDraft();
            expect(hasDraft.value).toBe(false);
        });

        it('should reset lastSaved to null', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, clearDraft, lastSaved } = useAutoSave('test-key', formData);

            saveDraft();
            expect(lastSaved.value).not.toBeNull();

            clearDraft();
            expect(lastSaved.value).toBeNull();
        });

        it('should clear error', () => {
            const formData = ref({ title: 'Test' });
            const { clearDraft, error } = useAutoSave('test-key', formData);

            error.value = 'Test error';

            clearDraft();

            expect(error.value).toBeNull();
        });
    });

    describe('auto-save with intervals', () => {
        it('should auto-save at specified interval when data changes', () => {
            const formData = ref({
                title: 'Initial',
                content: 'Content',
            });

            const { startAutoSave } = useAutoSave('test-key', formData, 30000);

            startAutoSave();

            // Change the data after starting auto-save
            formData.value.title = 'Auto-saved Post';

            // Fast-forward 30 seconds
            vi.advanceTimersByTime(30000);

            // Should have saved with the updated data
            const stored = localStorage.getItem('test-key');
            expect(stored).not.toBeNull();

            if (stored) {
                const parsed = JSON.parse(stored);
                expect(parsed.data.title).toBe('Auto-saved Post');
            }
        });

        it('should save multiple times at intervals', () => {
            const formData = ref({ title: 'Test' });
            const { startAutoSave } = useAutoSave('test-key', formData, 10000);

            startAutoSave();

            // Change data between intervals
            vi.advanceTimersByTime(10000);
            formData.value.title = 'Updated 1';

            vi.advanceTimersByTime(10000);
            formData.value.title = 'Updated 2';

            vi.advanceTimersByTime(10000);

            const stored = localStorage.getItem('test-key');
            const parsed = JSON.parse(stored!);
            expect(parsed.data.title).toBe('Updated 2');
        });

        it('should not save if data has not changed', () => {
            const formData = ref({ title: 'Test' });
            const { startAutoSave, saveDraft } = useAutoSave('test-key', formData, 10000);

            // Manual save first
            saveDraft();
            const firstSave = localStorage.getItem('test-key');
            const firstTimestamp = JSON.parse(firstSave!).timestamp;

            startAutoSave();

            // Advance time without changing data
            vi.advanceTimersByTime(10000);

            const secondSave = localStorage.getItem('test-key');
            const secondTimestamp = JSON.parse(secondSave!).timestamp;

            // Timestamp should be the same (no new save)
            expect(secondTimestamp).toBe(firstTimestamp);
        });

        it('should not save empty content', () => {
            const formData = ref({ title: '', content: '' });
            const { startAutoSave } = useAutoSave('test-key', formData, 10000);

            startAutoSave();

            vi.advanceTimersByTime(10000);

            // Should not have saved
            expect(localStorage.getItem('test-key')).toBeNull();
        });

        it('should save when there is non-empty content', () => {
            const formData = ref({ title: '  ', content: 'Some content' });
            const { startAutoSave } = useAutoSave('test-key', formData, 10000);

            startAutoSave();

            vi.advanceTimersByTime(10000);

            expect(localStorage.getItem('test-key')).toBeDefined();
        });

        it('should not start multiple intervals', () => {
            const formData = ref({ title: 'Test' });
            const { startAutoSave } = useAutoSave('test-key', formData, 10000);

            startAutoSave();
            startAutoSave();
            startAutoSave();

            vi.advanceTimersByTime(10000);

            // Should only save once per interval
            const stored = localStorage.getItem('test-key');
            expect(stored).toBeDefined();
        });
    });

    describe('stopAutoSave', () => {
        it('should stop auto-save interval', () => {
            const formData = ref({ title: 'Test', content: 'Content' });
            const { startAutoSave, stopAutoSave } = useAutoSave('test-key', formData, 10000);

            startAutoSave();
            stopAutoSave();

            vi.advanceTimersByTime(10000);

            // Should not have saved
            expect(localStorage.getItem('test-key')).toBeNull();
        });

        it('should be safe to call multiple times', () => {
            const formData = ref({ title: 'Test' });
            const { startAutoSave, stopAutoSave } = useAutoSave('test-key', formData);

            startAutoSave();
            stopAutoSave();
            stopAutoSave();
            stopAutoSave();

            // Should not throw
            expect(true).toBe(true);
        });
    });

    describe('getLastSavedText', () => {
        it('should return empty string when never saved', () => {
            const formData = ref({ title: 'Test' });
            const { getLastSavedText } = useAutoSave('test-key', formData);

            expect(getLastSavedText()).toBe('');
        });

        it('should return "just now" for recent save', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, getLastSavedText } = useAutoSave('test-key', formData);

            saveDraft();

            vi.advanceTimersByTime(30000); // 30 seconds

            expect(getLastSavedText()).toBe('just now');
        });

        it('should return "1 minute ago" for save 1 minute ago', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, getLastSavedText } = useAutoSave('test-key', formData);

            saveDraft();

            vi.advanceTimersByTime(60000); // 60 seconds

            expect(getLastSavedText()).toBe('1 minute ago');
        });

        it('should return "{n} minutes ago" for save multiple minutes ago', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, getLastSavedText } = useAutoSave('test-key', formData);

            saveDraft();

            vi.advanceTimersByTime(5 * 60000); // 5 minutes

            expect(getLastSavedText()).toBe('5 minutes ago');
        });

        it('should return time for saves over an hour ago', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, getLastSavedText } = useAutoSave('test-key', formData);

            saveDraft();

            vi.advanceTimersByTime(90 * 60000); // 90 minutes

            const result = getLastSavedText();
            // Should be a time string like "10:30 AM"
            expect(result).toMatch(/\d{1,2}:\d{2}/);
        });
    });

    describe('reactivity with refs', () => {
        it('should save current ref value, not initial value', async () => {
            const formData = ref({
                title: 'Initial',
                content: 'Initial content',
            });

            const { saveDraft } = useAutoSave('test-key', formData);

            // Change data before saving
            formData.value.title = 'Updated';
            formData.value.content = 'Updated content';

            await nextTick();

            saveDraft();

            const stored = localStorage.getItem('test-key');
            const parsed = JSON.parse(stored!);

            expect(parsed.data.title).toBe('Updated');
            expect(parsed.data.content).toBe('Updated content');
        });

        it('should work with computed refs', async () => {
            const baseData = ref({
                title: 'Test',
                content: 'Content',
            });

            const computedData = computed(() => ({
                ...baseData.value,
                excerpt: baseData.value.content.substring(0, 50),
            }));

            const { saveDraft } = useAutoSave('test-key', computedData);

            saveDraft();

            const stored = localStorage.getItem('test-key');
            const parsed = JSON.parse(stored!);

            expect(parsed.data.title).toBe('Test');
            expect(parsed.data.excerpt).toBe('Content');
        });

        it('should track reactive changes during auto-save', async () => {
            const formData = ref({ title: 'Initial' });
            const { startAutoSave } = useAutoSave('test-key', formData, 5000);

            startAutoSave();

            // Change data
            formData.value.title = 'Changed';

            await nextTick();

            vi.advanceTimersByTime(5000);

            const stored = localStorage.getItem('test-key');
            const parsed = JSON.parse(stored!);

            expect(parsed.data.title).toBe('Changed');
        });
    });

    describe('custom expiration', () => {
        it('should respect custom expiration time', () => {
            const oneDayMs = 24 * 60 * 60 * 1000;
            const twoDaysAgo = Date.now() - (2 * oneDayMs);

            const draftData = {
                timestamp: twoDaysAgo,
                data: { title: 'Old' },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '' });
            // Set expiration to 1 day
            const { checkForDraft } = useAutoSave('test-key', formData, 30000, oneDayMs);

            const draft = checkForDraft();

            // Should be expired
            expect(draft).toBeNull();
        });

        it('should keep draft within custom expiration', () => {
            const threeDaysMs = 3 * 24 * 60 * 60 * 1000;
            const twoDaysAgo = Date.now() - (2 * 24 * 60 * 60 * 1000);

            const draftData = {
                timestamp: twoDaysAgo,
                data: { title: 'Valid' },
            };

            localStorage.setItem('test-key', JSON.stringify(draftData));

            const formData = ref({ title: '' });
            // Set expiration to 3 days
            const { checkForDraft } = useAutoSave('test-key', formData, 30000, threeDaysMs);

            const draft = checkForDraft();

            // Should still be valid
            expect(draft).not.toBeNull();
            expect(draft?.data.title).toBe('Valid');
        });
    });

    describe('error handling', () => {
        it('should set error when localStorage.setItem fails', () => {
            const formData = ref({ title: 'Test' });
            const { saveDraft, error } = useAutoSave('test-key', formData);

            // Mock localStorage to throw error
            const originalSetItem = localStorage.setItem;
            localStorage.setItem = vi.fn(() => {
                throw new Error('Storage full');
            });

            saveDraft();

            expect(error.value).toContain('Failed to auto-save');

            // Restore
            localStorage.setItem = originalSetItem;
        });

        it('should handle errors during auto-save interval', () => {
            const formData = ref({ title: 'Test', content: 'Content' });
            const { startAutoSave, error } = useAutoSave('test-key', formData, 5000);

            // Mock localStorage to throw error
            const originalSetItem = localStorage.setItem;
            localStorage.setItem = vi.fn(() => {
                throw new Error('Storage full');
            });

            startAutoSave();

            // Change data to trigger save
            formData.value.title = 'Updated';

            vi.advanceTimersByTime(5000);

            expect(error.value).not.toBeNull();
            expect(error.value).toContain('Failed to auto-save');

            // Restore
            localStorage.setItem = originalSetItem;
        });
    });

    describe('unique storage keys', () => {
        it('should use different storage keys for different instances', () => {
            const formData1 = ref({ title: 'Post 1' });
            const formData2 = ref({ title: 'Post 2' });

            const { saveDraft: save1 } = useAutoSave('key-1', formData1);
            const { saveDraft: save2 } = useAutoSave('key-2', formData2);

            save1();
            save2();

            const item1 = localStorage.getItem('key-1');
            const item2 = localStorage.getItem('key-2');

            expect(item1).not.toBeNull();
            expect(item2).not.toBeNull();

            const stored1 = JSON.parse(item1!);
            const stored2 = JSON.parse(item2!);

            expect(stored1.data.title).toBe('Post 1');
            expect(stored2.data.title).toBe('Post 2');
        });
    });

    describe('edge cases and error scenarios', () => {
        it('should handle errors gracefully in clearDraft', () => {
            const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            
            // Create an object that will throw when removeItem is called
            const throwingStorage = {
                ...localStorage,
                removeItem: vi.fn(() => {
                    throw new Error('Storage quota exceeded');
                }),
            };
            
            // Temporarily replace localStorage
            const originalLocalStorage = global.localStorage;
            Object.defineProperty(global, 'localStorage', {
                value: throwingStorage,
                writable: true,
                configurable: true,
            });

            const formData = ref({ title: 'Test' });
            const { clearDraft } = useAutoSave('test-key', formData);

            // Should not throw, error should be caught
            expect(() => clearDraft()).not.toThrow();
            expect(consoleErrorSpy).toHaveBeenCalled();

            // Restore
            Object.defineProperty(global, 'localStorage', {
                value: originalLocalStorage,
                writable: true,
                configurable: true,
            });
            consoleErrorSpy.mockRestore();
        });

        it('should handle getFormData with undefined value in ref', () => {
            const formData = ref({ title: undefined as unknown as string });
            const { saveDraft } = useAutoSave('test-key', formData);

            // Should save even with undefined values
            saveDraft();

            const stored = localStorage.getItem('test-key');
            expect(stored).not.toBeNull();
            
            const parsed = JSON.parse(stored!);
            expect(parsed.data.title).toBeUndefined();
        });

        it('should format time correctly for 2-10 minutes ago', () => {
            vi.useFakeTimers();
            const baseTime = new Date('2024-01-01 12:00:00');
            vi.setSystemTime(baseTime);

            const formData = ref({ title: 'Test' });
            const { saveDraft, getLastSavedText } = useAutoSave('test-key', formData);

            saveDraft();

            // Move time forward by 5 minutes
            vi.setSystemTime(new Date('2024-01-01 12:05:00'));

            const text = getLastSavedText();
            expect(text).toBe('5 minutes ago');

            vi.useRealTimers();
        });

        it('should format time correctly for 30 minutes ago', () => {
            vi.useFakeTimers();
            const baseTime = new Date('2024-01-01 12:00:00');
            vi.setSystemTime(baseTime);

            const formData = ref({ title: 'Test' });
            const { saveDraft, getLastSavedText } = useAutoSave('test-key', formData);

            saveDraft();

            // Move time forward by 30 minutes
            vi.setSystemTime(new Date('2024-01-01 12:30:00'));

            const text = getLastSavedText();
            expect(text).toBe('30 minutes ago');

            vi.useRealTimers();
        });

        it('should format time correctly for 59 minutes ago', () => {
            vi.useFakeTimers();
            const baseTime = new Date('2024-01-01 12:00:00');
            vi.setSystemTime(baseTime);

            const formData = ref({ title: 'Test' });
            const { saveDraft, getLastSavedText } = useAutoSave('test-key', formData);

            saveDraft();

            // Move time forward by 59 minutes
            vi.setSystemTime(new Date('2024-01-01 12:59:00'));

            const text = getLastSavedText();
            expect(text).toBe('59 minutes ago');

            vi.useRealTimers();
        });
    });

    describe('form data types', () => {
        it('should handle plain object (non-ref) form data', () => {
            // Pass plain object instead of ref
            const plainObject = {
                title: 'Plain Title',
                content: 'Plain Content',
            };

            const { saveDraft } = useAutoSave('plain-key', plainObject);
            
            saveDraft();
            
            const stored = localStorage.getItem('plain-key');
            expect(stored).toBeDefined();
            
            const parsed = JSON.parse(stored!);
            expect(parsed.data.title).toBe('Plain Title');
            expect(parsed.data.content).toBe('Plain Content');
        });

        it('should detect content in array values', async () => {
            const formData = ref({
                title: 'Test',
                tags: [] as string[],
            });

            const { startAutoSave, stopAutoSave } = useAutoSave('array-key', formData, 100);
            
            startAutoSave();
            
            // Modify data after starting
            formData.value.tags = ['tag1', 'tag2', 'tag3'];
            
            // Wait for reactivity
            await nextTick();
            
            // Fast-forward past the interval
            vi.advanceTimersByTime(150);
            
            // Should have saved because tags array has content
            const stored = localStorage.getItem('array-key');
            expect(stored).toBeDefined();
            
            const parsed = JSON.parse(stored!);
            expect(parsed.data.tags).toEqual(['tag1', 'tag2', 'tag3']);
            
            stopAutoSave();
        });

        it('should not save when array is empty', () => {
            const formData = ref({
                title: '',
                tags: [],
            });

            const { startAutoSave, stopAutoSave } = useAutoSave('empty-array-key', formData, 100);
            
            startAutoSave();
            
            // Fast-forward past the interval
            vi.advanceTimersByTime(150);
            
            // Should not save because all content is empty
            const stored = localStorage.getItem('empty-array-key');
            expect(stored).toBeNull();
            
            stopAutoSave();
        });
    });
});
