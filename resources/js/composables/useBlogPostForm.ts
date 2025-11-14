import { ref } from 'vue';

// Debug flag - set to false for production
const DEBUG_VALIDATION = import.meta.env.DEV || false;

export const useImageValidation = () => {
  const imageError = ref<string | null>(null);
  const imagePreview = ref<string | null>(null);

  const validateImageFile = (file: File): void => {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    const maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!allowedTypes.includes(file.type)) {
      throw new Error('Invalid file type. Please select a JPEG, PNG, JPG, GIF, or WebP image.');
    }
    
    if (file.size > maxSize) {
      throw new Error('File size too large. Please select an image smaller than 2MB.');
    }
  };

  const handleFileUpload = (
    event: Event, 
    onSuccess: (file: File, preview: string) => void
  ) => {
    try {
      imageError.value = null;
      
      const target = event.target as HTMLInputElement;
      const file = target.files?.[0];
      
      if (!file) return;

      validateImageFile(file);
      
      const reader = new FileReader();
      
      reader.onload = (e) => {
        try {
          const preview = e.target?.result as string;
          imagePreview.value = preview;
          onSuccess(file, preview);
        } catch (error) {
          console.error('Error creating image preview:', error);
          imageError.value = 'Error creating image preview. The file will still be uploaded.';
        }
      };
      
      reader.onerror = () => {
        console.error('FileReader error occurred');
        imageError.value = 'Error reading the selected file. Please try again.';
        target.value = '';
      };
      
      reader.readAsDataURL(file);
      
    } catch (error) {
      console.error('File upload error:', error);
      
      const target = event.target as HTMLInputElement;
      target.value = '';
      imagePreview.value = null;
      
      if (error instanceof Error) {
        imageError.value = error.message;
      } else {
        imageError.value = 'An error occurred while uploading the file. Please try again.';
      }
    }
  };

  const clearImageError = () => {
    imageError.value = null;
  };

  const resetImage = () => {
    imagePreview.value = null;
    imageError.value = null;
  };

  return {
    imageError,
    imagePreview,
    validateImageFile,
    handleFileUpload,
    clearImageError,
    resetImage,
  };
};

export const useFormValidation = () => {
  const generalError = ref<string | null>(null);

  const clearGeneralError = () => {
    generalError.value = null;
  };

  const setGeneralError = (error: string) => {
    generalError.value = error;
  };

  const validateRequiredFields = (form: any): void => {
    if (DEBUG_VALIDATION) {
      console.log('Validating form data:', { 
        title: form.title, 
        excerpt: form.excerpt, 
        content: form.content,
        formKeys: Object.keys(form || {})
      });
    }
    
    const titleValue = form.title || form.data?.title || '';
    const excerptValue = form.excerpt || form.data?.excerpt || '';
    const contentValue = form.content || form.data?.content || '';
    
    if (DEBUG_VALIDATION) {
      console.log('Extracted values:', { titleValue, excerptValue, contentValue });
    }
    
    if (!titleValue || !String(titleValue).trim()) {
      throw new Error('Title is required');
    }
    if (!excerptValue || !String(excerptValue).trim()) {
      throw new Error('Excerpt is required');
    }
    if (!contentValue || !String(contentValue).trim()) {
      throw new Error('Content is required');
    }
  };

  return {
    generalError,
    clearGeneralError,
    setGeneralError,
    validateRequiredFields,
  };
};

export const useTagManagement = () => {
  const tagError = ref<string | null>(null);

  const validateTag = (tag: string, existingTags: string[]): void => {
    if (!tag) {
      throw new Error('Tag cannot be empty');
    }
    
    if (tag.length > 50) {
      throw new Error('Tag must be 50 characters or less');
    }
    
    if (existingTags.includes(tag)) {
      throw new Error('Tag already exists');
    }
    
    if (existingTags.length >= 10) {
      throw new Error('Maximum of 10 tags allowed');
    }
  };

  const addTag = (tagInput: string, tags: string[]): string => {
    try {
      tagError.value = null;
      
      const tag = tagInput.trim();
      validateTag(tag, tags);
      
      tags.push(tag);
      return ''; // Clear input
      
    } catch (error) {
      console.error('Tag addition error:', error);
      
      if (error instanceof Error) {
        tagError.value = error.message;
      } else {
        tagError.value = 'An error occurred while adding the tag.';
      }
      return tagInput; // Keep input value
    }
  };

  const removeTag = (index: number, tags: string[]): void => {
    try {
      tagError.value = null;
      
      if (index < 0 || index >= tags.length) {
        throw new Error('Invalid tag index');
      }
      
      tags.splice(index, 1);
      
    } catch (error) {
      console.error('Tag removal error:', error);
      
      if (error instanceof Error) {
        tagError.value = error.message;
      } else {
        tagError.value = 'An error occurred while removing the tag.';
      }
    }
  };

  const clearTagError = () => {
    tagError.value = null;
  };

  return {
    tagError,
    validateTag,
    addTag,
    removeTag,
    clearTagError,
  };
};