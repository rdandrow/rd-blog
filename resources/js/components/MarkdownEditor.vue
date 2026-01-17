<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
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

// Local state mirrors v-model
const value = ref(props.modelValue);
const tab = ref<'write' | 'preview'>('write');
const textareaRef = ref<HTMLTextAreaElement | null>(null);

// Use shared markdown configuration
const { render } = useMarkdown();
const rendered = ref('');
const isRendering = ref(false);
const renderError = ref<string | null>(null);
const lastSuccessfulRender = ref('');
const validationWarnings = ref<string[]>([]);

// Image upload state
const isUploadingImage = ref(false);
const uploadProgress = ref<string>('');
const isDraggingOver = ref(false);

// Keyboard navigation state
const focusedButtonIndex = ref(-1);
const toolbarRef = ref<HTMLElement | null>(null);

// Character and word count
const characterCount = computed(() => value.value.length);
const wordCount = computed(() => {
  const text = value.value.trim();
  if (!text) return 0;
  // Split by whitespace and filter out empty strings
  return text.split(/\s+/).filter(word => word.length > 0).length;
});

import { MAX_BLOG_POST_CHARACTERS, MAX_IMAGE_FILE_SIZE, ALLOWED_IMAGE_TYPES } from '@/constants/editor';
const maxCharacters = MAX_BLOG_POST_CHARACTERS;

// Enhanced debounce function with cancel capability
const debounce = <T extends (...args: any[]) => any>(
  func: T,
  delay: number
): ((...args: Parameters<T>) => void) & { cancel: () => void } => {
  let timeoutId: ReturnType<typeof setTimeout>;
  
  const debounced = (...args: Parameters<T>) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func(...args), delay);
  };
  
  debounced.cancel = () => {
    clearTimeout(timeoutId);
  };
  
  return debounced;
};

const renderMarkdown = () => {
  isRendering.value = true;
  renderError.value = null;
  
  try {
    // Validate content before rendering
    validationWarnings.value = validateMarkdownContent(value.value);
    
    const result = render(value.value);
    
    // Check if the result indicates an error
    if (result.includes('class="text-destructive"') || result.includes('Preview Error')) {
      renderError.value = 'Markdown preview encountered an error';
      // Keep the last successful render visible
      if (lastSuccessfulRender.value) {
        rendered.value = lastSuccessfulRender.value;
      } else {
        rendered.value = result; // Show error if no previous successful render
      }
    } else {
      rendered.value = result;
      lastSuccessfulRender.value = result; // Store successful render
      renderError.value = null;
    }
  } catch (error) {
    console.error('Critical render error:', error);
    renderError.value = error instanceof Error ? error.message : 'Critical rendering error';
    
    // Fallback to plain text if all else fails
    if (!lastSuccessfulRender.value) {
      rendered.value = `<pre class="whitespace-pre-wrap text-sm p-4 bg-muted rounded border">${value.value}</pre>`;
    }
  } finally {
    isRendering.value = false;
  }
};

// Debounced version with 300ms delay for optimal UX
const debouncedRenderMarkdown = debounce(renderMarkdown, 300);

