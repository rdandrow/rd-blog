<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ErrorDisplay from '@/components/ErrorDisplay.vue';
import ExpandableMarkdownEditor from '@/components/ExpandableMarkdownEditor.vue';
import { 
  useImageValidation, 
  useFormValidation, 
  useTagManagement 
} from '@/composables/useBlogPostForm';
import { useAutoSave } from '@/composables/useAutoSave';

// Debug flag - set to false for production
const DEBUG_VALIDATION = import.meta.env.DEV || false;

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Blog Posts', href: '/admin/blog-posts' },
  { title: 'Create Post', href: '/admin/blog-posts/create' },
];

const form = useForm({
  title: '',
  excerpt: '',
  content: '',
  featured_image: '' as string | null,
  featured_image_file: null as File | null,
  tags: [] as string[],
  is_featured: false,
  is_published: false,
  published_at: null as string | null,
});

// Composables
const { 
  generalError,
  clearGeneralError,
  setGeneralError,
  validateRequiredFields 
} = useFormValidation();
const { 
  imageError, 
  imagePreview, 
  validateImageFile, 
  handleFileUpload: handleImageUpload, 
  clearImageError, 
  resetImage 
} = useImageValidation();
const { 
  tagError, 
  addTag: addTagToList, 
  removeTag: removeTagFromList, 
  clearTagError 
} = useTagManagement();

// Form state
const tagInput = ref('');
const imageInputType = ref<'url' | 'file'>('url');
const showDraftRecovery = ref(false);
const draftData = ref<Record<string, any> | null>(null);

// Auto-save composable
const formDataForAutoSave = computed(() => ({
  title: form.title,
  excerpt: form.excerpt,
  content: form.content,
  featured_image: form.featured_image,
  tags: form.tags,
  is_featured: form.is_featured,
  is_published: form.is_published,
  published_at: form.published_at
}));

const {
  error,
  lastSaved,
  restoreDraft,
  clearDraft,
  startAutoSave,
  stopAutoSave,
  getLastSavedText
} = useAutoSave('blog-post-create-draft', formDataForAutoSave, 30000);

const addTag = () => {
  tagInput.value = addTagToList(tagInput.value, form.tags);
};

const removeTag = (index: number) => {
  removeTagFromList(index, form.tags);
};

const handleTagKeydown = (event: KeyboardEvent) => {
  // Clear error when user starts typing
  if (event.key !== 'Enter') {
    clearTagError();
  }
  
  if (event.key === 'Enter') {
    event.preventDefault();
    addTag();
  }
};

const handleImageTypeChange = (type: 'url' | 'file') => {
  try {
    clearImageError();
    
    if (type !== 'url' && type !== 'file') {
      throw new Error('Invalid image type selection');
    }
    
    imageInputType.value = type;
    
    if (type === 'url') {
      form.featured_image_file = null;
      resetImage();
    } else {
      form.featured_image = null;
    }
    
  } catch (error) {
    console.error('Image type change error:', error);
    
    if (error instanceof Error) {
      imageError.value = error.message;
    } else {
      imageError.value = 'An error occurred while changing image input type.';
    }
  }
};

const handleFileUpload = (event: Event) => {
  handleImageUpload(event, (file) => {
    form.featured_image_file = file;
  });
};

const removeImage = () => {
  form.featured_image = null;
  form.featured_image_file = null;
  resetImage();
};

// Auto-save functions
const loadDraft = () => {
  const draft = restoreDraft();
  if (draft) {
    draftData.value = draft;
    showDraftRecovery.value = true;
  }
};

const applyDraft = () => {
  if (draftData.value) {
    form.title = draftData.value.title || '';
    form.excerpt = draftData.value.excerpt || '';
    form.content = draftData.value.content || '';
    form.featured_image = draftData.value.featured_image || '';
    form.tags = draftData.value.tags || [];
    form.is_featured = draftData.value.is_featured || false;
    form.is_published = draftData.value.is_published || false;
    form.published_at = draftData.value.published_at || null;
  }
  showDraftRecovery.value = false;
  draftData.value = null;
};

const dismissDraft = () => {
  clearDraft();
  showDraftRecovery.value = false;
  draftData.value = null;
};

onMounted(() => {
  // Check for existing draft
  loadDraft();
  // Start auto-save
  startAutoSave();
});

