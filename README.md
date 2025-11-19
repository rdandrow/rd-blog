# Blog Application

A modern, full-stack blog application built with Laravel 11 and Vue.js 3, featuring a complete content management system with authentication, CRUD operations, and a beautiful public-facing blog.

## 🚀 Tech Stack

### Backend
- **[Laravel 11](https://laravel.com/)** - PHP web application framework
- **[PHP 8.2+](https://www.php.net/)** - Server-side programming language
- **[Inertia.js](https://inertiajs.com/)** - Modern monolith approach (Laravel + Vue.js)
- **[Laravel Fortify](https://laravel.com/docs/fortify)** - Authentication backend
- **[Laravel Wayfinder](https://laravel.com/docs/wayfinder)** - Type-safe routing for Laravel and Vue.js

### Frontend
- **[Vue.js 3](https://vuejs.org/)** - Progressive JavaScript framework
- **[TypeScript](https://www.typescriptlang.org/)** - Static type checking
- **[Composition API](https://vuejs.org/guide/composition-api.html)** - Modern Vue.js development pattern
- **[Tailwind CSS v4](https://tailwindcss.com/)** - Utility-first CSS framework
- **[Reka UI](https://reka-ui.com/)** - Unstyled, accessible UI components
- **[Lucide Vue Next](https://lucide.dev/)** - Beautiful & consistent icon toolkit

### Build Tools & Development
- **[Vite](https://vitejs.dev/)** - Fast build tool and development server
- **[ESLint](https://eslint.org/)** - JavaScript/TypeScript linting
- **[Prettier](https://prettier.io/)** - Code formatting
- **[Pest PHP](https://pestphp.com/)** - Testing framework

### Database & Storage
- **SQLite** - Lightweight database (configurable to MySQL/PostgreSQL)
- **Eloquent ORM** - Laravel's database abstraction layer

## 📋 Features

### 🔐 Authentication System
- User registration and login
- Email verification
- Password reset functionality  
- Two-factor authentication (2FA) support
- Session management

### 📝 Blog Management
- **Admin Dashboard** - Full CRUD operations for blog posts
- **Rich Content Editor** - Create and edit blog posts with excerpts
- **Content Organization** - Categories, tags, and featured posts
- **Publication Control** - Draft/published states with scheduling
- **SEO Features** - Automatic slug generation and meta information
- **Reading Time Calculation** - Automatic estimation based on content

### 🌐 Public Blog Interface
- **Landing Page** - Beautiful homepage with featured and recent posts
- **Blog Listing** - Paginated list of all published posts
- **Individual Post Pages** - Full post view with author information
- **Responsive Design** - Mobile-first approach with dark mode support

### 🎨 UI/UX Features
- **Dark/Light Mode** - System preference detection and manual toggle
- **Responsive Layout** - Optimized for all device sizes
- **Accessibility** - WCAG compliant with proper semantic HTML
- **Loading States** - Smooth user experience with loading indicators
- **Error Handling** - Graceful error states and user feedback

## 🏗 Architecture

### Directory Structure
```
├── app/
│   ├── Http/Controllers/        # Laravel controllers
│   │   ├── BlogPostController.php
│   │   └── Settings/
│   ├── Models/                  # Eloquent models
│   │   ├── BlogPost.php
│   │   └── User.php
│   └── Policies/               # Authorization policies
├── database/
│   ├── migrations/             # Database schema
│   ├── factories/              # Model factories
│   └── seeders/               # Database seeders
├── resources/
│   ├── js/
│   │   ├── components/        # Reusable Vue components
│   │   ├── layouts/           # Layout components
│   │   ├── pages/            # Page components
│   │   │   ├── Admin/BlogPosts/  # Admin CRUD pages
│   │   │   └── Welcome.vue       # Landing page
│   │   └── types/            # TypeScript definitions
│   └── css/                  # Stylesheets
├── routes/
│   ├── web.php              # Web routes
│   └── settings.php         # Settings routes
└── tests/                   # Test suites
```

### Data Models

#### BlogPost Model
```php
class BlogPost extends Model
{
    // Core fields
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content',
        'featured_image', 'tags', 'is_featured',
        'is_published', 'published_at', 'user_id'
    ];

    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopePublished($query)
    public function scopeFeatured($query)
}
```

#### User Model
```php
class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;
    
    // Supports Laravel Fortify features
    // Two-factor authentication ready
    // Email verification support
}
```

### Frontend Architecture

#### Component Structure
- **Layouts**: Reusable layout components with sidebar navigation
- **Pages**: Route-specific page components
- **Components**: Shared UI components (forms, cards, etc.)
- **Composables**: Reusable logic functions

#### Type Safety
- Full TypeScript integration
- Inertia.js type definitions
- Custom interfaces for all data structures
- Type-safe routing with Laravel Wayfinder

## 🚦 Getting Started

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (or MySQL/PostgreSQL)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd rd-blog
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=BlogPostSeeder  # Optional: seed with sample data
   ```

5. **Build assets**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

The application will be available at `http://localhost:8000`

### Quick Setup
Alternatively, use the automated setup script:
```bash
composer run setup
```

## 🔧 Configuration

### Database Configuration
Update `.env` for your preferred database:

```env
# SQLite (default)
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Authentication Features
Configure Laravel Fortify features in `config/fortify.php`:
```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

## 📖 Usage

### Admin Interface
1. Register a new account at `/register`
2. Verify your email (if enabled)
3. Access the admin dashboard at `/dashboard`
4. Manage blog posts at `/admin/blog-posts`

### Admin Features
- **Create Posts**: Rich content editor with title, excerpt, and content
- **Manage Posts**: Full CRUD operations with search and filtering
- **Publication Control**: Draft/published states with scheduling
- **Featured Posts**: Highlight important content
- **Tag Management**: Organize posts with custom tags

### Public Blog
- **Homepage**: Featured and recent posts at `/`
- **All Posts**: Complete blog listing at `/blog`  
- **Individual Posts**: Full post view at `/blog/{slug}`

## 🧪 Testing

The application includes a comprehensive test suite using Pest PHP:

```bash
# Run all tests
./vendor/bin/pest

# Run specific test suites
./vendor/bin/pest tests/Feature/
./vendor/bin/pest tests/Unit/

# Run with coverage
./vendor/bin/pest --coverage
```

### Test Structure
- **Feature Tests**: Full application workflow testing
- **Unit Tests**: Individual component testing
- **Authentication Tests**: Login, registration, and 2FA flows
- **Blog Management Tests**: CRUD operations and permissions

## 🚀 Deployment

### Production Build
```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Variables
Ensure production environment variables are set:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

## 🤝 Contributing

### Development Workflow
1. Follow Laravel and Vue.js best practices
2. Use TypeScript for all new JavaScript code
3. Write tests for new features
4. Use conventional commit messages
5. Format code with Prettier and lint with ESLint

### Code Standards
- **PHP**: Follow PSR-12 coding standards
- **JavaScript/TypeScript**: ESLint configuration with Vue.js rules
- **CSS**: Tailwind CSS utility classes preferred
- **Vue.js**: Composition API with `<script setup>` syntax

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [TypeScript Documentation](https://www.typescriptlang.org/docs/)

---

Built with ❤️ using Laravel and Vue.js