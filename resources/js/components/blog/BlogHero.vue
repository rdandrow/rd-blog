<script setup lang="ts">
import { computed } from 'vue';
import { ArrowRight } from 'lucide-vue-next';

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
  <section 
    class="relative py-20 lg:py-32 bg-primary"
    :style="heroStyles"
  >
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
      <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
        <defs>
          <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid)" />
      </svg>
    </div>

    <div class="relative container mx-auto px-4 max-w-6xl">
      <div class="max-w-4xl mx-auto text-center">
        <!-- Title -->
        <h1 class="text-4xl md:text-6xl font-bold text-primary-foreground mb-6 leading-tight">
          {{ title }}
        </h1>

        <!-- Subtitle -->
        <p class="text-xl md:text-2xl text-primary-foreground/80 mb-8 font-light">
          {{ subtitle }}
        </p>

        <!-- Description -->
        <p class="text-lg text-primary-foreground/70 mb-12 max-w-2xl mx-auto leading-relaxed">
          {{ description }}
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            type="button"
            class="inline-flex items-center px-8 py-4 bg-secondary hover:bg-secondary/90 text-secondary-foreground font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-ring"
          >
            Latest Articles
            <ArrowRight class="ml-2 w-5 h-5" />
          </button>
          
          <button
            type="button"
            class="inline-flex items-center px-8 py-4 border-2 border-primary-foreground/30 hover:border-primary-foreground/50 text-primary-foreground/80 hover:text-primary-foreground font-semibold rounded-lg transition-all duration-200 hover:bg-primary-foreground/5 focus:outline-none focus:ring-2 focus:ring-ring"
          >
            Subscribe
          </button>
        </div>
      </div>
    </div>

    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2">
      <div class="animate-bounce">
        <svg class="w-6 h-6 text-primary-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Custom gradient animation */
@keyframes gradient-shift {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.bg-gradient-to-br {
  background-size: 200% 200%;
  animation: gradient-shift 8s ease-in-out infinite;
}

/* Smooth scroll behavior */
html {
  scroll-behavior: smooth;
}

/* Button hover effects */
button {
  transform-origin: center;
}

button:active {
  transform: scale(0.98);
}
</style>