const submit = async () => {
  try {
    // Clear all previous errors
    clearGeneralError();
    clearImageError();
    clearTagError();
    
    if (DEBUG_VALIDATION) {
      console.log('Starting form submission...');
      console.log('Form data:', {
        title: form.title,
        excerpt: form.excerpt,
        content: form.content
      });

      console.log('Form values before submission:', {
        title: form.title,
        excerpt: form.excerpt, 
        content: form.content,
        titleLength: String(form.title || '').trim().length,
        excerptLength: String(form.excerpt || '').trim().length,
        contentLength: String(form.content || '').trim().length
      });
    }
    
    // Client-side validation as a safety net (server-side validation is authoritative)
    try {
      validateRequiredFields(form);
      if (DEBUG_VALIDATION) console.log('Client-side validation passed');
    } catch (validationError) {
      console.error('Client-side validation failed:', validationError);
      
      // Check if this is a false positive by examining the actual form state
      const hasTitle = form.title && String(form.title).trim().length > 0;
      const hasExcerpt = form.excerpt && String(form.excerpt).trim().length > 0;
      const hasContent = form.content && String(form.content).trim().length > 0;
      
      if (DEBUG_VALIDATION) {
        console.log('Form state check:', { hasTitle, hasExcerpt, hasContent });
      }
      
      // If we actually have all required fields, log this as a validation bug but continue
      if (hasTitle && hasExcerpt && hasContent) {
        console.warn('Client-side validation failed but fields appear to be valid. Continuing with server validation.');
      } else {
        // Re-throw the error if fields are actually missing
        throw validationError;
      }
    }

    // Validate image file if uploaded
    if (imageInputType.value === 'file' && form.featured_image_file) {
      if (DEBUG_VALIDATION) console.log('Validating image file...');
      validateImageFile(form.featured_image_file);
    }

    // Clear any previous errors
    form.clearErrors();

    // Handle form submission based on whether we have a file upload
    const submitOptions = {
      onSuccess: () => {
        if (DEBUG_VALIDATION) console.log('Blog post created successfully');
      },
      onError: (errors: any) => {
        console.error('Validation errors:', errors);
        // Inertia will automatically handle field-specific errors
      },
      onFinish: () => {
        // This runs regardless of success or failure
        if (DEBUG_VALIDATION) console.log('Form submission finished');
      }
    } as any;

    // Only use FormData if we actually have a file to upload
    if (imageInputType.value === 'file' && form.featured_image_file) {
      submitOptions.forceFormData = true;
      if (DEBUG_VALIDATION) console.log('Using FormData because file is present');
    } else {
      if (DEBUG_VALIDATION) console.log('Using regular JSON submission (no file upload)');
    }

    await form.post('/admin/blog-posts', submitOptions);
    
    // Clear draft on successful submission
    if (!form.hasErrors) {
      clearDraft();
      stopAutoSave();
    }

  } catch (error) {
    console.error('Form submission error:', error);
    
    // Handle client-side validation errors
    if (error instanceof Error) {
      // Show user-friendly error message
      setGeneralError(error.message);
    } else {
      // Handle unexpected errors
      setGeneralError('An unexpected error occurred. Please try again.');
    }
  }
};
</script>

