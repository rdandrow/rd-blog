<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { Line } from 'vue-chartjs';

defineProps<{
    data: ChartData<'line'>;
    options: ChartOptions<'line'>;
    heightClass?: string;
}>();
</script>

<template>
    <div :class="heightClass ?? 'h-64'">
        <Line
            :data="data"
            :options="options"
        />
    </div>
</template>
