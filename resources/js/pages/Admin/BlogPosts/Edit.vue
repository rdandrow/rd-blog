<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

// Utility functions for validation
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

// Debug flag - set to false for production
const DEBUG_VALIDATION = import.meta.env.DEV || false;

const validateRequiredFields = (formData: any, postId?: number): void => {
  if (DEBUG_VALIDATION) {
    console.log('Validating form data:', { 
      formData,
      formType: typeof formData,
      formKeys: Object.keys(formData || {}),
      postId
    });
  }
  
  // For Inertia forms, the data is directly accessible as properties
  const titleValue = formData.title;
  const excerptValue = formData.excerpt;
  const contentValue = formData.content;
  
  if (DEBUG_VALIDATION) {
    console.log('Raw values:', { 
      titleValue, 
      excerptValue, 
      contentValue,
      titleType: typeof titleValue,
      excerptType: typeof excerptValue,
      contentType: typeof contentValue
    });
  }
  
  // Convert to strings and check if they have meaningful content
  const titleStr = String(titleValue || '').trim();
  const excerptStr = String(excerptValue || '').trim();
  const contentStr = String(contentValue || '').trim();
  
  if (DEBUG_VALIDATION) {
    console.log('Processed values:', { 
      titleStr: `"${titleStr}"`, 
      excerptStr: `"${excerptStr}"`, 
      contentStr: `"${contentStr}"`,
      titleLength: titleStr.length,
      excerptLength: excerptStr.length,
      contentLength: contentStr.length
    });
  }
  
  if (!titleStr) {
    throw new Error('Title is required');
  }
  if (!excerptStr) {
    throw new Error('Excerpt is required');
  }
  if (!contentStr) {
    throw new Error('Content is required');
  }
  if (postId !== undefined && (!postId || postId <= 0)) {
    throw new Error('Invalid post ID');
  }
};

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

const form = useForm({
  title: props.post.title,
  excerpt: props.post.excerpt,
  content: props.post.content,
  featured_image: props.post.featured_image || '',
  featured_image_file: null as File | null,
  tags: [...props.post.tags],
  is_featured: props.post.is_featured,
  is_published: props.post.is_published,
  published_at: props.post.published_at ? new Date(props.post.published_at).toISOString().slice(0, 16) : null,
});

const imageInputType = ref<'url' | 'file'>(props.post.featured_image ? 'url' : 'url');
const imagePreview = ref<string | null>(null);

const tagInput = ref('');

const addTag = () => {
  try {
    const tag = tagInput.value.trim();
    
    if (!tag) {
      throw new Error('Tag cannot be empty');
    }
    
    if (tag.length > 50) {
      throw new Error('Tag must be 50 characters or less');
    }
    
    if (form.tags.includes(tag)) {
      throw new Error('Tag already exists');
    }
    
    if (form.tags.length >= 10) {
      throw new Error('Maximum of 10 tags allowed');
    }
    
    form.tags.push(tag);
    tagInput.value = '';
    
  } catch (error) {
    console.error('Tag addition error:', error);
    
    if (error instanceof Error) {
      alert(error.message);
    } else {
      alert('An error occurred while adding the tag.');
    }
  }
};

const removeTag = (index: number) => {
  try {
    if (index < 0 || index >= form.tags.length) {
      throw new Error('Invalid tag index');
    }
    
    form.tags.splice(index, 1);
    
  } catch (error) {
    console.error('Tag removal error:', error);
    
    if (error instanceof Error) {
      alert(error.message);
    } else {
      alert('An error occurred while removing the tag.');
    }
  }
};

const handleTagKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    addTag();
  }
};

const handleImageTypeChange = (type: 'url' | 'file') => {
  try {
    if (type !== 'url' && type !== 'file') {
      throw new Error('Invalid image type selection');
    }
    
    imageInputType.value = type;
    
    if (type === 'url') {
      form.featured_image_file = null;
      imagePreview.value = null;
    } else {
      form.featured_image = null;
    }
    
  } catch (error) {
    console.error('Image type change error:', error);
    
    if (error instanceof Error) {
      alert(error.message);
    } else {
      alert('An error occurred while changing image input type.');
    }
  }
};

