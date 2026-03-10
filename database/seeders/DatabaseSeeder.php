<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\BlogPostLike;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Create Master Admin
        $this->command->info('Creating master admin...');
        $masterAdmin = User::factory()->masterAdmin()->create([
            'name' => 'Ryan Dandrow',
            'email' => 'ryan@example.com',
            'website' => 'https://ryandandrow.com',
            'bio' => 'Master administrator and founder of RD Blog. Full-stack developer passionate about Laravel, Vue.js, and building elegant web applications.',
        ]);

        // Create Regular Admins
        $this->command->info('Creating admins...');
        $admin1 = User::factory()->admin()->create([
            'name' => 'Sarah Admin',
            'email' => 'sarah@example.com',
            'website' => 'https://sarahcodes.dev',
            'bio' => 'Senior developer and blog administrator. Love writing about web development best practices and modern JavaScript frameworks.',
        ]);

        $admin2 = User::factory()->admin()->create([
            'name' => 'Mike Administrator',
            'email' => 'mike@example.com',
            'website' => null,
            'bio' => 'Backend specialist focused on Laravel and PHP. Always learning, always building.',
        ]);

        // Create Featured Authors (admins with rich profiles)
        $this->command->info('Creating featured author admins...');
        $author1 = User::factory()->admin()->create([
            'name' => 'Emily Chen',
            'email' => 'emily@example.com',
            'website' => 'https://emilychen.io',
            'bio' => '🚀 Frontend engineer specializing in Vue.js and React. I write tutorials on modern web development, performance optimization, and UI/UX design. Coffee enthusiast ☕',
        ]);

        $author2 = User::factory()->admin()->create([
            'name' => 'James Wilson',
            'email' => 'james@example.com',
            'website' => 'https://jameswilson.dev',
            'bio' => 'Full-stack developer with a passion for clean code and elegant solutions. Sharing my journey through Laravel, PostgreSQL, and DevOps. 💻',
        ]);

        $author3 = User::factory()->admin()->create([
            'name' => 'Priya Patel',
            'email' => 'priya@example.com',
            'website' => null,
            'bio' => 'Software engineer and tech blogger. I love exploring new technologies and sharing what I learn. Always curious, always coding!',
        ]);

        $author4 = User::factory()->admin()->create([
            'name' => 'Alex Rodriguez',
            'email' => 'alex@example.com',
            'website' => 'https://alexcodes.com',
            'bio' => 'DevOps engineer who loves automation and CI/CD. Writing about Docker, Kubernetes, and making developers\' lives easier. 🐳',
        ]);

        // Create Regular Members
        $this->command->info('Creating regular members...');
        $members = User::factory()->count(10)->create([
            'role' => 'member',
        ]);

        // Add websites and bios to some members
        foreach ($members->take(5) as $index => $member) {
            $member->update([
                'website' => fake()->boolean(60) ? 'https://' . fake()->domainName() : null,
                'bio' => fake()->boolean(70) ? fake()->paragraph(2) : null,
            ]);
        }

        $allUsers = collect([$masterAdmin, $admin1, $admin2, $author1, $author2, $author3, $author4])
            ->concat($members);

        // Create User Follows (social connections)
        $this->command->info('Creating user follows...');
        foreach ($allUsers as $user) {
            // Featured authors get more followers
            $isPopular = in_array($user->id, [$author1->id, $author2->id, $author3->id, $masterAdmin->id]);
            $followerCount = $isPopular ? fake()->numberBetween(8, 15) : fake()->numberBetween(1, 6);
            
            $followers = $allUsers->where('id', '!=', $user->id)
                ->random(min($followerCount, $allUsers->count() - 1));
            
            foreach ($followers as $follower) {
                $follower->following()->attach($user->id);
            }
        }

        // Create Blog Posts with Unique Markdown Content
        $this->command->info('Creating blog posts with unique markdown content...');
        
        $blogPosts = [
            // Master Admin Posts
            [
                'author' => $masterAdmin,
                'title' => 'Mastering Vue 3 Composition API',
                'excerpt' => 'Dive deep into Vue 3\'s Composition API and learn how to write cleaner, more maintainable code. From setup functions to composables, master the modern way to build Vue applications.',
                'tags' => ['Vue.js', 'JavaScript', 'Frontend', 'Tutorial'],
                'is_featured' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# Mastering Vue 3 Composition API

![Vue 3 Logo](https://vuejs.org/images/logo.png)

The **Composition API** has *revolutionized* how we write Vue components. Let's explore its power and flexibility in this ~~comprehensive~~ **comprehensive** guide.

## Why Composition API?

The Options API served us well, but as applications grew, we faced challenges:

- **Logic reuse** was difficult without mixins
- *Type inference* struggled with complex components  
- ~~Code duplication~~ **Code organization** became messy in large components

### Comparison Table

| Feature | Options API | Composition API |
|---------|-------------|-----------------|
| Logic Organization | Split across options | Grouped by feature |
| Code Reuse | Mixins (problematic) | Composables (clean) |
| Type Safety | Limited | Excellent |
| Bundle Size | Larger | Tree-shakeable |
| Learning Curve | Easier initially | Steeper but more powerful |

## Core Concepts

### 1. Setup Function

The `setup()` function is the **entry point** for Composition API:

```javascript
import { ref, computed, onMounted } from 'vue';

export default {
  setup() {
    const count = ref(0);
    const doubled = computed(() => count.value * 2);
    
    onMounted(() => {
      console.log('Component mounted!');
    });
    
    return { count, doubled };
  }
}
```

> **Pro Tip**: The `setup()` function runs *before* component creation, so `this` is not available!

### 2. Reactivity System

Understanding Vue's reactivity is crucial:

1. `ref()` - for primitive values
2. `reactive()` - for objects
3. `computed()` - for derived state
4. `watch()` - for side effects
5. `watchEffect()` - for auto-tracking dependencies

#### Reactive Example

```javascript
import { ref, reactive, computed } from 'vue';

// Primitives need ref()
const count = ref(0);
const message = ref('Hello');

// Objects can use reactive()
const state = reactive({
  user: null,
  isLoading: false,
  errors: []
});

// Computed properties are cached
const doubleCount = computed(() => count.value * 2);
```

### 3. Composables Pattern

Extract reusable logic into **composables** (similar to React hooks):

```javascript
// composables/useAuth.js
export function useAuth() {
  const user = ref(null);
  const isAuthenticated = computed(() => !!user.value);
  const isLoading = ref(false);
  
  async function login(credentials) {
    isLoading.value = true;
    try {
      const response = await api.login(credentials);
      user.value = response.data;
      return { success: true };
    } catch (error) {
      return { success: false, error };
    } finally {
      isLoading.value = false;
    }
  }
  
  async function logout() {
    await api.logout();
    user.value = null;
  }
  
  return { 
    user, 
    isAuthenticated, 
    isLoading,
    login, 
    logout 
  };
}
```

Usage in components:

```vue
<script setup>
import { useAuth } from '@/composables/useAuth';

const { user, isAuthenticated, login, logout } = useAuth();

async function handleLogin() {
  const result = await login({ email, password });
  if (result.success) {
    router.push('/dashboard');
  }
}
</script>
```

## Script Setup Syntax

Modern Vue uses `<script setup>` for *cleaner* and *more concise* code:

```vue
<script setup>
import { ref, computed, watch } from 'vue';

// No need to return - everything is automatically exposed
const count = ref(0);
const doubled = computed(() => count.value * 2);

function increment() {
  count.value++;
}

// Watchers are automatically set up
watch(count, (newValue, oldValue) => {
  console.log(`Count changed from ${oldValue} to ${newValue}`);
});
</script>

<template>
  <div>
    <p>Count: {{ count }}</p>
    <p>Doubled: {{ doubled }}</p>
    <button @click="increment">Increment</button>
  </div>
</template>
```

> **Note**: `<script setup>` is the recommended syntax for Vue 3 projects!

## Advanced Patterns

### Lifecycle Hooks

All lifecycle hooks are available as functions:

```javascript
import { 
  onBeforeMount,
  onMounted,
  onBeforeUpdate,
  onUpdated,
  onBeforeUnmount,
  onUnmounted 
} from 'vue';

onMounted(() => {
  console.log('Component mounted!');
  fetchData();
});

onUnmounted(() => {
  console.log('Cleanup before unmount');
  clearSubscriptions();
});
```

### Provide/Inject

Share data across component hierarchy:

```javascript
// Parent component
import { provide, ref } from 'vue';

const theme = ref('dark');
provide('theme', theme);

// Child component (any level deep)
import { inject } from 'vue';

const theme = inject('theme');
```

## Best Practices Checklist

- ✅ Group related logic together using composables
- ✅ Use `ref()` for primitives, `reactive()` for objects
- ✅ Always access `.value` in JavaScript (not in templates)
- ✅ Leverage `computed()` for derived state (automatic caching)
- ✅ Extract reusable logic into composables
- ✅ Use TypeScript for better type inference
- ❌ Don't mix Options API and Composition API in the same component
- ❌ Don't destructure reactive objects (loses reactivity)

## Migration Strategy

| Step | Action | Priority |
|------|--------|----------|
| 1 | Start with new components | High |
| 2 | Convert utility mixins to composables | High |
| 3 | Refactor complex components gradually | Medium |
| 4 | Update tests and documentation | Medium |
| 5 | Team training and code reviews | High |

## Conclusion

The Composition API makes Vue code more **maintainable**, *testable*, and ~~complex~~ **reusable**. Here's what you get:

- Better code organization
- Enhanced TypeScript support  
- Improved logic reuse
- Smaller bundle sizes
- More flexible patterns

**Embrace it!** 🚀

---

*Questions? Drop a comment below!*
MARKDOWN
            ],
            [
                'author' => $masterAdmin,
                'title' => 'PostgreSQL Performance Tuning',
                'excerpt' => 'Unlock the full potential of PostgreSQL with proven optimization techniques. Learn about indexes, query optimization, connection pooling, and monitoring to supercharge your database performance.',
                'tags' => ['PostgreSQL', 'Database', 'Performance', 'Backend'],
                'is_featured' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# PostgreSQL Performance Tuning

![Database Performance](https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&h=400&fit=crop)

Unlock the full potential of PostgreSQL with these optimization techniques.

## Index Strategy

Indexes are your best friend for query performance:

```sql
-- B-tree index for equality and range queries
CREATE INDEX idx_users_email ON users(email);

-- Partial index for specific conditions
CREATE INDEX idx_active_users ON users(email) 
WHERE active = true;

-- GIN index for JSON and array searches
CREATE INDEX idx_posts_tags ON blog_posts USING GIN(tags);
```

### Composite Indexes

Order matters in composite indexes:

```sql
-- Good: Supports (user_id) and (user_id, created_at)
CREATE INDEX idx_posts_user_date ON posts(user_id, created_at);

-- Use EXPLAIN ANALYZE to verify index usage
EXPLAIN ANALYZE SELECT * FROM posts 
WHERE user_id = 123 
ORDER BY created_at DESC;
```

## Query Optimization

Common pitfalls and fixes:

```sql
-- Bad: SELECT *
SELECT * FROM users WHERE email = 'test@example.com';

-- Good: Select only needed columns
SELECT id, name, email FROM users WHERE email = 'test@example.com';

-- Bad: OFFSET for deep pagination
SELECT * FROM posts ORDER BY id OFFSET 10000 LIMIT 10;

-- Good: Keyset pagination
SELECT * FROM posts WHERE id > 10000 ORDER BY id LIMIT 10;
```

## Connection Pooling

Configure your connection pool wisely:

```ini
max_connections = 100
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB
```

> **Warning**: Too many connections can hurt performance. Use pgBouncer for pooling.

## Monitoring Queries

Track slow queries with `pg_stat_statements`:

```sql
CREATE EXTENSION pg_stat_statements;

-- Find slowest queries
SELECT query, mean_exec_time, calls 
FROM pg_stat_statements 
ORDER BY mean_exec_time DESC 
LIMIT 10;
```

## Vacuum and Analyze

Keep your database healthy:

```sql
-- Manual vacuum
VACUUM ANALYZE blog_posts;

-- Configure autovacuum
ALTER TABLE blog_posts SET (autovacuum_vacuum_scale_factor = 0.1);
```

**Remember**: PostgreSQL is powerful, but it needs proper tuning to shine! 📊
MARKDOWN
            ],
            [
                'author' => $masterAdmin,
                'title' => 'Laravel Inertia.js Best Practices',
                'excerpt' => 'Build modern full-stack applications with Laravel and Inertia.js. Discover best practices for structuring your app, handling forms, optimizing performance, and creating seamless user experiences.',
                'tags' => ['Laravel', 'Inertia.js', 'Vue.js', 'Full-Stack'],
                'is_featured' => false,
                'featured_image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# Laravel Inertia.js Best Practices

Build modern SPAs without the API complexity. Here's how to master Inertia.js.

## Props and Data Sharing

Share data efficiently with props:

```php
return Inertia::render('Posts/Show', [
    'post' => $post->load('author', 'comments'),
    'canEdit' => Auth::user()?->can('update', $post),
]);
```

### Lazy Props

Load expensive data only when needed:

```php
return Inertia::render('Dashboard', [
    'stats' => fn () => $this->getExpensiveStats(),
    'recent' => $recentActivity,
]);
```

## Form Handling

Use Inertia's form helper:

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  title: '',
  content: '',
});

function submit() {
  form.post('/posts', {
    onSuccess: () => form.reset(),
  });
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="form.title" />
    <span v-if="form.errors.title">{{ form.errors.title }}</span>
    <button :disabled="form.processing">Save</button>
  </form>
</template>
```

## Shared Data

Global data available everywhere:

```php
Inertia::share([
    'auth' => fn () => [
        'user' => Auth::user(),
    ],
    'flash' => fn () => [
        'success' => session('success'),
        'error' => session('error'),
    ],
]);
```

> **Pro Tip**: Use closure-based props to avoid N+1 queries!

## Asset Versioning

Cache bust automatically:

```php
Inertia::version(fn () => Vite::useBuildDirectory('build')->manifestHash());
```

**Conclusion**: Inertia.js gives you the SPA feel with Laravel's simplicity. Perfect combo! ⚡
MARKDOWN
            ],
            
            // Author Posts
            [
                'author' => $author1,
                'title' => 'Modern CSS: Grid vs Flexbox',
                'excerpt' => 'Confused about when to use CSS Grid vs Flexbox? This guide breaks down the strengths of each layout system and shows you exactly when to use which one for optimal results.',
                'tags' => ['CSS', 'Frontend', 'Web Development', 'Design'],
                'is_featured' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1507721999472-8ed4421c4af2?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# Modern CSS: Grid vs Flexbox

![CSS Layout Comparison](https://images.unsplash.com/photo-1523437113738-bbd3cc89fb19?w=800&h=400&fit=crop)

Both are powerful, but when should you use each? Let's break it down.

## Flexbox: One-Dimensional Layout

Perfect for components and single-axis layouts:

```css
.nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}
```

### Common Flexbox Patterns

**Centered content:**
```css
.center {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}
```

**Auto-spacing:**
```css
.buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
```

## Grid: Two-Dimensional Layout

For complex page layouts:

```css
.page {
  display: grid;
  grid-template-columns: 250px 1fr;
  grid-template-rows: auto 1fr auto;
  gap: 1rem;
  min-height: 100vh;
}
```

### Responsive Grid

```css
.gallery {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
```

> **Rule of Thumb**: Flexbox for components, Grid for layouts!

## Combining Both

```css
.card-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}

.card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
```

**Takeaway**: Master both—they're complementary, not competitive! 🎨
MARKDOWN
            ],
            [
                'author' => $author1,
                'title' => 'Tailwind CSS: Tips and Tricks',
                'excerpt' => 'Level up your Tailwind CSS skills with these practical tips and tricks. From custom utilities to component patterns and dark mode, learn how to make the most of this utility-first framework.',
                'tags' => ['Tailwind', 'CSS', 'Frontend', 'Tips'],
                'is_featured' => false,
                'featured_image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# Tailwind CSS: Tips and Tricks

Level up your Tailwind game with these practical tips!

## Custom Utilities

Extend Tailwind with your own utilities:

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      spacing: {
        '128': '32rem',
        '144': '36rem',
      },
      colors: {
        'brand': {
          50: '#f0f9ff',
          500: '#0ea5e9',
          900: '#0c4a6e',
        }
      }
    }
  }
}
```

## Component Patterns

```html
<!-- Button variants -->
<button class="btn btn-primary">
  Primary
</button>

<style>
@layer components {
  .btn {
    @apply px-4 py-2 rounded font-semibold transition;
  }
  .btn-primary {
    @apply bg-blue-500 text-white hover:bg-blue-600;
  }
}
</style>
```

## Arbitrary Values

Use any value on the fly:

```html
<div class="top-[117px] w-[762px]">
  Custom dimensions
</div>
```

## Dark Mode

```html
<div class="bg-white dark:bg-gray-900">
  <h1 class="text-gray-900 dark:text-white">
    Auto dark mode!
  </h1>
</div>
```

> **Pro Tip**: Use `@apply` sparingly—utility-first is Tailwind's strength!

**Conclusion**: Tailwind speeds up development without sacrificing flexibility. 🚀
MARKDOWN
            ],
            [
                'author' => $author2,
                'title' => 'Laravel Query Optimization',
                'excerpt' => 'Write efficient Laravel queries and avoid common performance pitfalls. Learn to tackle N+1 problems, use eager loading, optimize selects, and leverage query scopes for better database performance.',
                'tags' => ['Laravel', 'PHP', 'Database', 'Performance'],
                'is_featured' => false,
                'featured_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# Laravel Query Optimization

Write efficient queries and avoid common pitfalls.

## N+1 Query Problem

The classic trap:

```php
// BAD: N+1 queries
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name; // New query each time!
}

// GOOD: Eager loading
$posts = Post::with('author')->get();
foreach ($posts as $post) {
    echo $post->author->name; // No extra queries
}
```

## Select Only What You Need

```php
// BAD: Selecting everything
$users = User::all();

// GOOD: Select specific columns
$users = User::select(['id', 'name', 'email'])->get();
```

## Chunk Large Datasets

```php
// Process 1000 records at a time
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

## Query Scopes

Keep queries reusable:

```php
class Post extends Model {
    public function scopePublished($query) {
        return $query->where('published_at', '<=', now());
    }
    
    public function scopePopular($query) {
        return $query->where('views', '>', 1000);
    }
}

// Usage
$posts = Post::published()->popular()->get();
```

> **Debug Tip**: Use `->toSql()` to see the actual SQL!

## Database Transactions

```php
DB::transaction(function () {
    $user = User::create($userData);
    $profile = Profile::create($profileData);
    $user->profile()->associate($profile);
});
```

**Remember**: Optimize queries early—it's harder to fix later! 📈
MARKDOWN
            ],
            [
                'author' => $author2,
                'title' => 'Docker for Laravel Development',
                'excerpt' => 'Containerize your Laravel application with Docker for consistent development environments. Set up a complete stack with PHP, Nginx, MySQL, and Redis using Docker Compose.',
                'tags' => ['Docker', 'Laravel', 'DevOps', 'Tutorial'],
                'is_featured' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1605745341075-e4c6c8e82f5b?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# Docker for Laravel Development

![Docker Containers](https://images.unsplash.com/photo-1605745341112-85968b19335b?w=800&h=400&fit=crop)

Containerize your Laravel app for consistent development environments.

## Basic Dockerfile

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

CMD ["php-fpm"]
```

## Docker Compose

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "9000:9000"
    volumes:
      - .:/var/www
    networks:
      - laravel

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - laravel

  db:
    image: postgres:16
    environment:
      POSTGRES_DB: laravel
      POSTGRES_USER: laravel
      POSTGRES_PASSWORD: secret
    volumes:
      - pgdata:/var/lib/postgresql/data
    networks:
      - laravel

networks:
  laravel:

volumes:
  pgdata:
```

## Laravel Sail

Or use Laravel's official Docker environment:

```bash
curl -s https://laravel.build/my-app | bash
cd my-app
./vendor/bin/sail up
```

> **Pro Tip**: Use `.dockerignore` to exclude unnecessary files!

```
node_modules/
vendor/
.git/
.env
```

**Conclusion**: Docker eliminates "works on my machine" forever! 🐳
MARKDOWN
            ],
            [
                'author' => $author3,
                'title' => 'Testing Vue Components with Vitest',
                'excerpt' => 'Write confident, fast tests for your Vue 3 components using Vitest. Learn setup, component testing patterns, mocking strategies, and best practices for maintainable test suites.',
                'tags' => ['Testing', 'Vue.js', 'Vitest', 'JavaScript'],
                'is_featured' => false,
                'featured_image' => 'https://images.unsplash.com/photo-1516116216624-53e697fedbea?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# Testing Vue Components with Vitest

Write confident tests for your Vue 3 components.

## Setup

```bash
npm install -D vitest @vue/test-utils happy-dom
```

```javascript
// vitest.config.js
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'happy-dom',
  },
});
```

## Basic Component Test

```javascript
import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Counter from './Counter.vue';

