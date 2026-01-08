<?php

namespace Tests\Traits;

/**
 * Trait for common HTTP test patterns.
 * 
 * Provides convenient methods for common HTTP testing scenarios including
 * authentication, JSON testing, and response validation.
 * 
 * Usage:
 * ```php
 * use Tests\Traits\HttpTestHelpers;
 * 
 * uses(HttpTestHelpers::class);
 * 
 * it('returns JSON response', function () {
 *     $response = $this->getJson('/api/posts');
 *     $this->assertJsonResponseSuccess($response);
 * });
 * ```
 */
trait HttpTestHelpers
{
    /**
     * Assert that a JSON response was successful and has expected structure.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param array $expectedKeys
     * @return $this
     */
    protected function assertJsonResponseSuccess($response, array $expectedKeys = []): static
    {
        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json->whereType('data', 'array'));
        
        if (!empty($expectedKeys)) {
            foreach ($expectedKeys as $key) {
                $response->assertJsonPath("data.{$key}", fn ($value) => $value !== null);
            }
        }
        
        return $this;
    }
    
    /**
     * Assert that response redirects with success message.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param string $expectedMessage
     * @param string|null $expectedRoute
     * @return $this
     */
    protected function assertRedirectsWithSuccess($response, string $expectedMessage, ?string $expectedRoute = null): static
    {
        if ($expectedRoute) {
            $response->assertRedirect(route($expectedRoute));
        } else {
            $response->assertRedirect();
        }
        
        $response->assertSessionHas('success', $expectedMessage);
        
        return $this;
    }
    
    /**
     * Assert that response has validation errors for specified fields.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param array $fields
     * @return $this
     */
    protected function assertHasValidationErrors($response, array $fields): static
    {
        $response->assertSessionHasErrors($fields);
        
        return $this;
    }
    
    /**
     * Assert that response is unauthorized (401 or 403).
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @return $this
     */
    protected function assertUnauthorizedResponse($response): static
    {
        expect($response->status())->toBeIn([401, 403]);
        
        return $this;
    }
    
    /**
     * Assert that response is a successful Inertia response with expected component.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param string $component
     * @param array $props
     * @return $this
     */
    protected function assertInertiaResponse($response, string $component, array $props = []): static
    {
        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($component, $props) {
            $page->component($component);
            
            foreach ($props as $key => $value) {
                if (is_callable($value)) {
                    $page->has($key, $value);
                } else {
                    $page->where($key, $value);
                }
            }
        });
        
        return $this;
    }
    
    /**
     * Create an authenticated request and assert it succeeds.
     *
     * @param \App\Models\User $user
     * @param string $method
     * @param string $uri
     * @param array $data
     * @return \Illuminate\Testing\TestResponse
     */
    protected function authenticatedRequest($user, string $method, string $uri, array $data = [])
    {
        return match(strtoupper($method)) {
            'GET' => authenticatedGet($user, $uri),
            'POST' => authenticatedPost($user, $uri, $data),
            'PUT', 'PATCH' => authenticatedPut($user, $uri, $data),
            'DELETE' => authenticatedDelete($user, $uri),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }
    
    /**
     * Assert that response has specific HTTP status code.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param int $expectedStatus
     * @return $this
     */
    protected function assertResponseStatus($response, int $expectedStatus): static
    {
        expect($response->status())->toBe($expectedStatus);
        
        return $this;
    }
}
