/**
 * Editor configuration constants
 */

/**
 * Maximum number of characters allowed in blog post content
 */
export const MAX_BLOG_POST_CHARACTERS = 50000;

/**
 * Maximum file size for image uploads in bytes (2MB)
 */
export const MAX_IMAGE_FILE_SIZE = 2 * 1024 * 1024; // 2MB

/**
 * Allowed MIME types for image uploads
 * Must match backend validation rules
 */
export const ALLOWED_IMAGE_TYPES = [
  'image/jpeg',
  'image/png',
  'image/gif',
  'image/webp'
] as const;
