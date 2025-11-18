<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ErrorDisplay from '@/components/ErrorDisplay.vue';
import MarkdownEditor from '@/components/MarkdownEditor.vue';
import { useImageValidation, useFormValidation, useTagManagement } from '@/composables/useBlogPostForm';

// Debug flag - set to false for production
const DEBUG_VALIDATION = import.meta.env.DEV || false;

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

interface BlogPost {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  featured_image: string | null;
  tags: string[];
  is_featured: boolean;
  is_published: boolean;
  published_at: string | null;
  reading_time: number;
  created_at: string;
  updated_at: string;
  author: {
    id: number;
    name: string;
    email: string;
  };
}

interface PageProps {
  post: BlogPost;
}

const props = defineProps<PageProps>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Blog Posts', href: '/admin/blog-posts' },
  { title: props.post.title, href: `/admin/blog-posts/${props.post.id}` },
  { title: 'Edit', href: `/admin/blog-posts/${props.post.id}/edit` },
];

// Helper function to determine if a featured_image is a local file path or external URL
const isLocalFilePath = (imagePath: string | null): boolean => {
  if (!imagePath) return false;
  return imagePath.startsWith('/storage/');
};

const isExternalUrl = (imagePath: string | null): boolean => {
  if (!imagePath) return false;
  return imagePath.startsWith('http://') || imagePath.startsWith('https://');
};

// Initialize form with proper handling of file paths vs URLs
const initializeFeaturedImage = () => {
  const existingImage = props.post.featured_image;
  
  if (isLocalFilePath(existingImage)) {
    // Keep the local file path to preserve the image, but don't show it in URL input
    return existingImage;
  } else if (isExternalUrl(existingImage)) {
    // If it's an external URL, show it in the URL field
    return existingImage;
  } else {
    // If it's empty or invalid, default to empty
    return '';
  }
};

const form = useForm({
  title: props.post.title,
  excerpt: props.post.excerpt,
  content: props.post.content,
  featured_image: initializeFeaturedImage(),
  featured_image_file: null as File | null,
  remove_current_image: false, // Flag to indicate if current image should be removed
  tags: [...props.post.tags],
  is_featured: props.post.is_featured,
  is_published: props.post.is_published,
  published_at: props.post.published_at ? new Date(props.post.published_at).toISOString().slice(0, 16) : null,
});

// Set initial input type based on existing image
const getInitialImageInputType = (): 'url' | 'file' => {
  const existingImage = props.post.featured_image;
  
  if (isExternalUrl(existingImage)) {
    return 'url';
  } else {
    // Default to 'url' for new entries or local files (user can switch to file if needed)
    return 'url';
  }
};

// Form state
const imageInputType = ref<'url' | 'file'>(getInitialImageInputType());
const tagInput = ref('');

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
  clearImageError();
  imageInputType.value = type;
  
  if (type === 'url') {
    resetImage();
  } else {
    form.featured_image = null;
  }
};

const handleFileUpload = (event: Event) => {
  handleImageUpload(event, (file, preview) => {
    form.featured_image_file = file;
  });
};

const removeImage = () => {
  form.featured_image = null;
  form.featured_image_file = null;
  resetImage();
};

const removeCurrentImage = () => {
  // Signal to the server to remove the current image
  form.remove_current_image = true;
  form.featured_image = '';
  form.featured_image_file = null;
  imagePreview.value = null;
};

const handleUrlInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  form.featured_image = target.value;
  // If user enters a URL, they're replacing any existing image
  if (target.value && target.value.trim()) {
    form.remove_current_image = false; // Reset the removal flag since they're providing a new URL
  }
};

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
        content: form.content,
        postId: props.post?.id
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
    if (form.featured_image_file) {
      if (DEBUG_VALIDATION) console.log('Validating image file...');
      validateImageFile(form.featured_image_file);
    }

    // Clear any previous errors
    form.clearErrors();

    console.log('About to submit form with data:', {
      url: `/admin/blog-posts/${props.post.id}`,
      method: 'PUT',
      data: {
        title: form.title,
        excerpt: form.excerpt,
        content: form.content,
        featured_image: form.featured_image,
        featured_image_file: form.featured_image_file,
        tags: form.tags,
        is_featured: form.is_featured,
        is_published: form.is_published,
        published_at: form.published_at
      }
    });

    // Additional debugging: check actual values and types
    console.log('Detailed form field analysis:', {
      title: { 
        value: form.title, 
        type: typeof form.title, 
        length: form.title ? form.title.length : 0,
        isEmpty: !form.title || form.title.toString().trim() === ''
      },
      excerpt: { 
        value: form.excerpt, 
        type: typeof form.excerpt, 
        length: form.excerpt ? form.excerpt.length : 0,
        isEmpty: !form.excerpt || form.excerpt.toString().trim() === ''
      },
      content: { 
        value: form.content, 
        type: typeof form.content, 
        length: form.content ? form.content.length : 0,
        isEmpty: !form.content || form.content.toString().trim() === ''
      }
    });

    // Try without forceFormData first, only use it if we have a file upload
    const submitOptions = {
      onSuccess: (response: any) => {
        console.log('Blog post updated successfully', response);
      },
      onError: (errors: any) => {
        console.error('Server validation errors:', errors);
        console.log('Form errors object:', form.errors);
        console.log('Raw error response:', JSON.stringify(errors, null, 2));
        // Inertia will automatically handle field-specific errors
      },
      onFinish: () => {
        // This runs regardless of success or failure
        console.log('Form submission finished');
      }
    } as any;

    // Handle file uploads differently to ensure all fields are properly sent
    if (form.featured_image_file) {
      console.log('Using FormData because file is present');
      
      // When we have a file, we need to be extra careful about FormData serialization
      // Let's explicitly ensure all text fields are properly set
      const formDataToSend = new FormData();
      
      // Add all text fields explicitly
      formDataToSend.append('title', form.title || '');
      formDataToSend.append('excerpt', form.excerpt || '');
      formDataToSend.append('content', form.content || '');
      formDataToSend.append('featured_image', form.featured_image || '');
      formDataToSend.append('is_featured', form.is_featured ? '1' : '0');
      formDataToSend.append('is_published', form.is_published ? '1' : '0');
      formDataToSend.append('published_at', form.published_at || '');
      
      // Add tags as JSON string or individual entries
      if (form.tags && form.tags.length > 0) {
        form.tags.forEach((tag, index) => {
          formDataToSend.append(`tags[${index}]`, tag);
        });
      }
      
      // Add the file
      formDataToSend.append('featured_image_file', form.featured_image_file);
      
      // Add Laravel method spoofing for PUT request
      formDataToSend.append('_method', 'PUT');
      
      console.log('FormData contents being sent:', {
        title: formDataToSend.get('title'),
        excerpt: formDataToSend.get('excerpt'),
        content: formDataToSend.get('content'),
        file: formDataToSend.get('featured_image_file')
      });
      
      // Use router.post with FormData for file uploads (Laravel will handle _method=PUT)
      await router.post(`/admin/blog-posts/${props.post.id}`, formDataToSend, {
        ...submitOptions,
        forceFormData: false, // We're already using FormData
      });
    } else {
      console.log('Using regular JSON submission (no file upload)');
      await form.put(`/admin/blog-posts/${props.post.id}`, submitOptions);
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
  <Head title="Edit Blog Post" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-foreground">Edit Blog Post</h1>
        <p class="text-muted-foreground">Update your blog post content and settings</p>
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
            <MarkdownEditor
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
                Upload New File
              </label>
            </div>

            <!-- URL Input -->
            <div v-if="imageInputType === 'url'">
              <input
                id="featured_image_url"
                :value="isExternalUrl(form.featured_image) ? form.featured_image : ''"
                @input="handleUrlInput"
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

            <!-- Current Image (if exists and not being removed/replaced) -->
            <div v-if="props.post.featured_image && !imagePreview && !form.remove_current_image && !form.featured_image_file && (!isExternalUrl(form.featured_image) || form.featured_image === props.post.featured_image)" class="mt-4">
              <p class="text-sm font-medium text-muted-foreground mb-2">Current Image:</p>
              <div class="flex items-start gap-3">
                <img
                  :src="props.post.featured_image"
                  alt="Current featured image"
                  class="w-32 h-24 object-cover rounded border"
                />
                <div class="flex-1">
                  <p class="text-sm text-muted-foreground">
                    <span v-if="isLocalFilePath(props.post.featured_image)">
                      Uploaded file: {{ props.post.featured_image.split('/').pop() }}
                    </span>
                    <span v-else>
                      External URL: {{ props.post.featured_image }}
                    </span>
                  </p>
                  <button
                    type="button"
                    @click="removeCurrentImage"
                    class="text-sm text-destructive hover:text-destructive/80 mt-1"
                  >
                    Remove Current Image
                  </button>
                </div>
              </div>
            </div>

            <!-- New Image Preview (only show for actual new images) -->
            <div v-if="imagePreview || (imageInputType === 'url' && form.featured_image && isExternalUrl(form.featured_image) && form.featured_image !== props.post.featured_image)" class="mt-4">
              <p class="text-sm font-medium text-muted-foreground mb-2">New Image Preview:</p>
              <div class="flex items-start gap-3">
                <img
                  :src="imagePreview || form.featured_image || ''"
                  alt="New image preview"
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
              Published
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
              Leave blank to use current publish date, or set a future date to schedule.
            </p>
          </div>
        </div>

        <!-- Post Info -->
        <div class="bg-muted/50 border border-border rounded-lg p-6">
          <h3 class="text-lg font-semibold text-foreground mb-3">Post Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <span class="font-medium text-foreground">Slug:</span>
              <span class="ml-2 text-muted-foreground">{{ post.slug }}</span>
            </div>
            <div>
              <span class="font-medium text-foreground">Reading Time:</span>
              <span class="ml-2 text-muted-foreground">{{ post.reading_time }} minutes</span>
            </div>
            <div>
              <span class="font-medium text-foreground">Created:</span>
              <span class="ml-2 text-muted-foreground">{{ new Date(post.created_at).toLocaleDateString() }}</span>
            </div>
            <div>
              <span class="font-medium text-foreground">Last Updated:</span>
              <span class="ml-2 text-muted-foreground">{{ new Date(post.updated_at).toLocaleDateString() }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="$inertia.visit(`/admin/blog-posts/${post.id}`)"
              class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors"
            >
              Cancel
            </button>
          </div>
          
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ form.processing ? 'Updating...' : 'Update Post' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>