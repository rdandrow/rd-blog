<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Include content if we're on a blog.show route or if viewing a single post
        $includeContent = $request->routeIs('blog.show') || $request->route()?->getName() === 'blog.show';
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($includeContent, $this->content),
            'featured_image' => $this->featured_image,
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'avatar' => $this->author->avatar ?? null,
            ],
            'published_at' => $this->published_at?->toISOString(),
            'reading_time' => $this->reading_time,
            'tags' => $this->tags ?? [],
            'is_featured' => $this->is_featured,
        ];
    }
}