describe('Counter', () => {
  it('increments count when button clicked', async () => {
    const wrapper = mount(Counter);
    
    await wrapper.find('button').trigger('click');
    
    expect(wrapper.text()).toContain('Count: 1');
  });
});
```

## Testing Props

```javascript
it('displays the correct title', () => {
  const wrapper = mount(BlogPost, {
    props: {
      title: 'Test Post',
      author: 'John Doe'
    }
  });
  
  expect(wrapper.find('h1').text()).toBe('Test Post');
});
```

## Testing Events

```javascript
it('emits update event', async () => {
  const wrapper = mount(Input);
  
  await wrapper.find('input').setValue('new value');
  
  expect(wrapper.emitted('update')).toBeTruthy();
  expect(wrapper.emitted('update')[0]).toEqual(['new value']);
});
```

## Mocking Composables

```javascript
import { vi } from 'vitest';

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: { name: 'Test User' },
    isAuthenticated: true,
  }),
}));
```

> **Best Practice**: Test behavior, not implementation details!

**Conclusion**: Good tests catch bugs before users do. 🧪
MARKDOWN
            ],
            [
                'author' => $author3,
                'title' => 'TypeScript with Vue 3',
                'excerpt' => 'Add robust type safety to your Vue 3 applications with TypeScript. Master typed props, composables, generic components, and get better IDE support and fewer runtime errors.',
                'tags' => ['TypeScript', 'Vue.js', 'JavaScript', 'Tutorial'],
                'is_featured' => false,
                'featured_image' => 'https://images.unsplash.com/photo-1587620962725-abab7fe55159?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# TypeScript with Vue 3

Add type safety to your Vue applications.

## Component Props

```vue
<script setup lang="ts">
interface Props {
  title: string;
  count?: number;
  tags: string[];
}

