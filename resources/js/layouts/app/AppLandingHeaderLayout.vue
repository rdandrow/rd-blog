<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import UserDropdown from '@/components/UserDropdown.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user as any);
</script>

<template>
    <AppShell class="flex-col">
        <!-- Landing Page Style Header -->
        <header class="border-b border-[#19140035] dark:border-[#3E3E3A]">
            <div class="mx-auto max-w-7xl px-6 py-4">
                <nav class="flex items-center justify-between">
                    <div class="flex items-center gap-8">
                        <h1 class="text-2xl font-bold">Blog</h1>
                        <Link
                            href="/blog"
                            class="text-sm hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            All Posts
                        </Link>
                    </div>
                    <div class="flex items-center gap-4">
                        <template v-if="user">
                            <!-- User dropdown for members -->
                            <UserDropdown
                                :userName="user.name"
                                :userRole="user.role"
                            />
                        </template>
                    </div>
                </nav>
            </div>
        </header>
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>