const handleFileUpload = (event: Event) => {
  try {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (!file) {
      return;
    }

    // Validate file
    validateImageFile(file);

    form.featured_image_file = file;
    
    // Create preview with error handling
    const reader = new FileReader();
    
    reader.onload = (e) => {
      try {
        imagePreview.value = e.target?.result as string;
      } catch (error) {
        console.error('Error creating image preview:', error);
        alert('Error creating image preview. The file will still be uploaded.');
      }
    };
    
    reader.onerror = () => {
      console.error('FileReader error occurred');
      alert('Error reading the selected file. Please try again.');
      // Reset the file input
      target.value = '';
      form.featured_image_file = null;
    };
    
    reader.readAsDataURL(file);
    
  } catch (error) {
    console.error('File upload error:', error);
    
    // Reset the file input on error
    const target = event.target as HTMLInputElement;
    target.value = '';
    form.featured_image_file = null;
    imagePreview.value = null;
    
    if (error instanceof Error) {
      alert(error.message);
    } else {
      alert('An error occurred while uploading the file. Please try again.');
    }
  }
};

const removeImage = () => {
  form.featured_image = null;
  form.featured_image_file = null;
  imagePreview.value = null;
};

const submit = async () => {
  try {
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
      validateRequiredFields(form, props.post?.id);
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
      onSuccess: (response) => {
        console.log('Blog post updated successfully', response);
      },
      onError: (errors) => {
        console.error('Server validation errors:', errors);
        console.log('Form errors object:', form.errors);
        console.log('Raw error response:', JSON.stringify(errors, null, 2));
        // Inertia will automatically handle field-specific errors
      },
      onFinish: () => {
        // This runs regardless of success or failure
        console.log('Form submission finished');
      }
    };

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
      alert(error.message);
    } else {
      // Handle unexpected errors
      alert('An unexpected error occurred. Please try again.');
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
            <div v-if="form.errors.title" class="mt-1 text-sm text-destructive">
              {{ form.errors.title }}
            </div>
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
            <div v-if="form.errors.excerpt" class="mt-1 text-sm text-destructive">
              {{ form.errors.excerpt }}
            </div>
          </div>

          <!-- Content -->
          <div>
            <label for="content" class="block text-sm font-medium text-foreground mb-2">
              Content *
            </label>
            <textarea
              id="content"
              v-model="form.content"
              required
              rows="12"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-y"
              placeholder="Write your post content here..."
            />
            <p class="mt-1 text-sm text-muted-foreground">
              You can use Markdown formatting for rich text content.
            </p>
            <div v-if="form.errors.content" class="mt-1 text-sm text-destructive">
              {{ form.errors.content }}
            </div>
          </div>

          <!-- Featured Image -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-3">
              Featured Image
            </label>
            
            <!-- Current Image Display -->
            <div v-if="props.post.featured_image && !imagePreview && imageInputType === 'url'" class="mb-4">
              <div class="flex items-start gap-3">
                <img
                  :src="props.post.featured_image"
                  alt="Current featured image"
                  class="w-32 h-24 object-cover rounded border"
                />
                <div class="text-sm text-muted-foreground">
                  Current image
                </div>
              </div>
            </div>
            
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
            <div v-if="imagePreview || (imageInputType === 'url' && form.featured_image && form.featured_image !== props.post.featured_image)" class="mt-4">
              <div class="flex items-start gap-3">
                <img
                  :src="imagePreview || form.featured_image"
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

            <div v-if="form.errors.featured_image" class="mt-1 text-sm text-destructive">
              {{ form.errors.featured_image }}
            </div>
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
            <div v-if="form.errors.tags" class="mt-1 text-sm text-destructive">
              {{ form.errors.tags }}
            </div>
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