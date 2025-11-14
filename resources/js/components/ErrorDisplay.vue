<script setup lang="ts">
interface Props {
  error?: string | null;
  type?: 'error' | 'warning' | 'info';
  dismissible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  error: null,
  type: 'error',
  dismissible: false,
});

const emit = defineEmits<{
  dismiss: [];
}>();

const getIconPath = (type: string): string => {
  switch (type) {
    case 'warning':
      return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z';
    case 'info':
      return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    default:
      return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
  }
};

const getBgClass = (type: string): string => {
  switch (type) {
    case 'warning':
      return 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/10 dark:border-yellow-800';
    case 'info':
      return 'bg-blue-50 border-blue-200 dark:bg-blue-900/10 dark:border-blue-800';
    default:
      return 'bg-destructive/10 border-destructive/20';
  }
};

const getTextClass = (type: string): string => {
  switch (type) {
    case 'warning':
      return 'text-yellow-700 dark:text-yellow-300';
    case 'info':
      return 'text-blue-700 dark:text-blue-300';
    default:
      return 'text-destructive';
  }
};

const getIconClass = (type: string): string => {
  switch (type) {
    case 'warning':
      return 'text-yellow-500 dark:text-yellow-400';
    case 'info':
      return 'text-blue-500 dark:text-blue-400';
    default:
      return 'text-destructive';
  }
};
</script>

<template>
  <div 
    v-if="error" 
    :class="['p-4 border rounded-lg', getBgClass(type)]"
  >
    <div class="flex items-center gap-2">
      <svg 
        :class="['w-5 h-5 flex-shrink-0', getIconClass(type)]" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
      >
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          :d="getIconPath(type)" 
        />
      </svg>
      
      <span :class="['font-medium flex-1', getTextClass(type)]">
        {{ error }}
      </span>
      
      <button
        v-if="dismissible"
        @click="emit('dismiss')"
        :class="['ml-2 hover:opacity-70 transition-opacity', getIconClass(type)]"
        aria-label="Dismiss"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</template>