<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';

// Reactive state
const isMobileMenuOpen = ref(false);

// Navigation items
const navigationItems = [
  { name: 'Home', href: '/' },
  { name: 'Blog', href: '/blog', current: true },
  { name: 'About', href: '/about' },
  { name: 'Contact', href: '/contact' },
];

// Methods
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false;
};

// Computed
const currentYear = computed(() => new Date().getFullYear());
</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-background border-b border-border">
      <nav class="container mx-auto px-4" aria-label="Main navigation">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <div class="flex items-center">
            <Link 
              href="/" 
              class="flex items-center space-x-2 text-xl font-bold text-foreground hover:text-primary transition-colors"
              @click="closeMobileMenu"
            >
              <AppLogo class="w-8 h-8" />
              <span>Blog</span>
            </Link>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden md:flex items-center space-x-8">
            <div class="flex space-x-6">
              <Link
                v-for="item in navigationItems"
                :key="item.name"
                :href="item.href"
                :class="[
                  item.current 
                    ? 'text-primary font-medium' 
                    : 'text-muted-foreground hover:text-foreground',
                  'px-3 py-2 text-sm font-medium transition-colors rounded-md hover:bg-accent'
                ]"
                :aria-current="item.current ? 'page' : undefined"
              >
                {{ item.name }}
              </Link>
            </div>
          </div>

          <!-- Mobile menu button -->
          <div class="md:hidden">
            <button
              type="button"
              @click="toggleMobileMenu"
              class="p-2 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
              :aria-expanded="isMobileMenuOpen"
              aria-label="Toggle main menu"
            >
              <svg v-if="!isMobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </nav>

      <!-- Mobile Navigation -->
      <div v-if="isMobileMenuOpen" class="md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 bg-background border-b border-border">
          <Link
            v-for="item in navigationItems"
            :key="item.name"
            :href="item.href"
            :class="[
              item.current 
                ? 'bg-accent text-primary' 
                : 'text-muted-foreground hover:bg-accent hover:text-foreground',
              'block px-3 py-2 rounded-md text-base font-medium transition-colors'
            ]"
            :aria-current="item.current ? 'page' : undefined"
            @click="closeMobileMenu"
          >
            {{ item.name }}
          </Link>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-card border-t border-border">
      <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Brand -->
          <div class="col-span-1 md:col-span-2">
            <Link 
              href="/" 
              class="flex items-center space-x-2 text-xl font-bold text-foreground hover:text-primary transition-colors mb-4"
            >
              <AppLogo class="w-8 h-8" />
              <span>Blog</span>
            </Link>
            <p class="text-muted-foreground max-w-md">
              Sharing insights, tutorials, and stories about web development, technology, and innovation.
            </p>
          </div>

          <!-- Quick Links -->
          <div>
            <h3 class="text-lg font-semibold mb-4 text-foreground">Quick Links</h3>
            <ul class="space-y-2">
              <li v-for="item in navigationItems" :key="item.name">
                <Link 
                  :href="item.href"
                  class="text-muted-foreground hover:text-foreground transition-colors"
                >
                  {{ item.name }}
                </Link>
              </li>
            </ul>
          </div>

          <!-- Connect -->
          <div>
            <h3 class="text-lg font-semibold mb-4 text-foreground">Connect</h3>
            <ul class="space-y-2">
              <li>
                <a href="#" class="text-muted-foreground hover:text-foreground transition-colors">
                  Twitter
                </a>
              </li>
              <li>
                <a href="#" class="text-muted-foreground hover:text-foreground transition-colors">
                  GitHub
                </a>
              </li>
              <li>
                <a href="#" class="text-muted-foreground hover:text-foreground transition-colors">
                  LinkedIn
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div class="border-t border-border mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
          <p class="text-muted-foreground text-sm">
            &copy; {{ currentYear }} Blog. All rights reserved.
          </p>
          <div class="flex space-x-6 mt-4 md:mt-0">
            <a href="#" class="text-muted-foreground hover:text-foreground text-sm transition-colors">
              Privacy Policy
            </a>
            <a href="#" class="text-muted-foreground hover:text-foreground text-sm transition-colors">
              Terms of Service
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Ensure smooth transitions */
* {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>