<template>
  <Head title="Create Blog Post" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto max-w-form">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-foreground">Create New Blog Post</h1>
            <p class="text-muted-foreground">Write and publish a new article for your blog</p>
          </div>
          <div v-if="lastSaved" class="text-sm text-muted-foreground">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Draft saved {{ getLastSavedText() }}
            </div>
          </div>
        </div>
      </div>

      <!-- Auto-save Error Alert -->
      <div v-if="error" class="mb-6 bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 rounded-lg p-4">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span class="text-sm font-medium">{{ error }}</span>
        </div>
      </div>

      <!-- Draft Recovery Banner -->
      <div v-if="showDraftRecovery" class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100">Draft Found</h3>
            <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
              We found an auto-saved draft from your previous session. Would you like to restore it?
            </p>
            <div class="mt-3 flex gap-3">
              <button
                type="button"
                @click="applyDraft"
                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors"
              >
                Restore Draft
              </button>
              <button
                type="button"
                @click="dismissDraft"
                class="px-4 py-2 bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 text-sm border border-blue-200 dark:border-blue-700 rounded-md hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors"
              >
                Dismiss
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- General Error Display -->
      <ErrorDisplay 
        v-if="generalError" 
        :error="generalError"
        type="error" 
        class="mb-6" 
        dismissible
        @dismiss="clearGeneralError"
      />

      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-card border border-border rounded-lg p-6 space-y-6">
          <!-- Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-foreground mb-2">
              Title *
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
              placeholder="Enter post title..."
            />
            <ErrorDisplay 
              v-if="form.errors.title" 
              :error="form.errors.title" 
              type="error" 
              class="mt-1" 
            />
          </div>

          <!-- Excerpt -->
          <div>
            <label for="excerpt" class="block text-sm font-medium text-foreground mb-2">
              Excerpt *
            </label>
            <textarea
              id="excerpt"
              v-model="form.excerpt"
              required
              rows="3"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-y"
              placeholder="Brief description of your post..."
            />
            <p class="mt-1 text-sm text-muted-foreground">
              A short summary that appears in post listings and social media previews.
            </p>
            <ErrorDisplay 
              v-if="form.errors.excerpt" 
              :error="form.errors.excerpt" 
              type="error" 
              class="mt-1" 
            />
          </div>

          <!-- Content (Markdown) -->
          <div>
            <ExpandableMarkdownEditor
              v-model="form.content"
              label="Content"
              placeholder="Write your post content in Markdown..."
              :rows="12"
            />
            <ErrorDisplay 
              v-if="form.errors.content" 
              :error="form.errors.content" 
              type="error" 
              class="mt-1" 
            />
          </div>

          <!-- Featured Image -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-3">
              Featured Image
            </label>
            
            <!-- Image Type Toggle -->
            <div class="flex gap-4 mb-4">
              <label class="flex items-center">
                <input
                  type="radio"
                  :checked="imageInputType === 'url'"
                  @change="handleImageTypeChange('url')"
                  class="mr-2"
                />
                Image URL
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  :checked="imageInputType === 'file'"
                  @change="handleImageTypeChange('file')"
                  class="mr-2"
                />
                Upload File
              </label>
            </div>

            <!-- URL Input -->
            <div v-if="imageInputType === 'url'">
              <input
                id="featured_image_url"
                v-model="form.featured_image"
                type="url"
                class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                placeholder="https://example.com/image.jpg"
              />
              <p class="mt-1 text-sm text-muted-foreground">
                Enter a URL to an image hosted elsewhere.
              </p>
            </div>

            <!-- File Upload -->
            <div v-if="imageInputType === 'file'">
              <input
                id="featured_image_file"
                type="file"
                accept="image/*"
                @change="handleFileUpload"
                class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-muted file:text-muted-foreground hover:file:bg-muted/80"
              />
              <p class="mt-1 text-sm text-muted-foreground">
                Upload an image from your computer (JPG, PNG, GIF, WebP).
              </p>
            </div>

            <!-- Image Preview -->
            <div v-if="imagePreview || (imageInputType === 'url' && form.featured_image)" class="mt-4">
              <div class="flex items-start gap-3">
                <img
                  :src="imagePreview || form.featured_image || ''"
                  alt="Image preview"
                  class="w-32 h-24 object-cover rounded border"
                  @error="imagePreview = null"
                />
                <button
                  type="button"
                  @click="removeImage"
                  class="text-sm text-destructive hover:text-destructive/80"
                >
                  Remove
                </button>
              </div>
            </div>

            <!-- Client-side image error -->
            <ErrorDisplay 
              v-if="imageError" 
              :error="imageError" 
              type="error" 
              class="mt-1" 
            />
            <!-- Server-side image error -->
            <ErrorDisplay 
              v-if="form.errors.featured_image" 
              :error="form.errors.featured_image" 
              type="error" 
              class="mt-1" 
            />
          </div>

          <!-- Tags -->
          <div>
            <label for="tags" class="block text-sm font-medium text-foreground mb-2">
              Tags
            </label>
            <div class="space-y-3">
              <!-- Tag Input -->
              <div class="flex gap-2">
                <input
                  id="tags"
                  v-model="tagInput"
                  type="text"
                  @keydown="handleTagKeydown"
                  class="flex-1 px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                  placeholder="Add a tag and press Enter..."
                />
                <button
                  type="button"
                  @click="addTag"
                  class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors"
                >
                  Add
                </button>
              </div>

              <!-- Tag List -->
              <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2">
                <span
                  v-for="(tag, index) in form.tags"
                  :key="index"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-muted text-muted-foreground text-sm rounded-full"
                >
                  {{ tag }}
                  <button
                    type="button"
                    @click="removeTag(index)"
                    class="text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
              </div>
            </div>
            <!-- Client-side tag validation error -->
            <ErrorDisplay 
              v-if="tagError" 
              :error="tagError" 
              type="error" 
              class="mt-1" 
            />
            <!-- Server-side tag validation error -->
            <ErrorDisplay 
              v-if="form.errors.tags" 
              :error="form.errors.tags" 
              type="error" 
              class="mt-1" 
            />
          </div>
        </div>

        <!-- Publishing Options -->
        <div class="bg-card border border-border rounded-lg p-6 space-y-4">
          <h3 class="text-lg font-semibold text-foreground">Publishing Options</h3>
          
          <!-- Featured -->
          <div class="flex items-center gap-3">
            <input
              id="is_featured"
              v-model="form.is_featured"
              type="checkbox"
              class="w-4 h-4 border border-border rounded focus:ring-2 focus:ring-ring"
            />
            <label for="is_featured" class="text-sm font-medium text-foreground">
              Featured Post
            </label>
            <span class="text-sm text-muted-foreground">
              Display this post prominently on the blog homepage
            </span>
          </div>

          <!-- Published -->
          <div class="flex items-center gap-3">
            <input
              id="is_published"
              v-model="form.is_published"
              type="checkbox"
              class="w-4 h-4 border border-border rounded focus:ring-2 focus:ring-ring"
            />
            <label for="is_published" class="text-sm font-medium text-foreground">
              Publish Immediately
            </label>
            <span class="text-sm text-muted-foreground">
              Make this post visible to readers
            </span>
          </div>

          <!-- Custom Publish Date -->
          <div v-if="form.is_published" class="ml-7">
            <label for="published_at" class="block text-sm font-medium text-foreground mb-2">
              Publish Date (Optional)
            </label>
            <input
              id="published_at"
              v-model="form.published_at"
              type="datetime-local"
              class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
            <p class="mt-1 text-sm text-muted-foreground">
              Leave blank to publish now, or set a future date to schedule.
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
          <button
            type="button"
            @click="$inertia.visit('/admin/blog-posts')"
            class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors"
          >
            Cancel
          </button>
          
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ form.processing ? 'Creating...' : 'Create Post' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>