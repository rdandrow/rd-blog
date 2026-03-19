<script setup lang="ts">
import { cn } from '@/lib/utils';
import * as icons from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    name: string;
    class?: string;
    size?: number | string;
    color?: string;
    strokeWidth?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
    size: 16,
    strokeWidth: 2,
});

const className = computed(() => cn('h-4 w-4', props.class));

const normalizedSize = computed(() => {
    const numericSize = Number(props.size);
    return Number.isFinite(numericSize) && numericSize > 0 ? props.size : 24;
});

const icon = computed(() => {
    const iconName = props.name.charAt(0).toUpperCase() + props.name.slice(1);
    return (icons as Record<string, any>)[iconName];
});
</script>

<template>
    <component
        :is="icon"
        :class="className"
        :size="normalizedSize"
        :stroke-width="strokeWidth"
        :color="color"
    />
</template>