const props = withDefaults(defineProps<Props>(), {
  count: 0,
});

const emit = defineEmits<{
  (e: 'update', value: number): void;
  (e: 'delete'): void;
}>();
</script>
```

## Composables with Types

```typescript
interface User {
  id: number;
  name: string;
  email: string;
}

export function useUser() {
  const user = ref<User | null>(null);
  const loading = ref(false);
  const error = ref<Error | null>(null);
  
  async function fetchUser(id: number): Promise<void> {
    loading.value = true;
    try {
      const response = await api.get<User>(`/users/${id}`);
      user.value = response.data;
    } catch (e) {
      error.value = e as Error;
    } finally {
      loading.value = false;
    }
  }
  
  return { user, loading, error, fetchUser };
}
```

## Generic Components

```vue
<script setup lang="ts" generic="T">
interface Props<T> {
  items: T[];
  keyFn: (item: T) => string | number;
}

const props = defineProps<Props<T>>();
</script>

<template>
  <div v-for="item in items" :key="keyFn(item)">
    <slot :item="item" />
  </div>
</template>
```

> **Pro Tip**: Use `computed` with explicit return types for better inference!

```typescript
const fullName = computed<string>(() => {
  return `${user.value.firstName} ${user.value.lastName}`;
});
```

**Conclusion**: TypeScript prevents bugs and improves DX. Worth the learning curve! 💎
MARKDOWN
            ],
            [
                'author' => $author4,
                'title' => 'CI/CD with GitHub Actions',
                'excerpt' => 'Automate your entire deployment pipeline with GitHub Actions. From running tests and linting to deploying to production, learn how to build reliable CI/CD workflows that save time and prevent errors.',
                'tags' => ['CI/CD', 'GitHub Actions', 'DevOps', 'Automation'],
                'is_featured' => true,
                'featured_image' => 'https://images.unsplash.com/photo-1618401471353-b98afee0b2eb?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# CI/CD with GitHub Actions

![CI/CD Pipeline](https://images.unsplash.com/photo-1667372393119-3d4c48d07fc9?w=800&h=400&fit=crop)

Automate your deployment pipeline with GitHub Actions.

## Basic Workflow

```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_pgsql
          
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
        
      - name: Run tests
        run: ./vendor/bin/pest
