<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import MarkdownEditor from './MarkdownEditor.vue';
import { useMarkdown } from '@/composables/useMarkdown';

interface Props {
  modelValue: string;
  label?: string;
  placeholder?: string;
  rows?: number;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  label: 'Content',
  placeholder: 'Write your post content in Markdown…',
  rows: 12,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>();

// Expanded state
const isExpanded = ref(false);
const viewMode = ref<'tab' | 'split'>('tab');

// Local value for v-model
const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

// Markdown rendering for split view preview
const { render } = useMarkdown();
const previewHtml = computed(() => render(localValue.value));

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value;
  if (isExpanded.value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
};

const toggleViewMode = () => {
  viewMode.value = viewMode.value === 'tab' ? 'split' : 'tab';
};

const exitExpanded = () => {
  isExpanded.value = false;
  document.body.style.overflow = '';
};

// Handle keyboard shortcuts
const handleKeydown = (e: KeyboardEvent) => {
  // Escape key to exit expanded mode
  if (e.key === 'Escape' && isExpanded.value) {
    e.preventDefault();
    exitExpanded();
    return;
  }
  
  // Ctrl/Cmd + Shift + S: Toggle split view
  if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'S') {
    e.preventDefault();
    toggleViewMode();
    return;
  }
  
  // Ctrl/Cmd + Shift + E: Toggle expanded mode
  if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'E') {
    e.preventDefault();
    toggleExpanded();
    return;
  }
};

// Add keyboard shortcuts listener on mount
onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

// Cleanup on unmount
onUnmounted(() => {
  document.body.style.overflow = '';
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div class="relative">
    <!-- Normal Mode: Wrapped MarkdownEditor with Controls -->
    <div v-if="!isExpanded">
      <!-- Label and Controls -->
      <div class="flex items-center justify-between mb-2">
        <label class="block text-sm font-medium text-foreground">
          {{ label }} *
        </label>
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="toggleViewMode"
            :title="viewMode === 'tab' ? 'Switch to split view' : 'Switch to tab view'"
            class="px-3 py-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors flex items-center gap-1 border border-border rounded-md hover:bg-muted"
          >
            <svg v-if="viewMode === 'tab'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-5M9 4h10a2 2 0 012 2v5M9 4v16" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span class="hidden sm:inline">{{ viewMode === 'tab' ? 'Split View' : 'Tab View' }}</span>
          </button>
          <button
            type="button"
            @click="toggleExpanded"
            class="px-3 py-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors flex items-center gap-1 border border-border rounded-md hover:bg-muted"
            title="Expand editor to full screen"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            <span class="hidden sm:inline">Expand</span>
          </button>
        </div>
      </div>

      <!-- Content Area -->
      <div>
        <!-- Tab Mode: Use regular MarkdownEditor -->
        <div v-if="viewMode === 'tab'">
          <MarkdownEditor
            v-model="localValue"
            :label="''"
            :placeholder="placeholder"
            :rows="rows"
            class="[&_label]:hidden"
          />
        </div>

        <!-- Split View Mode: Editor + Preview side by side -->
        <div v-else>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Left: Editor textarea -->
            <div class="space-y-2">
              <div class="text-xs text-muted-foreground font-medium">Editor</div>
              <textarea
                v-model="localValue"
                :rows="rows"
                maxlength="50000"
                class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-y font-mono text-sm"
                :placeholder="placeholder"
              ></textarea>
              <div class="flex justify-between text-xs text-muted-foreground">
                <span>{{ localValue.trim() ? localValue.trim().split(/\s+/).filter(w => w.length > 0).length : 0 }} words</span>
                <span>{{ localValue.length.toLocaleString() }} / 50,000 characters</span>
              </div>
            </div>

            <!-- Right: Live Preview -->
            <div class="space-y-2">
              <div class="text-xs text-muted-foreground font-medium">Preview</div>
              <div class="border border-border rounded-md bg-muted/30 p-4 overflow-auto" :style="{ maxHeight: rows * 1.5 + 'rem' }">
                <div v-if="localValue.trim()" class="prose prose-sm dark:prose-invert max-w-none" v-html="previewHtml"></div>
                <div v-else class="text-muted-foreground text-sm italic">Preview will appear here...</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Expanded Overlay Mode -->
    <Teleport to="body">
      <div
        v-if="isExpanded"
        class="fixed inset-0 bg-background z-50 flex flex-col"
        style="padding-top: env(safe-area-inset-top)"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/60">
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="exitExpanded"
              class="p-2 text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
              title="Exit full screen (Esc)"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            <h2 class="text-lg font-semibold text-foreground">{{ label }}</h2>
          </div>
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="toggleViewMode"
              class="px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors flex items-center gap-2 border border-border rounded-md hover:bg-muted"
            >
              <svg v-if="viewMode === 'tab'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-5M9 4h10a2 2 0 012 2v5M9 4v16" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <span>{{ viewMode === 'tab' ? 'Split View' : 'Tab View' }}</span>
            </button>
            <div class="text-sm text-muted-foreground">
              {{ localValue.length.toLocaleString() }} / 50,000
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-hidden flex flex-col">
          <!-- Tab View: Use MarkdownEditor -->
          <div v-if="viewMode === 'tab'" class="flex-1 overflow-auto p-6">
            <MarkdownEditor
              v-model="localValue"
              :label="''"
              :placeholder="placeholder"
              :rows="30"
              class="[&_label]:hidden"
            />
          </div>

          <!-- Split View: Editor + Preview -->
          <div v-else class="flex-1 grid grid-cols-2 gap-6 p-6 min-h-0">
            <!-- Editor -->
            <div class="flex flex-col min-h-0">
              <div class="text-sm font-medium text-foreground mb-3 flex-shrink-0">Editor</div>
              <textarea
                v-model="localValue"
                maxlength="50000"
                class="flex-1 w-full px-4 py-3 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-none font-mono text-sm min-h-0"
                :placeholder="placeholder"
              ></textarea>
              <div class="mt-2 flex justify-between text-xs text-muted-foreground flex-shrink-0">
                <span>{{ localValue.trim() ? localValue.trim().split(/\s+/).filter(w => w.length > 0).length : 0 }} words</span>
                <span>{{ localValue.length.toLocaleString() }} / 50,000 characters</span>
              </div>
            </div>

            <!-- Preview -->
            <div class="flex flex-col min-h-0">
              <div class="text-sm font-medium text-foreground mb-3 flex-shrink-0">Preview</div>
              <div class="flex-1 overflow-y-auto border border-border rounded-md bg-muted/30 p-6 min-h-0">
                <div v-if="localValue.trim()" class="prose prose-sm dark:prose-invert max-w-none" v-html="previewHtml"></div>
                <div v-else class="text-muted-foreground text-sm italic">Preview will appear here...</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
