# Blog Application

A modern full-stack blog platform built with Laravel 11 and Vue.js 3, featuring enterprise-grade architecture, type safety, and comprehensive authentication.

## Tech Stack

**Backend:** Laravel 11 • PHP 8.2+ • Inertia.js • SQLite/MySQL  
**Frontend:** Vue.js 3 (Composition API) • TypeScript • Tailwind CSS v4 • Vite  
**Testing:** Pest PHP • Feature & Unit Tests

## Key Features

- Full authentication system with 2FA support
- Complete blog CMS with CRUD operations
- Responsive design with dark mode
- Search and filtering capabilities
- Type-safe routing with Laravel Wayfinder
- WCAG-compliant accessibility

## Architecture & Best Practices

### Backend (Laravel 11)

**Service Layer Pattern** - Business logic extracted from controllers
```php
// Services handle complex operations
app/Services/
├── BlogPostService.php    # Query logic, filtering, data aggregation
└── BlogImageService.php   # File uploads, deletions, transformations
```

**Form Requests** - Validation separated from controllers
```php
app/Http/Requests/
├── StoreBlogPostRequest.php
└── UpdateBlogPostRequest.php
```

**API Resources** - Consistent data transformation
```php
app/Http/Resources/
└── BlogPostResource.php   # Standardized JSON responses
```

**Policy-Based Authorization** - Granular access control
```php
app/Policies/
└── BlogPostPolicy.php     # Owner-based permissions
```

**Model Best Practices**
- Typed relationships (`BelongsTo`, `HasMany`)
- Eloquent scopes for reusable queries (`published()`, `featured()`)
- Model events in `boot()` for slug generation & reading time
- Proper casts for JSON, dates, and booleans
- Strict return type hints on all methods

**Controller Structure**
- Thin controllers delegating to services
- Dependency injection for type safety
- No business logic in routes or controllers
- Proper separation of admin vs public endpoints

### Frontend (Vue.js 3)

**Composition API Best Practices**
```typescript
// Shared composables for reusable logic
resources/js/composables/
├── useBlogUtils.ts      # formatDate, date utilities
└── useSearchState.ts    # Search sidebar state management
```

**TypeScript Integration**
```typescript
// Centralized type definitions
resources/js/types/index.d.ts
├── BlogPost interface
├── Author interface  
└── SearchFilters interface
```

**Reactive Patterns**
- `toRefs()` for proper reactive destructuring
- `computed()` for derived state
- `v-memo` for performance optimization on lists

**Component Organization**
```
resources/js/
├── components/         # Reusable UI components
├── composables/        # Shared reactive logic
├── layouts/            # Page layouts
├── pages/              # Route components
└── types/              # TypeScript definitions
```

**Performance Optimizations**
- Lazy loading images with native `loading="lazy"`
- v-memo directives on list items
- Centralized CSS utilities (no duplicates)
- Optimized animations in global styles

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── BlogPostController.php       # Admin CRUD
│   │   └── PublicBlogController.php     # Public routes
│   ├── Requests/                        # Form validation
│   └── Resources/                       # API transformations
├── Models/
│   ├── BlogPost.php                     # Typed relationships & scopes
│   └── User.php                         # Fortify authentication
├── Policies/                            # Authorization rules
└── Services/                            # Business logic layer

resources/js/
├── components/                          # Shared Vue components
├── composables/                         # Reusable composition functions
├── pages/                               # Inertia page components
└── types/                               # TypeScript definitions

routes/
├── web.php                              # Thin routes (no closures)
└── settings.php                         # User settings routes
```

## Quick Start

```bash
# Install dependencies
composer install && npm install

# Setup environment
cp .env.example .env && php artisan key:generate

# Database & seed
php artisan migrate --seed

# Build assets & serve
npm run build
php artisan serve
```

Visit `http://localhost:8000`

**Default credentials after seeding:**
- Email: `admin@example.com`
- Password: `password`

## Testing

```bash
# Run all tests
./vendor/bin/pest

# With coverage
./vendor/bin/pest --coverage

# Specific suites
./vendor/bin/pest tests/Feature/Auth/
```

**Test Coverage:**
- Authentication flows (login, register, 2FA)
- Blog CRUD operations
- Authorization policies
- Dashboard access

## Production Deployment

```bash
# Build assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set production environment
APP_ENV=production
APP_DEBUG=false
```

## Database Configuration

Edit the following lines within `.env`
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

## Framework Documentation

- [Laravel 11 Docs](https://laravel.com/docs/11.x)
- [Vue.js 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Inertia.js Guide](https://inertiajs.com/)
- [Tailwind CSS v4](https://tailwindcss.com/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