// Content validation functions
const validateMarkdownContent = (content: string): string[] => {
  const warnings: string[] = [];
  const lines = content.split('\n');
  
  // First, remove code blocks from validation to avoid false positives
  let contentWithoutCodeBlocks = content;
  const codeBlockRegex = /```[\s\S]*?```/g;
  const inlineCodeRegex = /`[^`]+`/g;
  
  // Extract code blocks to check if they're closed
  const codeBlockMatches = content.match(/```/g);
  if (codeBlockMatches && codeBlockMatches.length % 2 !== 0) {
    warnings.push('Unclosed code block detected');
  }
  
  // Remove code blocks and inline code for other validations
  contentWithoutCodeBlocks = contentWithoutCodeBlocks.replace(codeBlockRegex, '');
  contentWithoutCodeBlocks = contentWithoutCodeBlocks.replace(inlineCodeRegex, '');
  
  // Check for unclosed emphasis/bold (only outside code blocks)
  const boldMatches = contentWithoutCodeBlocks.match(/\*\*/g);
  if (boldMatches && boldMatches.length % 2 !== 0) {
    warnings.push('Unclosed bold formatting (**) detected');
  }
  
  // Check for unclosed italic formatting, excluding list markers
  // Remove list markers (*, -, +) at the start of lines to avoid false positives
  let contentWithoutLists = contentWithoutCodeBlocks;
  contentWithoutLists = contentWithoutLists.replace(/^\s*[-*+]\s+/gm, ''); // Remove list markers
  
  // Count properly paired italic patterns (single asterisk pairs, not bold)
  const pairedItalics = contentWithoutLists.match(/\*(?!\*)([^*]+?)\*/g);
  const pairedCount = pairedItalics ? pairedItalics.length * 2 : 0;
  
  // Count all remaining single asterisks (not part of bold **)
  const allSingleAsterisks = contentWithoutLists.match(/(?<!\*)\*(?!\*)/g);
  const totalSingleAsterisks = allSingleAsterisks ? allSingleAsterisks.length : 0;
  
  // If there are unpaired single asterisks, warn
  if (totalSingleAsterisks > pairedCount) {
    warnings.push('Possible unclosed italic formatting (*) detected');
  }
  
  // Check for malformed links (only outside code blocks)
  const malformedLinks = contentWithoutCodeBlocks.match(/\[[^\]]*\]\([^)]*$/gm);
  if (malformedLinks && malformedLinks.length > 0) {
    warnings.push('Incomplete link formatting detected');
  }
  
  // Check for malformed headers (these are always line-based, so code blocks don't affect them)
  lines.forEach((line, index) => {
    // Skip lines that are inside code blocks
    if (line.trim().startsWith('```')) return;
    
    if (line.match(/^#{7,}/)) {
      warnings.push(`Invalid header level on line ${index + 1} (max 6 # allowed)`);
    }
    if (line.match(/^#+\S/)) {
      warnings.push(`Missing space after # on line ${index + 1}`);
    }
  });
  
  // Check for malformed lists (skip code block lines)
  let inCodeBlock = false;
  lines.forEach((line, index) => {
    if (line.trim().startsWith('```')) {
      inCodeBlock = !inCodeBlock;
      return;
    }
    if (inCodeBlock) return;
    
    // Check for numbered lists with invalid format
    if (line.match(/^\s*\d+[^\.\s]/)) {
      warnings.push(`Invalid numbered list format on line ${index + 1} (missing period and space)`);
    }
    
    // Check for bullet lists without proper spacing
    if (line.match(/^\s*[-*+][^\s]/)) {
      warnings.push(`Missing space after bullet on line ${index + 1}`);
    }
  });
  
  // Check for inconsistent list markers (skip code blocks)
  const bulletLines = lines.filter((line, index) => {
    // Skip if in code block
    const linesBeforeCurrent = lines.slice(0, index);
    const codeBlockStartCount = linesBeforeCurrent.filter(l => l.trim().startsWith('```')).length;
    const isInCodeBlock = codeBlockStartCount % 2 !== 0;
    
    return !isInCodeBlock && line.match(/^\s*[-*+]\s+/);
  });
  
  if (bulletLines.length > 1) {
    const markers = bulletLines.map(line => line.match(/^\s*([-*+])/)?.[1]);
    const uniqueMarkers = new Set(markers);
    if (uniqueMarkers.size > 1) {
      warnings.push('Inconsistent bullet list markers (mixing -, *, +)');
    }
  }
  
  return warnings;
};

// Keyboard navigation functions will be defined after the action functions

const handleKeyboardShortcuts = (event: KeyboardEvent) => {
  // Only handle shortcuts when in write mode and textarea is focused
  if (tab.value !== 'write' || !textareaRef.value || document.activeElement !== textareaRef.value) {
    return;
  }

  const isCtrl = event.ctrlKey || event.metaKey;
  
  if (isCtrl) {
    switch (event.key.toLowerCase()) {
      case '1':
        event.preventDefault();
        makeH1();
        break;
      case '2':
        event.preventDefault();
        makeH2();
        break;
      case '3':
        event.preventDefault();
        makeH3();
        break;
      case 'b':
        event.preventDefault();
        makeBold();
        break;
      case 'i':
        event.preventDefault();
        makeItalic();
        break;
      case 'x':
        if (event.shiftKey) {
          event.preventDefault();
          makeStrikethrough();
        }
        break;
      case 'k':
        event.preventDefault();
        makeLink();
        break;
      case 'e':
        event.preventDefault();
        makeCodeInline();
        break;
      case 'c':
        if (event.shiftKey) {
          event.preventDefault();
          makeCodeBlock();
        }
        break;
      case '8':
        if (event.shiftKey) {
          event.preventDefault();
          makeBulletedList();
        }
        break;
      case '7':
        if (event.shiftKey) {
          event.preventDefault();
          makeNumberedList();
        }
        break;
      case 't':
        if (event.shiftKey) {
          event.preventDefault();
          makeTable();
        }
        break;
      case '.':
        if (event.shiftKey) {
          event.preventDefault();
          makeQuote();
        }
        break;
    }
  }
};

const handleToolbarKeyNavigation = (event: KeyboardEvent) => {
  const buttons = toolbarRef.value?.querySelectorAll('button[data-toolbar-button]');
  if (!buttons || buttons.length === 0) return;

  const currentIndex = focusedButtonIndex.value;
  let newIndex = currentIndex;

  switch (event.key) {
    case 'ArrowRight':
    case 'ArrowDown':
      event.preventDefault();
      newIndex = currentIndex < buttons.length - 1 ? currentIndex + 1 : 0;
      break;
    case 'ArrowLeft':
    case 'ArrowUp':
      event.preventDefault();
      newIndex = currentIndex > 0 ? currentIndex - 1 : buttons.length - 1;
      break;
    case 'Home':
      event.preventDefault();
      newIndex = 0;
      break;
    case 'End':
      event.preventDefault();
      newIndex = buttons.length - 1;
      break;
    case 'Escape':
      event.preventDefault();
      // Return focus to textarea
      textareaRef.value?.focus();
      focusedButtonIndex.value = -1;
      return;
    case 'Enter':
    case ' ':
      event.preventDefault();
      if (currentIndex >= 0 && currentIndex < buttons.length) {
        (buttons[currentIndex] as HTMLButtonElement).click();
        // Return focus to textarea after action
        setTimeout(() => {
          textareaRef.value?.focus();
          focusedButtonIndex.value = -1;
        }, 0);
      }
      return;
    default:
      return;
  }

  focusedButtonIndex.value = newIndex;
  (buttons[newIndex] as HTMLButtonElement).focus();
};

const handleButtonFocus = (index: number) => {
  focusedButtonIndex.value = index;
};

const handleButtonBlur = () => {
  // Small delay to check if focus moved to another toolbar button
  setTimeout(() => {
    const buttons = toolbarRef.value?.querySelectorAll('button[data-toolbar-button]');
    const activeElement = document.activeElement;
    const isToolbarButtonFocused = buttons && Array.from(buttons).includes(activeElement as HTMLButtonElement);
    
    if (!isToolbarButtonFocused) {
      focusedButtonIndex.value = -1;
    }
  }, 0);
};

const handleTextareaKeyDown = (event: KeyboardEvent) => {
  // Handle Tab key to navigate to toolbar
  if (event.key === 'Tab' && event.ctrlKey) {
    event.preventDefault();
    const buttons = toolbarRef.value?.querySelectorAll('button[data-toolbar-button]');
    if (buttons && buttons.length > 0) {
      focusedButtonIndex.value = 0;
      (buttons[0] as HTMLButtonElement).focus();
    }
    return;
  }
  
  // Handle Enter key for smart line breaks and list continuation
  if (event.key === 'Enter') {
    const el = textareaRef.value;
    if (!el) return;
    
    const text = value.value ?? '';
    const cursorPos = el.selectionStart ?? 0;
    
    // Find the current line
    const lineStart = text.lastIndexOf('\n', cursorPos - 1) + 1;
    const lineEnd = text.indexOf('\n', cursorPos);
    const currentLine = text.slice(lineStart, lineEnd === -1 ? text.length : lineEnd);
    
    // Check for list patterns
    const bulletMatch = currentLine.match(/^(\s*)([-*+])\s+(.*)$/);
    const numberedMatch = currentLine.match(/^(\s*)(\d+)\.\s+(.*)$/);
    const headerMatch = currentLine.match(/^(#{1,6})\s+(.*)$/);
    
    if (bulletMatch) {
      const [, indent, bullet, content] = bulletMatch;
      if (!content.trim()) {
        // Empty list item - remove it and return to normal line
        event.preventDefault();
        const newText = text.slice(0, lineStart) + indent + text.slice(cursorPos);
        value.value = newText;
        nextTick(() => {
          const newCursor = lineStart + indent.length;
          el.setSelectionRange(newCursor, newCursor);
        });
      } else {
        // Continue the list
        event.preventDefault();
        const continuation = `\n${indent}${bullet} `;
        const newText = text.slice(0, cursorPos) + continuation + text.slice(cursorPos);
        value.value = newText;
        nextTick(() => {
          const newCursor = cursorPos + continuation.length;
          el.setSelectionRange(newCursor, newCursor);
        });
      }
    } else if (numberedMatch) {
      const [, indent, num, content] = numberedMatch;
      if (!content.trim()) {
        // Empty numbered item - remove it and return to normal line
        event.preventDefault();
        const newText = text.slice(0, lineStart) + indent + text.slice(cursorPos);
        value.value = newText;
        nextTick(() => {
          const newCursor = lineStart + indent.length;
          el.setSelectionRange(newCursor, newCursor);
        });
      } else {
        // Continue the numbered list
        event.preventDefault();
        const nextNum = parseInt(num) + 1;
        const continuation = `\n${indent}${nextNum}. `;
        const newText = text.slice(0, cursorPos) + continuation + text.slice(cursorPos);
        value.value = newText;
        nextTick(() => {
          const newCursor = cursorPos + continuation.length;
          el.setSelectionRange(newCursor, newCursor);
        });
      }
    } else if (headerMatch && cursorPos >= lineStart + currentLine.length) {
      // After a header, add an extra line break for proper spacing
      event.preventDefault();
      const newText = text.slice(0, cursorPos) + '\n\n' + text.slice(cursorPos);
      value.value = newText;
      nextTick(() => {
        const newCursor = cursorPos + 2;
        el.setSelectionRange(newCursor, newCursor);
      });
    }
  }
};

// Function to handle tab switching with immediate render
const switchToPreview = () => {
  tab.value = 'preview';
  // Cancel any pending debounced render and render immediately
  debouncedRenderMarkdown.cancel();
  renderMarkdown();
};

// Function to handle switching back to write mode
const switchToWrite = () => {
  tab.value = 'write';
  // Clear validation warnings when switching to write mode
  validationWarnings.value = [];
};

watch(() => props.modelValue, (v) => {
  value.value = v;
  if (tab.value === 'preview') {
    // Immediate render for external changes
    renderMarkdown();
  }
});

watch(value, (v) => {
  emit('update:modelValue', v);
  // Use debounced render for user typing
  if (tab.value === 'preview') {
    debouncedRenderMarkdown();
  }
});

// Selection helpers for toolbar actions
type Selection = { start: number; end: number; selected: string };

const getSelection = (): Selection => {
  const el = textareaRef.value;
  const text = value.value ?? '';
  if (!el) return { start: 0, end: 0, selected: '' };
  const start = el.selectionStart ?? 0;
  const end = el.selectionEnd ?? 0;
  return { start, end, selected: text.slice(start, end) };
};

const replaceSelection = async (before: string, after: string, placeholder = '') => {
  const el = textareaRef.value;
  const text = value.value ?? '';
  if (!el) return;
  const { start, end, selected } = getSelection();
  const inner = selected || placeholder;
  const newText = text.slice(0, start) + before + inner + after + text.slice(end);
  const caretPos = start + before.length + inner.length;
  value.value = newText;
  await nextTick();
  // restore focus and selection
  el.focus();
  el.setSelectionRange(caretPos, caretPos);
};

const wrapLines = async (prefix: string, ordered = false) => {
  const el = textareaRef.value;
  const text = value.value ?? '';
  if (!el) return;
  const { start, end } = getSelection();
  
  // Expand selection to full lines
  const lineStart = text.lastIndexOf('\n', start - 1) + 1;
  const lineEnd = text.indexOf('\n', end);
  const selEnd = lineEnd === -1 ? text.length : lineEnd;
  const block = text.slice(lineStart, selEnd);
  const lines = block.split('\n');
  
  const isHeader = prefix.startsWith('#');
  const isList = prefix.startsWith('-') || prefix.match(/^\d+\.\s/);
  
  const transformed = lines.map((l, idx) => {
    if (!l.trim()) return l; // leave empty lines
    
    // Remove existing formatting
    let cleanLine = l;
    if (isHeader) {
      // Remove existing headers
      cleanLine = l.replace(/^#{1,6}\s+/, '');
    } else if (isList) {
      // Remove existing list markers
      cleanLine = l.replace(/^(\s*)([-*+]|\d+\.)\s+/, '$1');
    }
    
    if (ordered) {
      // For numbered lists, preserve indentation and add sequential numbers
      const indent = l.match(/^\s*/)?.[0] || '';
      return `${indent}${idx + 1}. ${cleanLine.trim()}`;
    } else if (isList) {
      // For bullet lists, preserve indentation
      const indent = l.match(/^\s*/)?.[0] || '';
      return `${indent}${prefix}${cleanLine.trim()}`;
    } else {
      // For headers and other formatting, apply to the whole line
      return `${prefix}${cleanLine.trim()}`;
    }
  }).join('\n');
  
  const newText = text.slice(0, lineStart) + transformed + text.slice(selEnd);
  value.value = newText;
  await nextTick();
  el.focus();
  
  // Place caret at end of transformed block
  const newCaret = lineStart + transformed.length;
  el.setSelectionRange(newCaret, newCaret);
};

// Toolbar actions
const makeBold = () => replaceSelection('**', '**', 'bold');
const makeItalic = () => replaceSelection('*', '*', 'italic');
const makeStrikethrough = () => replaceSelection('~~', '~~', 'strikethrough');
const makeCodeInline = () => replaceSelection('`', '`', 'code');
const makeCodeBlock = () => replaceSelection('\n```javascript\n', '\n```\n', 'code');
const makeLink = () => replaceSelection('[', '](https://)', 'link-text');
const makeBulletedList = () => wrapLines('- ');
const makeNumberedList = () => wrapLines('1. ', true);
const makeQuote = () => wrapLines('> ');

const makeTable = async () => {
  const el = textareaRef.value;
  const text = value.value ?? '';
  if (!el) return;
  const { start, end } = getSelection();
  
  // Create a 3x3 table template
  const table = `
| Header 1 | Header 2 | Header 3 |
|----------|----------|----------|
| Cell 1   | Cell 2   | Cell 3   |
| Cell 4   | Cell 5   | Cell 6   |
`;
  
  const newText = text.slice(0, start) + table + text.slice(end);
  value.value = newText;
  await nextTick();
  el.focus();
  
  // Place cursor at the first header cell for easy editing
  const headerStart = start + table.indexOf('Header 1');
  el.setSelectionRange(headerStart, headerStart + 'Header 1'.length);
};

// Header actions
const makeHeader = (level: number) => {
  const prefix = '#'.repeat(level) + ' ';
  wrapLines(prefix, false);
};

const makeH1 = () => makeHeader(1);
const makeH2 = () => makeHeader(2);
const makeH3 = () => makeHeader(3);

// Image upload functionality
const uploadImage = async (file: File): Promise<void> => {
  // Validate file type - must match backend accepted formats
  if (!(ALLOWED_IMAGE_TYPES as readonly string[]).includes(file.type)) {
    uploadProgress.value = 'Error: Only JPEG, PNG, GIF, and WebP images are allowed';
    setTimeout(() => uploadProgress.value = '', 3000);
    return;
  }

  if (file.size > MAX_IMAGE_FILE_SIZE) {
    uploadProgress.value = 'Error: Image must be less than 2MB';
    setTimeout(() => uploadProgress.value = '', 3000);
    return;
  }

  isUploadingImage.value = true;
  uploadProgress.value = `Uploading ${file.name}...`;

  const formData = new FormData();
  formData.append('image', file);

  try {
    const response = await fetch('/admin/blog-posts/upload-image', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    // Check if response is HTML (likely a redirect or error page)
    const contentType = response.headers.get('content-type');
    if (contentType && contentType.includes('text/html')) {
      throw new Error('Authentication required. Please refresh the page and try again.');
    }

    if (!response.ok) {
      let errorMessage = 'Upload failed';
      try {
        const error = await response.json();
        
        // Validate that we received a valid error object
        if (error && typeof error === 'object') {
          // Try standard error message fields
          errorMessage = error.error || error.message || errorMessage;
          
          // Handle validation errors (Laravel format)
          if (error.errors && typeof error.errors === 'object') {
            const errorValues = Object.values(error.errors);
            if (errorValues.length > 0) {
              const firstError = errorValues[0];
              if (Array.isArray(firstError) && firstError.length > 0) {
                errorMessage = String(firstError[0]);
              } else if (firstError) {
                errorMessage = String(firstError);
              }
            }
          }
        }
      } catch {
        // If JSON parsing fails or any error occurs, use status text
        errorMessage = response.statusText || errorMessage;
      }
      throw new Error(errorMessage);
    }

    const data = await response.json();
    
    if (!data.url) {
      throw new Error('Invalid response from server');
    }
    
    // Insert markdown image syntax at cursor position
    const el = textareaRef.value;
    if (!el) return;

    const text = value.value ?? '';
    const start = el.selectionStart ?? 0;
    const end = el.selectionEnd ?? 0;
    
    const imageMarkdown = `![${file.name.replace(/\.[^/.]+$/, '')}](${data.url})`;
    const newText = text.slice(0, start) + imageMarkdown + text.slice(end);
    
    value.value = newText;
    
    await nextTick();
    
    // Position cursor after inserted image
    const newCursorPos = start + imageMarkdown.length;
    el.focus();
    el.setSelectionRange(newCursorPos, newCursorPos);
    
    uploadProgress.value = 'Image uploaded successfully!';
    setTimeout(() => uploadProgress.value = '', 2000);
  } catch (error) {
    console.error('Image upload error:', error);
    uploadProgress.value = error instanceof Error ? `Error: ${error.message}` : 'Upload failed';
    setTimeout(() => uploadProgress.value = '', 3000);
  } finally {
    isUploadingImage.value = false;
  }
};

// Handle paste events for images
const handlePaste = async (event: ClipboardEvent) => {
  const items = event.clipboardData?.items;
  if (!items) return;

  for (const item of Array.from(items)) {
    if (item.type.startsWith('image/')) {
      event.preventDefault();
      const file = item.getAsFile();
      if (file) {
        await uploadImage(file);
      }
      break;
    }
  }
};

// Handle drag and drop events
const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  isDraggingOver.value = true;
};

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault();
  isDraggingOver.value = false;
};

const handleDrop = async (event: DragEvent) => {
  event.preventDefault();
  isDraggingOver.value = false;

  const files = event.dataTransfer?.files;
  if (!files || files.length === 0) return;

  // Upload the first image file found
  for (const file of Array.from(files)) {
    if (file.type.startsWith('image/')) {
      await uploadImage(file);
      break;
    }
  }
};

// Toolbar button definitions for keyboard navigation
const toolbarButtons = [
  { action: makeH1, label: 'Heading 1', shortcut: 'Ctrl/Cmd+1', category: 'headers' },
  { action: makeH2, label: 'Heading 2', shortcut: 'Ctrl/Cmd+2', category: 'headers' },
  { action: makeH3, label: 'Heading 3', shortcut: 'Ctrl/Cmd+3', category: 'headers' },
  { action: makeBold, label: 'Bold', shortcut: 'Ctrl/Cmd+B', category: 'format' },
  { action: makeItalic, label: 'Italic', shortcut: 'Ctrl/Cmd+I', category: 'format' },
  { action: makeStrikethrough, label: 'Strikethrough', shortcut: 'Ctrl/Cmd+Shift+X', category: 'format' },
  { action: makeLink, label: 'Link', shortcut: 'Ctrl/Cmd+K', category: 'format' },
  { action: makeCodeInline, label: 'Inline Code', shortcut: 'Ctrl/Cmd+E', category: 'format' },
  { action: makeCodeBlock, label: 'Code Block', shortcut: 'Ctrl/Cmd+Shift+C', category: 'format' },
  { action: makeBulletedList, label: 'Bulleted List', shortcut: 'Ctrl/Cmd+Shift+8', category: 'lists' },
  { action: makeNumberedList, label: 'Numbered List', shortcut: 'Ctrl/Cmd+Shift+7', category: 'lists' },
  { action: makeQuote, label: 'Quote', shortcut: 'Ctrl/Cmd+Shift+.', category: 'format' },
  { action: makeTable, label: 'Table', shortcut: 'Ctrl/Cmd+Shift+T', category: 'structure' },
];

// Add keyboard event listeners
onMounted(() => {
  if (tab.value === 'preview') renderMarkdown();
  
  // Add global keyboard shortcut listener
  document.addEventListener('keydown', handleKeyboardShortcuts);
});

// Remove event listener on unmount
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyboardShortcuts);
});
</script>

