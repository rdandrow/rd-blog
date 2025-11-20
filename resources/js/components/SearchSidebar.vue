<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Author, SearchFilters } from '@/types';

interface Props {
    isOpen: boolean;
    filters: SearchFilters;
    availableTags: string[];
    availableAuthors: Author[];
    currentRoute?: string;
}

interface Emits {
    (e: 'close'): void;
}

const props = withDefaults(defineProps<Props>(), {
    currentRoute: '/'
});

const emit = defineEmits<Emits>();

// Local state for form inputs
const searchInput = ref(props.filters.search || '');
const selectedTag = ref(props.filters.tag || '');
const selectedAuthor = ref(props.filters.author || '');

// Watch for filter prop changes (when page loads with existing filters)
watch(() => props.filters, (newFilters) => {
    searchInput.value = newFilters.search || '';
    selectedTag.value = newFilters.tag || '';
    selectedAuthor.value = newFilters.author || '';
}, { deep: true });

// Apply filters by updating URL query parameters
const applyFilters = () => {
    const params: Record<string, string> = {};
    
    if (searchInput.value) {
        params.search = searchInput.value;
    }
    
    if (selectedTag.value) {
        params.tag = selectedTag.value;
    }
    
    if (selectedAuthor.value) {
        params.author = selectedAuthor.value;
    }
    
    router.get(props.currentRoute, params, {
        preserveState: false,
        preserveScroll: false,
        onSuccess: () => {
            emit('close');
        }
    });
};

// Clear all filters
const clearFilters = () => {
    searchInput.value = '';
    selectedTag.value = '';
    selectedAuthor.value = '';
    
    router.get(props.currentRoute, {}, {
        preserveState: false,
        preserveScroll: false,
        onSuccess: () => {
            emit('close');
        }
    });
};

// Check if any filters are active - using computed property
const hasActiveFilters = computed(() => {
    return !!(searchInput.value || selectedTag.value || selectedAuthor.value);
});

// Close sidebar when clicking backdrop
const handleBackdropClick = () => {
    emit('close');
};

// Prevent closing when clicking inside sidebar
const handleSidebarClick = (event: Event) => {
    event.stopPropagation();
};
</script>

<template>
    <!-- Backdrop Overlay -->
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 bg-black/50 z-40 backdrop-blur-sm"
            @click="handleBackdropClick"
        ></div>
    </Transition>

    <!-- Sidebar -->
    <Transition
        enter-active-class="transition-transform duration-300"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <div
            v-if="isOpen"
            class="fixed right-0 top-0 h-full w-full sm:w-96 bg-white dark:bg-gray-900 shadow-2xl z-50 overflow-y-auto"
            @click="handleSidebarClick"
        >
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Search & Filter
                </h2>
                <button
                    @click="emit('close')"
                    class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    aria-label="Close search"
                >
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Sidebar Content -->
            <div class="p-6 space-y-6">
                <!-- Active Filters Badge -->
                <div v-if="hasActiveFilters" class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Filters Active</span>
                    </div>
                    <button
                        @click="clearFilters"
                        class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors"
                    >
                        Clear All
                    </button>
                </div>

                <!-- Search Input -->
                <div>
                    <label for="sidebar-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Search Posts
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            id="sidebar-search"
                            v-model="searchInput"
                            type="text"
                            placeholder="Search by title, excerpt, or content..."
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                            @keydown.enter="applyFilters"
                        />
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Press Enter to search
                    </p>
                </div>

                <!-- Tag Filter -->
                <div>
                    <label for="sidebar-tag" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Filter by Tag
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <select
                            id="sidebar-tag"
                            v-model="selectedTag"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white appearance-none cursor-pointer"
                        >
                            <option value="">All Tags</option>
                            <option v-for="tag in availableTags" :key="tag" :value="tag">
                                {{ tag }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    <p v-if="availableTags.length === 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        No tags available
                    </p>
                </div>

                <!-- Author Filter -->
                <div>
                    <label for="sidebar-author" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Filter by Author
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <select
                            id="sidebar-author"
                            v-model="selectedAuthor"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white appearance-none cursor-pointer"
                        >
                            <option value="">All Authors</option>
                            <option v-for="author in availableAuthors" :key="author.id" :value="author.id">
                                {{ author.name }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    <p v-if="availableAuthors.length === 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        No authors available
                    </p>
                </div>

                <!-- Apply Button -->
                <button
                    @click="applyFilters"
                    class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 shadow-sm"
                >
                    Apply Filters
                </button>

                <!-- Results Info -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                        <span v-if="hasActiveFilters">
                            Filtering results based on your selection
                        </span>
                        <span v-else>
                            Select filters to narrow down results
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Custom scrollbar for sidebar */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.5);
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.7);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(75, 85, 99, 0.5);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(75, 85, 99, 0.7);
}
</style>