```

## Frontend Testing

```yaml
  frontend:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '20'
          cache: 'npm'
          
      - name: Install dependencies
        run: npm ci
        
      - name: Run linter
        run: npm run lint
        
      - name: Run tests
        run: npm run test
```

## Database Testing

```yaml
services:
  postgres:
    image: postgres:16
    env:
      POSTGRES_DB: testing
      POSTGRES_USER: test
      POSTGRES_PASSWORD: test
    options: >-
      --health-cmd pg_isready
      --health-interval 10s
      --health-timeout 5s
      --health-retries 5
```

## Deployment

```yaml
  deploy:
    needs: [test, frontend]
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    
    steps:
      - name: Deploy to production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/app
            git pull origin main
            composer install --no-dev
            php artisan migrate --force
            php artisan config:cache
```

> **Security Tip**: Always use secrets for sensitive data!

**Conclusion**: Automation saves time and prevents human error. Set it and forget it! 🤖
MARKDOWN
            ],
            [
                'author' => $author4,
                'title' => 'Kubernetes Basics for Developers',
                'excerpt' => 'Understanding Kubernetes fundamentals without the overwhelm. Learn pods, deployments, services, and how to deploy your containerized applications to production with confidence.',
                'tags' => ['Kubernetes', 'DevOps', 'Docker', 'Cloud'],
                'is_featured' => false,
                'featured_image' => 'https://images.unsplash.com/photo-1667372393086-9d4001d51cf1?w=1200&h=600&fit=crop',
                'content' => <<<'MARKDOWN'
# Kubernetes Basics for Developers

Understanding K8s fundamentals without the overwhelm.

## Core Concepts

### Pods
The smallest deployable unit:

```yaml
apiVersion: v1
kind: Pod
metadata:
  name: my-app