<template>
  <div>
    <label class="block text-sm font-medium text-foreground mb-2">
      {{ label }} *
    </label>

    <!-- Tabs -->
    <div class="flex items-center gap-2 mb-2 border-b border-border">
      <button
        type="button"
        :class="[
          'px-3 py-1.5 text-sm -mb-px border-b-2',
          tab === 'write' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'
        ]"
        @click="switchToWrite"
      >
        Write
      </button>
      <button
        type="button"
        :class="[
          'px-3 py-1.5 text-sm -mb-px border-b-2',
          tab === 'preview' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'
        ]"
        @click="switchToPreview"
      >
        Preview
      </button>
    </div>

    <!-- Toolbar (Write mode) -->
    <div 
      v-if="tab === 'write'" 
      ref="toolbarRef"
      class="flex flex-wrap items-center gap-2 mb-2"
      role="toolbar"
      aria-label="Markdown formatting toolbar"
      @keydown="handleToolbarKeyNavigation"
    >
      <!-- Headers Group -->
      <div class="flex items-center gap-1 border-r border-border pr-2">
        <button 
          v-for="(button, index) in toolbarButtons.filter(b => b.category === 'headers')"
          :key="button.label"
          type="button" 
          :data-toolbar-button="toolbarButtons.findIndex(b => b === button)"
          :tabindex="index === 0 ? '0' : '-1'"
          class="px-2 py-1 text-sm rounded border border-border hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1 transition-colors"
          :class="{ 'ring-2 ring-ring ring-offset-1': focusedButtonIndex === toolbarButtons.findIndex(b => b === button) }"
          :aria-label="`${button.label} (${button.shortcut})`"
          :title="`${button.label} - ${button.shortcut}`"
          @click="button.action"
          @focus="handleButtonFocus(toolbarButtons.findIndex(b => b === button))"
          @blur="handleButtonBlur"
        >
          <span v-if="button.label === 'Heading 1'" class="font-bold text-lg">H1</span>
          <span v-else-if="button.label === 'Heading 2'" class="font-semibold">H2</span>
          <span v-else-if="button.label === 'Heading 3'" class="font-medium text-sm">H3</span>
        </button>
      </div>
      
      <!-- Format Group -->
      <div class="flex items-center gap-1 border-r border-border pr-2">
        <button 
          v-for="button in toolbarButtons.filter(b => b.category === 'format')"
          :key="button.label"
          type="button" 
          :data-toolbar-button="toolbarButtons.findIndex(b => b === button)"
          :tabindex="-1"
          class="px-2 py-1 text-sm rounded border border-border hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1 transition-colors"
          :class="{ 'ring-2 ring-ring ring-offset-1': focusedButtonIndex === toolbarButtons.findIndex(b => b === button) }"
          :aria-label="`${button.label} (${button.shortcut})`"
          :title="`${button.label} - ${button.shortcut}`"
          @click="button.action"
          @focus="handleButtonFocus(toolbarButtons.findIndex(b => b === button))"
          @blur="handleButtonBlur"
        >
          <span v-if="button.label === 'Bold'" class="font-semibold">B</span>
          <span v-else-if="button.label === 'Italic'" class="italic">I</span>
          <span v-else-if="button.label === 'Strikethrough'" class="line-through">S</span>
          <span v-else-if="button.label === 'Link'">Link</span>
          <span v-else-if="button.label === 'Inline Code'">`code`</span>
          <span v-else-if="button.label === 'Code Block'">```</span>
          <span v-else-if="button.label === 'Quote'">❝ Quote</span>
        </button>
      </div>
      
      <!-- Lists Group -->
      <div class="flex items-center gap-1 border-r border-border pr-2">
        <button 
          v-for="button in toolbarButtons.filter(b => b.category === 'lists')"
          :key="button.label"
          type="button" 
          :data-toolbar-button="toolbarButtons.findIndex(b => b === button)"
          :tabindex="-1"
          class="px-2 py-1 text-sm rounded border border-border hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1 transition-colors"
          :class="{ 'ring-2 ring-ring ring-offset-1': focusedButtonIndex === toolbarButtons.findIndex(b => b === button) }"
          :aria-label="`${button.label} (${button.shortcut})`"
          :title="`${button.label} - ${button.shortcut}`"
          @click="button.action"
          @focus="handleButtonFocus(toolbarButtons.findIndex(b => b === button))"
          @blur="handleButtonBlur"
        >
          <span v-if="button.label === 'Bulleted List'">• List</span>
          <span v-else-if="button.label === 'Numbered List'">1. List</span>
        </button>
      </div>
      
      <!-- Structure Group -->
      <div class="flex items-center gap-1">
        <button 
          v-for="button in toolbarButtons.filter(b => b.category === 'structure')"
          :key="button.label"
          type="button" 
          :data-toolbar-button="toolbarButtons.findIndex(b => b === button)"
          :tabindex="-1"
          class="px-2 py-1 text-sm rounded border border-border hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1 transition-colors"
          :class="{ 'ring-2 ring-ring ring-offset-1': focusedButtonIndex === toolbarButtons.findIndex(b => b === button) }"
          :aria-label="`${button.label} (${button.shortcut})`"
          :title="`${button.label} - ${button.shortcut}`"
          @click="button.action"
          @focus="handleButtonFocus(toolbarButtons.findIndex(b => b === button))"
          @blur="handleButtonBlur"
        >
          <span v-if="button.label === 'Table'">⊞ Table</span>
        </button>
      </div>
      
      <!-- Keyboard shortcuts hint -->
      <div class="ml-2 text-xs text-muted-foreground hidden sm:block">
        Press Tab to navigate toolbar, or use shortcuts
      </div>
    </div>

    <!-- Image upload status -->
    <div v-if="uploadProgress" class="mb-2 p-2 rounded text-sm flex items-center gap-2" :class="uploadProgress.startsWith('Error') ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary'">
      <svg v-if="isUploadingImage" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <svg v-else-if="!uploadProgress.startsWith('Error')" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
      <span>{{ uploadProgress }}</span>
    </div>

    <!-- Write (with drag-drop overlay) -->
    <div v-if="tab === 'write'" class="relative">
      <textarea
        ref="textareaRef"
        v-model="value"
        :rows="rows"
        :maxlength="maxCharacters"
        :class="[
          'w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-y',
          isDraggingOver && 'ring-2 ring-primary'
        ]"
        :placeholder="placeholder"
        @keydown="handleTextareaKeyDown"
        @paste="handlePaste"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
      />
      
      <!-- Drag overlay -->
      <div
        v-if="isDraggingOver"
        class="absolute inset-0 bg-primary/10 border-2 border-dashed border-primary rounded-md flex items-center justify-center pointer-events-none"
      >
        <div class="text-center">
          <svg class="w-12 h-12 mx-auto mb-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <p class="text-sm font-medium text-primary">Drop image to upload</p>
        </div>
      </div>
    </div>

    <!-- Preview -->
    <div
      v-else
      class="prose prose-sm dark:prose-invert max-w-none bg-muted/30 border border-border rounded-md p-4 relative"
    >
      <!-- Loading indicator -->
      <div v-if="isRendering" class="absolute top-2 right-2 flex items-center gap-1 text-xs text-muted-foreground">
        <div class="w-3 h-3 border border-current border-t-transparent rounded-full animate-spin"></div>
        Rendering...
      </div>
      
      <!-- Error indicator -->
      <div v-if="renderError" class="absolute top-2 left-2 flex items-center gap-1 text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded border border-amber-200">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        Preview Error
      </div>
      
      <!-- Rendered content -->
      <div v-html="rendered" />
      
      <!-- Empty state -->
      <div v-if="!value.trim() && !isRendering" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
        <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-sm">Start typing to see your markdown preview</p>
      </div>
    </div>

    <!-- Character and word count -->
    <div class="mt-2 flex items-center justify-between text-xs">
      <div class="text-muted-foreground">
        {{ wordCount.toLocaleString() }} {{ wordCount === 1 ? 'word' : 'words' }}
      </div>
      <div 
        :class="[
          'font-medium',
          characterCount > maxCharacters * 0.95 ? 'text-destructive' : 
          characterCount > maxCharacters * 0.9 ? 'text-amber-600' : 
          'text-muted-foreground'
        ]"
      >
        {{ characterCount.toLocaleString() }} / {{ maxCharacters.toLocaleString() }} characters
      </div>
    </div>

    <!-- Validation warnings -->
    <div v-if="validationWarnings.length > 0" class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-md">
      <div class="flex items-start gap-2">
        <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <div class="flex-1">
          <p class="text-sm font-medium text-amber-800">Markdown Syntax Issues</p>
          <ul class="mt-1 text-xs text-amber-700 space-y-1">
            <li v-for="warning in validationWarnings" :key="warning">• {{ warning }}</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Keyboard shortcuts help -->
    <div class="mt-2 text-xs text-muted-foreground space-y-1">
      <p>Supports Markdown: headings, lists, links, images, code, and more.</p>
            <details class="cursor-pointer">
        <summary class="hover:text-foreground">Tables</summary>
        <div class="mt-2 text-xs bg-muted/30 p-3 rounded border space-y-3">
          <div>
            <p class="font-medium text-foreground mb-2">Create tables with pipes and dashes:</p>
            <div class="bg-background/50 p-2 rounded font-mono text-xs space-y-1">
              <div>| Header 1 | Header 2 | Header 3 |</div>
              <div>|----------|----------|----------|</div>
              <div>| Cell 1   | Cell 2   | Cell 3   |</div>
              <div>| Cell 4   | Cell 5   | Cell 6   |</div>
            </div>
          </div>
          <div>
            <p class="font-medium text-foreground mb-1">Tips:</p>
            <ul class="ml-4 space-y-1">
              <li>• Use <kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+T</kbd> to insert a template</li>
              <li>• Pipes <code>|</code> separate columns</li>
              <li>• Second row defines the header separator</li>
              <li>• Alignment is optional (spaces for readability)</li>
            </ul>
          </div>
        </div>
      </details>
      <details class="cursor-pointer">
        <summary class="hover:text-foreground">Image Upload</summary>
        <div class="mt-2 text-xs bg-muted/30 p-3 rounded border space-y-3">
          <div>
            <p class="font-medium text-foreground mb-2">Upload images directly to your post:</p>
            <ul class="space-y-1 ml-4">
              <li>• <kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+V</kbd> Paste images from your clipboard</li>
              <li>• Drag and drop image files into the editor</li>
              <li>• Add multiple images - each will be uploaded separately</li>
            </ul>
          </div>
          <div>
            <p class="font-medium text-foreground mb-1">Supported formats:</p>
            <p class="ml-4">JPEG, PNG, GIF, WebP</p>
          </div>
          <div>
            <p class="font-medium text-foreground mb-1">Maximum file size:</p>
            <p class="ml-4">2 MB per image</p>
          </div>
        </div>
      </details>
      <details class="cursor-pointer">
        <summary class="hover:text-foreground">Code Syntax Highlighting</summary>
        <div class="mt-2 text-xs bg-muted/30 p-3 rounded border">
          <p class="mb-2">Add a language identifier after the opening backticks for syntax highlighting:</p>
          <div class="bg-background/50 p-2 rounded font-mono text-xs mb-2">
            ```javascript<br/>
            const greeting = "Hello World";<br/>
            ```
          </div>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1">
            <div class="font-medium text-foreground col-span-2 mb-1">Supported Languages:</div>
            <div>• <code class="text-xs">javascript</code> or <code class="text-xs">js</code></div>
            <div>• <code class="text-xs">typescript</code> or <code class="text-xs">ts</code></div>
            <div>• <code class="text-xs">python</code> or <code class="text-xs">py</code></div>
            <div>• <code class="text-xs">php</code></div>
            <div>• <code class="text-xs">ruby</code> or <code class="text-xs">rb</code></div>
            <div>• <code class="text-xs">bash</code> or <code class="text-xs">sh</code></div>
          </div>
        </div>
      </details>
      <details class="cursor-pointer">
        <summary class="hover:text-foreground">Keyboard Shortcuts</summary>
        <div class="mt-2 text-xs bg-muted/30 p-3 rounded border">
          <div class="grid grid-cols-2 gap-x-4 gap-y-1 mb-3">
            <div class="font-medium text-foreground col-span-2 mb-1">Headers</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+1</kbd> Heading 1</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+2</kbd> Heading 2</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+3</kbd> Heading 3</div>
          </div>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1 mb-3">
            <div class="font-medium text-foreground col-span-2 mb-1">Formatting</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+B</kbd> Bold</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+I</kbd> Italic</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+X</kbd> Strikethrough</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+K</kbd> Link</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+E</kbd> Inline Code</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+.</kbd> Quote</div>
          </div>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1 mb-3">
            <div class="font-medium text-foreground col-span-2 mb-1">Lists & Blocks</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+8</kbd> Bullet List</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+7</kbd> Number List</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+C</kbd> Code Block</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+T</kbd> Table</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Enter</kbd> Continue List</div>
          </div>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1">
            <div class="font-medium text-foreground col-span-2 mb-1">Navigation</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Tab</kbd> Focus Toolbar</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Arrow Keys</kbd> Navigate Toolbar</div>
          </div>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1 mt-3">
            <div class="font-medium text-foreground col-span-2 mb-1">View Modes</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+S</kbd> Toggle Split View</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Ctrl/Cmd+Shift+E</kbd> Toggle Expanded</div>
            <div><kbd class="px-1 py-0.5 bg-background rounded text-xs">Esc</kbd> Exit Expanded</div>
          </div>
        </div>
      </details>
    </div>
  </div>
</template>
