<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogImageService
{
    /**
     * Upload a blog image and return the storage path.
     */
    public function upload(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $extension;
        $path = $file->storeAs('blog-images', $filename, 'public');
        
        return '/storage/' . $path;
    }

    /**
     * Delete a blog image if it's stored locally.
     */
    public function delete(?string $imagePath): void
    {
        if ($imagePath && str_starts_with($imagePath, '/storage/')) {
            $diskPath = str_replace('/storage/', '', $imagePath);
            Storage::disk('public')->delete($diskPath);
        }
    }

    /**
     * Handle image input for creating a new blog post.
     * 
     * @param  \Illuminate\Http\UploadedFile|null  $file
     * @param  string|null  $url
     * @return string|null
     */
    public function handleCreate(?UploadedFile $file, ?string $url): ?string
    {
        if ($file) {
            return $this->upload($file);
        }
        
        return $url;
    }

    /**
     * Handle image update logic based on the request inputs.
     * 
     * @param  \Illuminate\Http\UploadedFile|null  $newFile
     * @param  string|null  $newUrl
     * @param  bool  $removeCurrentImage
     * @param  string|null  $currentImagePath
     * @return string|null
     */
    public function handleUpdate(
        ?UploadedFile $newFile,
        ?string $newUrl,
        bool $removeCurrentImage,
        ?string $currentImagePath
    ): ?string {
        // Priority 1: New file uploaded
        if ($newFile) {
            $this->delete($currentImagePath);
            return $this->upload($newFile);
        }

        // Priority 2: Explicit removal requested
        if ($removeCurrentImage) {
            $this->delete($currentImagePath);
            return null;
        }

        // Priority 3: New URL provided and it's different
        if ($newUrl && $newUrl !== $currentImagePath) {
            $this->delete($currentImagePath);
            return $newUrl;
        }

        // Keep existing image
        return $currentImagePath;
    }
}
