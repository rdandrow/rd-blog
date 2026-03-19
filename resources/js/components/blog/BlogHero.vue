<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    title: string;
    subtitle: string;
    description: string;
    backgroundImage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    backgroundImage: undefined,
});

// Computed styles for background
const heroStyles = computed(() => {
    if (props.backgroundImage) {
        return {
            backgroundImage: `linear-gradient(rgba(15, 23, 42, 0.6), rgba(15, 23, 42, 0.6)), url(${props.backgroundImage})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
        };
    }
    return {};
});
</script>

<template>
    <section class="relative bg-primary py-20 lg:py-32" :style="heroStyles">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg
                class="h-full w-full"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 100 100"
            >
                <defs>
                    <pattern
                        id="grid"
                        width="10"
                        height="10"
                        patternUnits="userSpaceOnUse"
                    >
                        <path
                            d="M 10 0 L 0 0 0 10"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="0.5"
                        />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>

        <div class="relative container mx-auto max-w-6xl px-4">
            <div class="mx-auto max-w-4xl text-center">
                <!-- Title -->
                <h1
                    class="mb-6 text-4xl leading-tight font-bold text-primary-foreground md:text-6xl"
                >
                    {{ title }}
                </h1>

                <!-- Subtitle -->
                <p
                    class="mb-8 text-xl font-light text-primary-foreground/80 md:text-2xl"
                >
                    {{ subtitle }}
                </p>

                <!-- Description -->
                <p
                    class="mx-auto mb-12 max-w-2xl text-lg leading-relaxed text-primary-foreground/70"
                >
                    {{ description }}
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-secondary px-8 py-4 font-semibold text-secondary-foreground transition-all duration-200 hover:bg-secondary/90 focus:ring-2 focus:ring-ring focus:outline-none"
                    >
                        Latest Articles
                        <ArrowRight class="ml-2 h-5 w-5" />
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border-2 border-primary-foreground/30 px-8 py-4 font-semibold text-primary-foreground/80 transition-all duration-200 hover:border-primary-foreground/50 hover:bg-primary-foreground/5 hover:text-primary-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                    >
                        Subscribe
                    </button>
                </div>
            </div>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 transform">
            <div class="animate-bounce">
                <svg
                    class="h-6 w-6 text-primary-foreground/50"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                    />
                </svg>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Custom gradient animation */

/* Button hover effects */
button {
    transform-origin: center;
}

button:active {
    transform: scale(0.98);
}
</style>