spec:
  containers:
  - name: app
    image: my-app:latest
    ports:
    - containerPort: 8080
```

### Deployments
Manage replica sets:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: my-app
spec:
  replicas: 3
  selector:
    matchLabels:
      app: my-app
  template:
    metadata:
      labels:
        app: my-app
    spec:
      containers:
      - name: app
        image: my-app:latest
        resources:
          requests:
            memory: "64Mi"
            cpu: "250m"
          limits:
            memory: "128Mi"
            cpu: "500m"
```

## Services

Expose your pods:

```yaml
apiVersion: v1
kind: Service
metadata:
  name: my-app-service
spec:
  selector:
    app: my-app
  ports:
  - protocol: TCP
    port: 80
    targetPort: 8080
  type: LoadBalancer
```

## ConfigMaps and Secrets

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: app-config
data:
  APP_ENV: "production"
  LOG_LEVEL: "info"
```

```yaml
apiVersion: v1
kind: Secret
metadata:
  name: app-secrets
type: Opaque
stringData:
  DATABASE_PASSWORD: "supersecret"
```

## kubectl Commands

```bash
# Get resources
kubectl get pods
kubectl get services
kubectl get deployments

# Describe resource
kubectl describe pod my-app

# View logs
kubectl logs my-app

# Execute commands
kubectl exec -it my-app -- /bin/bash

