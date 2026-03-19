<script setup lang="ts">
import AppLandingHeaderLayout from '@/layouts/app/AppLandingHeaderLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const user = page.props.auth.user as any;
// Show landing layout for guests and members, sidebar for admins
const showLandingLayout = !user || user?.role === 'member';
</script>

<template>
    <AppLandingHeaderLayout v-if="showLandingLayout">
        <slot />
    </AppLandingHeaderLayout>
    <AppSidebarLayout v-else :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>
