<script setup lang="ts">
import { computed, ref } from 'vue';
import { useMarkdown } from '@/composables/useMarkdown';

const props = withDefaults(defineProps<{
  content: string | null | undefined;
  fallbackToPlainText?: boolean;
}>(), {
  fallbackToPlainText: true
});

// Use shared markdown configuration
const { render } = useMarkdown();
const hasError = ref(false);

const rendered = computed(() => {
  try {
    hasError.value = false;
    const result = render(props.content);
    
    // Check if rendering resulted in an error display
    if (result.includes('class="text-destructive"') || result.includes('Preview Error')) {
      hasError.value = true;
      
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
    hasError.value = true;
    
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
