<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface Author {
    id: number;
    name: string;
}

interface Filters {
    search?: string | null;
    tag?: string | null;
    author?: string | null;
}

interface Props {
    filters: Filters;
    availableTags: string[];
    availableAuthors: Author[];
    currentRoute?: string;
}

const props = withDefaults(defineProps<Props>(), {
    currentRoute: '/'
});

// Local state for form inputs
const searchInput = ref(props.filters.search || '');
const selectedTag = ref(props.filters.tag || '');
const selectedAuthor = ref(props.filters.author || '');

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
    });
};

// Check if any filters are active
const hasActiveFilters = () => {
    return searchInput.value || selectedTag.value || selectedAuthor.value;
};
</script>

<template>
    <div class="space-y-4 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Search & Filter
            </h3>
            <button
                v-if="hasActiveFilters()"
                @click="clearFilters"
                class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors"
            >
                Clear All
            </button>
        </div>

        <!-- Search Input -->
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Search Posts
            </label>
            <input
                id="search"
                v-model="searchInput"
                type="text"
                placeholder="Search by title, excerpt, or content..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                @keydown.enter="applyFilters"
            />
        </div>

        <!-- Tag Filter -->
        <div>
            <label for="tag" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Filter by Tag
            </label>
            <select
                id="tag"
                v-model="selectedTag"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
                <option value="">All Tags</option>
                <option v-for="tag in availableTags" :key="tag" :value="tag">
                    {{ tag }}
                </option>
            </select>
        </div>

        <!-- Author Filter -->
        <div>
            <label for="author" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Filter by Author
            </label>
            <select
                id="author"
                v-model="selectedAuthor"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
                <option value="">All Authors</option>
                <option v-for="author in availableAuthors" :key="author.id" :value="author.id">
                    {{ author.name }}
                </option>
            </select>
        </div>

        <!-- Apply Button -->
        <button
            @click="applyFilters"
            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        >
            Apply Filters
        </button>
    </div>
</template>
