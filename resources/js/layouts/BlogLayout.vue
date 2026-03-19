<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UserDropdown from '@/components/UserDropdown.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();

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

const signOut = () => {
    router.post('/logout');
};

// Computed
const currentYear = computed(() => new Date().getFullYear());
const user = computed(() => page.props.auth?.user as any);
const isLoggedIn = computed(() => !!user.value);
const isMember = computed(() => user.value?.role === 'member');
const isAdmin = computed(
    () =>
        user.value &&
        (user.value.role === 'admin' || user.value.role === 'master_admin'),
);
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-border bg-background">
            <nav class="container mx-auto px-4" aria-label="Main navigation">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link
                            href="/"
                            class="flex items-center space-x-2 text-xl font-bold text-foreground transition-colors hover:text-primary"
                            @click="closeMobileMenu"
                        >
                            <AppLogo class="h-8 w-8" />
                            <span>Blog</span>
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden items-center space-x-8 md:flex">
                        <div class="flex space-x-6">
                            <Link
                                v-for="item in navigationItems"
                                :key="item.name"
                                :href="item.href"
                                :class="[
                                    item.current
                                        ? 'font-medium text-primary'
                                        : 'text-muted-foreground hover:text-foreground',
                                    'rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent',
                                ]"
                                :aria-current="
                                    item.current ? 'page' : undefined
                                "
                            >
                                {{ item.name }}
                            </Link>
                        </div>

                        <!-- User Authentication -->
                        <div v-if="isLoggedIn" class="flex items-center gap-3">
                            <!-- Dashboard link for admins -->
                            <Link
                                v-if="isAdmin"
                                href="/admin/dashboard"
                                class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                            >
                                Dashboard
                            </Link>

                            <!-- User dropdown for members -->
                            <UserDropdown
                                v-if="isMember"
                                :userName="user.name"
                                :userRole="user.role"
                            />

                            <!-- Sign out button for admins -->
                            <button
                                v-if="isAdmin"
                                @click="signOut"
                                class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            >
                                Sign out
                            </button>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button
                            type="button"
                            @click="toggleMobileMenu"
                            class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            :aria-expanded="isMobileMenuOpen"
                            aria-label="Toggle main menu"
                        >
                            <svg
                                v-if="!isMobileMenuOpen"
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                            <svg
                                v-else
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Mobile Navigation -->
            <div v-if="isMobileMenuOpen" class="md:hidden">
                <div
                    class="space-y-1 border-b border-border bg-background px-2 pt-2 pb-3"
                >
                    <Link
                        v-for="item in navigationItems"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            item.current
                                ? 'bg-accent text-primary'
                                : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                            'block rounded-md px-3 py-2 text-base font-medium transition-colors',
                        ]"
                        :aria-current="item.current ? 'page' : undefined"
                        @click="closeMobileMenu"
                    >
                        {{ item.name }}
                    </Link>

                    <!-- Mobile User Menu -->
                    <div
                        v-if="isLoggedIn"
                        class="mt-4 space-y-1 border-t border-border pt-4"
                    >
                        <!-- User info -->
                        <div class="px-3 py-2 text-sm">
                            <p class="font-medium text-foreground">
                                {{ user.name }}
                            </p>
                            <p class="text-xs text-muted-foreground capitalize">
                                {{ user.role.replace('_', ' ') }}
                            </p>
                        </div>

                        <!-- Dashboard link for admins -->
                        <Link
                            v-if="isAdmin"
                            href="/admin/dashboard"
                            class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            @click="closeMobileMenu"
                        >
                            Dashboard
                        </Link>

                        <!-- Settings links for members -->
                        <template v-if="isMember">
                            <Link
                                href="/settings/profile"
                                class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                @click="closeMobileMenu"
                            >
                                Profile
                            </Link>
                            <Link
                                href="/settings/password"
                                class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                @click="closeMobileMenu"
                            >
                                Password
                            </Link>
                            <Link
                                href="/settings/two-factor"
                                class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                @click="closeMobileMenu"
                            >
                                Two-Factor Auth
                            </Link>
                            <Link
                                href="/settings/appearance"
                                class="block rounded-md px-3 py-2 text-base font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                @click="closeMobileMenu"
                            >
                                Appearance
                            </Link>
                        </template>

                        <!-- Sign out -->
                        <button
                            @click="signOut"
                            class="w-full rounded-md px-3 py-2 text-left text-base font-medium text-red-600 transition-colors hover:bg-accent"
                        >
                            Sign out
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-border bg-card">
            <div class="container mx-auto px-4 py-12">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                    <!-- Brand -->
                    <div class="col-span-1 md:col-span-2">
                        <Link
                            href="/"
                            class="mb-4 flex items-center space-x-2 text-xl font-bold text-foreground transition-colors hover:text-primary"
                        >
                            <AppLogo class="h-8 w-8" />
                            <span>Blog</span>
                        </Link>
                        <p class="max-w-md text-muted-foreground">
                            Sharing insights, tutorials, and stories about web
                            development, technology, and innovation.
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="mb-4 text-lg font-semibold text-foreground">
                            Quick Links
                        </h3>
                        <ul class="space-y-2">
                            <li
                                v-for="item in navigationItems"
                                :key="item.name"
                            >
                                <Link
                                    :href="item.href"
                                    class="text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    {{ item.name }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Connect -->
                    <div>
                        <h3 class="mb-4 text-lg font-semibold text-foreground">
                            Connect
                        </h3>
                        <ul class="space-y-2">
                            <li>
                                <a
                                    href="#"
                                    class="text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Twitter
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    GitHub
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    LinkedIn
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="mt-12 flex flex-col items-center justify-between border-t border-border pt-8 md:flex-row"
                >
                    <p class="text-sm text-muted-foreground">
                        &copy; {{ currentYear }} Blog. All rights reserved.
                    </p>
                    <div class="mt-4 flex space-x-6 md:mt-0">
                        <a
                            href="#"
                            class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Privacy Policy
                        </a>
                        <a
                            href="#"
                            class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Terms of Service
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<!-- Removed universal transition (*) for better performance - use Tailwind transition utilities instead -->
