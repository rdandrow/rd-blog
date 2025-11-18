import MarkdownIt from 'markdown-it';

/**
 * Shared markdown configuration and utilities
 * Centralizes markdown-it setup to ensure consistency across components
 */
export const useMarkdown = () => {
  // Singleton pattern - create one instance and reuse it
  let mdInstance: MarkdownIt | null = null;

  const createMarkdownInstance = (): MarkdownIt => {
    if (!mdInstance) {
      mdInstance = new MarkdownIt({
        html: false,        // Disable raw HTML for security
        linkify: true,      // Auto-convert URLs to links
        breaks: true,       // Convert line breaks to <br>
        typographer: true,  // Enable smart quotes and other typographic replacements
      });
    }
    return mdInstance;
  };

  const render = (content: string | null | undefined): string => {
    try {
      const md = createMarkdownInstance();
      const inputContent = String(content ?? '');
      
      // Validate input before processing
      if (inputContent.length > 50000) {
        return `<div class="text-amber-600 p-4 border border-amber-200 bg-amber-50 rounded">
          <p class="font-medium">Content Too Large</p>
          <p class="text-sm">Content exceeds 50,000 characters. Please reduce the size for better performance.</p>
        </div>`;
      }
      
      return md.render(inputContent);
    } catch (error) {
      console.error('Markdown render error:', error);
      
      // Provide specific error messages for common issues
      let errorMessage = 'Failed to render markdown';
      
      if (error instanceof Error) {
        if (error.message.includes('unexpected')) {
          errorMessage = 'Invalid markdown syntax detected';
        } else if (error.message.includes('memory') || error.message.includes('stack')) {
          errorMessage = 'Content too complex to render';
        } else {
          errorMessage = error.message;
        }
      }
      
      return `<div class="text-destructive p-4 border border-destructive/20 bg-destructive/5 rounded">
        <p class="font-medium flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          Markdown Preview Error
        </p>
        <p class="text-sm mt-1">${errorMessage}</p>
        <p class="text-xs mt-2 text-muted-foreground">The content will still be saved correctly.</p>
      </div>`;
    }
  };

  const renderInline = (content: string | null | undefined): string => {
    try {
      const md = createMarkdownInstance();
      return md.renderInline(String(content ?? ''));
    } catch (error) {
      console.error('Markdown inline render error:', error);
      return String(content ?? '');
    }
  };

  // Expose the instance for advanced usage if needed
  const getInstance = (): MarkdownIt => createMarkdownInstance();

  return {
    render,
    renderInline,
    getInstance,
  };
};