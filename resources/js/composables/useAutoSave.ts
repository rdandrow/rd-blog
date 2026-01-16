import { ref, onUnmounted, ComputedRef, Ref } from 'vue';

interface AutoSaveData {
  timestamp: number;
  data: Record<string, any>;
}

type FormDataSource = Record<string, any> | Ref<Record<string, any>> | ComputedRef<Record<string, any>>;

export const useAutoSave = (storageKey: string, formData: FormDataSource, intervalMs: number = 30000) => {
  const lastSaved = ref<Date | null>(null);
  const hasDraft = ref(false);
  let autoSaveInterval: ReturnType<typeof setInterval> | null = null;
  let lastSavedData: string | null = null;

  // Helper to get the current form data value
  const getFormData = (): Record<string, any> => {
    // Handle computed refs and regular refs
    if ('value' in formData) {
      return formData.value;
    }
    // Handle plain objects
    return formData;
  };

  // Check for existing draft on mount
  const checkForDraft = (): AutoSaveData | null => {
    try {
      const savedData = localStorage.getItem(storageKey);
      if (savedData) {
        const parsed: AutoSaveData = JSON.parse(savedData);
        // Check if draft is less than 7 days old
        const sevenDaysAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
        if (parsed.timestamp > sevenDaysAgo) {
          return parsed;
        } else {
          // Remove stale draft
          localStorage.removeItem(storageKey);
        }
      }
    } catch (error) {
      console.error('Error checking for draft:', error);
      localStorage.removeItem(storageKey);
    }
    return null;
  };

  // Save draft to localStorage
  const saveDraft = () => {
    try {
      const currentData = getFormData();
      const dataToSave: AutoSaveData = {
        timestamp: Date.now(),
        data: { ...currentData }
      };
      const serialized = JSON.stringify(dataToSave);
      localStorage.setItem(storageKey, serialized);
      lastSavedData = JSON.stringify(currentData);
      lastSaved.value = new Date();
      hasDraft.value = true;
    } catch (error) {
      console.error('Error saving draft:', error);
    }
  };

  // Restore draft from localStorage
  const restoreDraft = (): Record<string, any> | null => {
    const draft = checkForDraft();
    if (draft) {
      hasDraft.value = true;
      // Update lastSavedData to prevent immediate re-save
      lastSavedData = JSON.stringify(draft.data);
      return draft.data;
    }
    return null;
  };

  // Clear draft from localStorage
  const clearDraft = () => {
    try {
      localStorage.removeItem(storageKey);
      hasDraft.value = false;
      lastSaved.value = null;
      lastSavedData = null;
    } catch (error) {
      console.error('Error clearing draft:', error);
    }
  };

  // Start auto-save interval
  const startAutoSave = () => {
    if (autoSaveInterval) return;

    // Initialize lastSavedData with current form state to prevent immediate save
    if (lastSavedData === null) {
      const currentData = getFormData();
      lastSavedData = JSON.stringify(currentData);
    }

    autoSaveInterval = setInterval(() => {
      const currentData = getFormData();
      // Only save if there's content
      const hasContent = Object.values(currentData).some(value => {
        if (typeof value === 'string') return value.trim().length > 0;
        if (Array.isArray(value)) return value.length > 0;
        return value != null;
      });

      if (hasContent) {
        // Check if data has changed since last save
        const currentSerialized = JSON.stringify(currentData);
        
        // Only save if data is different from last saved state
        if (currentSerialized !== lastSavedData) {
          saveDraft();
        }
      }
    }, intervalMs);
  };

  // Stop auto-save interval
  const stopAutoSave = () => {
    if (autoSaveInterval) {
      clearInterval(autoSaveInterval);
      autoSaveInterval = null;
    }
  };

  // Format last saved time
  const getLastSavedText = (): string => {
    if (!lastSaved.value) return '';
    
    const now = new Date();
    const diff = now.getTime() - lastSaved.value.getTime();
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    
    if (seconds < 60) return 'just now';
    if (minutes === 1) return '1 minute ago';
    if (minutes < 60) return `${minutes} minutes ago`;
    
    return lastSaved.value.toLocaleTimeString(undefined, { 
      hour: '2-digit', 
      minute: '2-digit' 
    });
  };

  // Cleanup on unmount
  onUnmounted(() => {
    stopAutoSave();
  });

  return {
    lastSaved,
    hasDraft,
    checkForDraft,
    saveDraft,
    restoreDraft,
    clearDraft,
    startAutoSave,
    stopAutoSave,
    getLastSavedText
  };
};
