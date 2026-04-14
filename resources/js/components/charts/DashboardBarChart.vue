<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { Bar } from 'vue-chartjs';

defineProps<{
    data: ChartData<'bar'>;
    options: ChartOptions<'bar'>;
    heightClass?: string;
}>();
</script>

<template>
    <div :class="heightClass ?? 'h-72'">
        <Bar
            :data="data"
            :options="options"
        />
    </div>
</template>