# Apply configuration
kubectl apply -f deployment.yaml

# Scale deployment
kubectl scale deployment my-app --replicas=5
```

> **Important**: K8s is powerful but complex. Start simple and grow!

**Conclusion**: Kubernetes orchestrates containers at scale. Essential for modern ops! ☸️
MARKDOWN
            ],
        ];

        // Create posts with unique content
        foreach ($blogPosts as $postData) {
            BlogPost::create([
                'user_id' => $postData['author']->id,
                'title' => $postData['title'],
                'slug' => \Illuminate\Support\Str::slug($postData['title']),
                'excerpt' => $postData['excerpt'],
                'content' => $postData['content'],
                'featured_image' => $postData['featured_image'] ?? null,
                'tags' => $postData['tags'],
                'is_featured' => $postData['is_featured'],
                'is_published' => true,
                'published_at' => now()->subDays(fake()->numberBetween(1, 90)),
            ]);
        }

        // Add more posts from regular admins and members with factory-generated content
        foreach ([$admin1, $admin2] as $admin) {
            BlogPost::factory()->count(2)->published()->create([
                'user_id' => $admin->id,
            ]);
        }

        // Regular member posts with varied tags
        $memberTags = [
            ['PHP', 'Laravel'],
            ['JavaScript', 'Frontend'],
            ['Vue.js', 'Tutorial'],
            ['Database', 'PostgreSQL'],
            ['CSS', 'Design'],
            ['Testing', 'Quality'],
        ];

        foreach ($members->take(6) as $index => $member) {
            BlogPost::factory()->count(fake()->numberBetween(1, 3))->create([
                'user_id' => $member->id,
                'tags' => $memberTags[$index % count($memberTags)],
                'is_published' => fake()->boolean(60),
                'published_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-4 months', 'now') : null,
            ]);
        }

        $allPosts = BlogPost::all();
        $publishedPosts = $allPosts->where('is_published', true);

        // Create Comments with Threading
        $this->command->info('Creating comments with replies...');
        
        $commentTexts = [
            "Great article! This really helped me understand the concept better. Thanks for sharing! 👍",
            "I've been looking for this exact solution. Your explanation is clear and concise.",
            "Interesting perspective. Have you considered the performance implications?",
            "This is exactly what I needed for my current project. Bookmarking this!",
            "Could you elaborate more on the section about error handling?",
            "Fantastic write-up! I'll definitely be implementing this in my next project.",
            "I had a different approach, but yours seems much cleaner. Thanks!",
            "One of the best tutorials I've read on this topic. Well done!",
            "Quick question: does this work with the latest version?",
            "Love the code examples. Very practical and easy to follow.",
        ];

        foreach ($publishedPosts as $post) {
            // Popular posts get more comments
            $isPopular = in_array($post->user_id, [$author1->id, $author2->id, $masterAdmin->id]);
            $commentCount = $isPopular ? fake()->numberBetween(8, 20) : fake()->numberBetween(2, 10);

            // Create top-level comments
            $topLevelComments = collect();
            foreach (range(1, $commentCount) as $i) {
                $commenter = $allUsers->where('id', '!=', $post->user_id)->random();
                $comment = Comment::factory()->create([
                    'blog_post_id' => $post->id,
                    'user_id' => $commenter->id,
                    'content' => $commentTexts[array_rand($commentTexts)],
                    'parent_id' => null,
                ]);
                $topLevelComments->push($comment);
            }

            // Add replies to some comments (threading)
            $commentsWithReplies = $topLevelComments->random(min(fake()->numberBetween(2, 5), $topLevelComments->count()));
            foreach ($commentsWithReplies as $parentComment) {
                $replyCount = fake()->numberBetween(1, 3);
                foreach (range(1, $replyCount) as $i) {
                    $replier = $allUsers->where('id', '!=', $parentComment->user_id)->random();
                    Comment::factory()->create([
                        'blog_post_id' => $post->id,
                        'user_id' => $replier->id,
                        'content' => $commentTexts[array_rand($commentTexts)],
                        'parent_id' => $parentComment->id,
                    ]);
                }

                // Sometimes the post author replies
                if (fake()->boolean(40)) {
                    Comment::factory()->create([
                        'blog_post_id' => $post->id,
                        'user_id' => $post->user_id,
                        'content' => "Thanks for the feedback! Glad you found it helpful. 😊",
                        'parent_id' => $parentComment->id,
                    ]);
                }
            }
        }

        // Create Blog Post Likes
        $this->command->info('Creating blog post likes...');
        foreach ($publishedPosts as $post) {
            // Popular posts get more likes
            $isPopular = in_array($post->user_id, [$author1->id, $author2->id, $masterAdmin->id]);
            $likeCount = $isPopular 
                ? fake()->numberBetween(15, 40) 
                : fake()->numberBetween(3, 15);

            $likers = $allUsers->where('id', '!=', $post->user_id)
                ->random(min($likeCount, $allUsers->count() - 1));

            foreach ($likers as $liker) {
                BlogPostLike::factory()->create([
                    'blog_post_id' => $post->id,
                    'user_id' => $liker->id,
                ]);
            }
        }

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📊 Summary:');
        $this->command->info('  Users: ' . User::count() . ' (1 master admin, 6 admins, ' . (User::count() - 7) . ' members)');
        $this->command->info('  Blog Posts: ' . BlogPost::count() . ' (' . $publishedPosts->count() . ' published)');
        $this->command->info('  Comments: ' . Comment::count());
        $this->command->info('  Likes: ' . BlogPostLike::count());
        $this->command->newLine();
        $this->command->info('🔑 Login credentials (all admins):');
        $this->command->info('  Master Admin: ryan@example.com / password');
        $this->command->info('  Admin: sarah@example.com / password');
        $this->command->info('  Admin: mike@example.com / password');
        $this->command->info('  Admin (Author): emily@example.com / password');
        $this->command->info('  Admin (Author): james@example.com / password');
        $this->command->info('  Admin (Author): priya@example.com / password');
        $this->command->info('  Admin (Author): alex@example.com / password');
    }
}
