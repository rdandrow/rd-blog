<script setup lang="ts">
import { useMarkdown } from '@/composables/useMarkdown';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        content: string | null | undefined;
        fallbackToPlainText?: boolean;
    }>(),
    {
        fallbackToPlainText: true,
    },
);

// Use shared markdown configuration
const { render } = useMarkdown();
const hasError = ref(false);

const rendered = computed(() => {
    try {
        const result = render(props.content);

        // Check if rendering resulted in an error display
        if (
            result.includes('class="text-destructive"') ||
            result.includes('Preview Error')
        ) {
            if (props.fallbackToPlainText && props.content) {
                // Fallback to plain text with basic formatting
                return `<div class="whitespace-pre-wrap text-sm p-4 bg-muted/50 rounded border border-dashed">
          <p class="text-xs text-muted-foreground mb-2 italic">Displaying as plain text due to rendering error:</p>
          ${String(props.content).replace(/</g, '&lt;').replace(/>/g, '&gt;')}
        </div>`;
            }
        }

        return result;
    } catch (error) {
        console.error('MarkdownRender error:', error);

        if (props.fallbackToPlainText && props.content) {
            return `<div class="whitespace-pre-wrap text-sm p-4 bg-muted/50 rounded border border-dashed">
        <p class="text-xs text-muted-foreground mb-2 italic">Displaying as plain text due to critical error:</p>
        ${String(props.content).replace(/</g, '&lt;').replace(/>/g, '&gt;')}
      </div>`;
        }

        return `<div class="text-destructive text-sm p-4 border border-destructive/20 rounded">
      <p>Failed to render content</p>
    </div>`;
    }
});

// Watch for errors in rendering to update hasError ref
watch(
    rendered,
    (newValue) => {
        hasError.value =
            newValue.includes('class="text-destructive"') ||
            newValue.includes('Preview Error') ||
            newValue.includes('Failed to render content');
    },
    { immediate: true },
);
</script>

<template>
    <div v-html="rendered" />

    <!--
    Note: Parent should provide typography classes, e.g.:
    <div class="prose max-w-none dark:prose-invert">
      <MarkdownRender :content="markdown" />
    </div>
  -->
</template>
