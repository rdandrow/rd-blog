import { describe, expect, it, beforeEach, vi } from 'vitest';
import { useMarkdown } from '@/composables/useMarkdown';

describe('useMarkdown', () => {
    let markdown: ReturnType<typeof useMarkdown>;

    beforeEach(() => {
        markdown = useMarkdown();
        vi.clearAllMocks();
    });

    describe('render', () => {
        it('should render basic markdown to HTML', () => {
            const result = markdown.render('# Hello World');
            expect(result).toContain('<h1>Hello World</h1>');
        });

        it('should render paragraphs', () => {
            const result = markdown.render('This is a paragraph.');
            expect(result).toContain('<p>This is a paragraph.</p>');
        });

        it('should render bold text', () => {
            const result = markdown.render('**bold text**');
            expect(result).toContain('<strong>bold text</strong>');
        });

        it('should render italic text', () => {
            const result = markdown.render('*italic text*');
            expect(result).toContain('<em>italic text</em>');
        });

        it('should render links', () => {
            const result = markdown.render('[Link](https://example.com)');
            expect(result).toContain('<a href="https://example.com">Link</a>');
        });

        it('should auto-linkify URLs', () => {
            const result = markdown.render('Visit https://example.com');
            expect(result).toContain('<a href="https://example.com">https://example.com</a>');
        });

        it('should render lists', () => {
            const result = markdown.render('- Item 1\n- Item 2');
            expect(result).toContain('<ul>');
            expect(result).toContain('<li>Item 1</li>');
            expect(result).toContain('<li>Item 2</li>');
        });

        it('should render ordered lists', () => {
            const result = markdown.render('1. First\n2. Second');
            expect(result).toContain('<ol>');
            expect(result).toContain('<li>First</li>');
            expect(result).toContain('<li>Second</li>');
        });

        it('should render blockquotes', () => {
            const result = markdown.render('> Quote text');
            expect(result).toContain('<blockquote>');
            expect(result).toContain('Quote text');
        });

        it('should render code blocks', () => {
            const result = markdown.render('```\ncode here\n```');
            expect(result).toContain('<pre');
            expect(result).toContain('code here');
        });

        it('should render inline code', () => {
            const result = markdown.render('Use `console.log()` for debugging');
            expect(result).toContain('<code>console.log()</code>');
        });

        it('should render tables', () => {
            const table = `| Header 1 | Header 2 |
|----------|----------|
| Cell 1   | Cell 2   |`;
            const result = markdown.render(table);
            expect(result).toContain('<table>');
            expect(result).toContain('<thead>');
            expect(result).toContain('<tbody>');
            expect(result).toContain('Header 1');
            expect(result).toContain('Cell 1');
        });

        it('should handle inline markdown in table cells', () => {
            const table = `| Feature | Status |
|---------|--------|
| **Bold** | *Italic* |`;
            const result = markdown.render(table);
            expect(result).toContain('<strong>Bold</strong>');
            expect(result).toContain('<em>Italic</em>');
        });

        it('should convert line breaks to <br>', () => {
            const result = markdown.render('Line 1\nLine 2');
            expect(result).toContain('<br>');
        });

        it('should handle null input', () => {
            const result = markdown.render(null);
            expect(result).toBe('');
        });

        it('should handle undefined input', () => {
            const result = markdown.render(undefined);
            expect(result).toBe('');
        });

        it('should handle empty string', () => {
            const result = markdown.render('');
            expect(result).toBe('');
        });

        it('should not render raw HTML for security', () => {
            const result = markdown.render('<script>alert("xss")</script>');
            expect(result).not.toContain('<script>');
            expect(result).toContain('&lt;script&gt;');
        });

        it('should escape dangerous HTML entities', () => {
            const result = markdown.render('<img src=x onerror="alert(1)">');
            // The tag should be escaped, not executable
            expect(result).toContain('&lt;img');
            expect(result).toContain('&gt;');
            // Should not contain actual executable img tag
            expect(result).not.toMatch(/<img[^>]*onerror=/i);
        });

        it('should render content at character limit', () => {
            const content = 'a'.repeat(50000);
            const result = markdown.render(content);
            expect(result).toContain('<p>');
            expect(result).not.toContain('Content Too Large');
        });

        it('should show error for content exceeding limit', () => {
            const content = 'a'.repeat(50001);
            const result = markdown.render(content);
            expect(result).toContain('Content Too Large');
            expect(result).toContain('50,000');
        });

        it('should enable typographer (smart quotes)', () => {
            const result = markdown.render('"Hello"');
            // Typographer converts straight quotes to curly quotes
            expect(result).toMatch(/[“”]/); // Unicode curly quotes
        });

        describe('syntax highlighting', () => {
            it('should highlight JavaScript code', () => {
                const code = '```javascript\nconst x = 10;\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('const');
            });

            it('should highlight TypeScript code', () => {
                const code = '```typescript\ninterface User {}\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('interface');
            });

            it('should highlight Python code', () => {
                const code = '```python\ndef hello():\n    pass\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('def');
            });

            it('should highlight PHP code', () => {
                const code = '```php\n<?php echo "test"; ?>\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
            });

            it('should highlight bash/shell code', () => {
                const code = '```bash\necho "test"\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('echo');
            });

            it('should handle language aliases (js for javascript)', () => {
                const code = '```js\nconsole.log();\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
            });

            it('should handle language aliases (ts for typescript)', () => {
                const code = '```ts\ntype Foo = string;\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
            });

            it('should handle language aliases (py for python)', () => {
                const code = '```py\nprint("test")\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
            });

            it('should highlight SQL code', () => {
                const code = '```sql\nSELECT * FROM users WHERE id = 1;\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('SELECT');
            });

            it('should highlight CSS code', () => {
                const code = '```css\n.button { color: blue; }\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('button');
            });

            it('should highlight HTML code', () => {
                const code = '```html\n<div class="container">Content</div>\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                // Check that HTML is escaped and highlighted
                expect(result).toContain('hljs-tag');
                expect(result).toContain('hljs-name');
            });

            it('should highlight Vue template code', () => {
                const code = '```vue\n<template>\n  <div>{{ message }}</div>\n</template>\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                // Check that Vue template is escaped and highlighted
                expect(result).toContain('hljs-tag');
                expect(result).toContain('hljs-name');
            });

            it('should highlight YAML code', () => {
                const code = '```yaml\nname: test\nversion: 1.0\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('name');
            });

            it('should handle language aliases (yml for yaml)', () => {
                const code = '```yml\nkey: value\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
            });

            it('should highlight Dockerfile code', () => {
                const code = '```dockerfile\nFROM node:18\nWORKDIR /app\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('FROM');
            });

            it('should highlight INI configuration files', () => {
                const code = '```ini\n[section]\nkey=value\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('section');
            });

            it('should handle language aliases (sh for bash)', () => {
                const code = '```sh\nls -la\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
            });

            it('should handle language aliases (rb for ruby)', () => {
                const code = '```rb\nputs "Hello"\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
            });

            it('should escape code for unknown languages', () => {
                const code = '```unknownlang\n<script>alert(1)</script>\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).not.toContain('<script>');
                expect(result).toContain('&lt;script&gt;');
            });

            it('should handle code blocks without language specification', () => {
                const code = '```\nsome code\n```';
                const result = markdown.render(code);
                expect(result).toContain('<pre class="hljs">');
                expect(result).toContain('some code');
            });
        });

        describe('error handling', () => {
            it('should handle render errors gracefully', () => {
                // Mock markdown instance to throw error
                const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
                
                // Create a scenario that might cause an error
                const markdown = useMarkdown();
                const instance = markdown.getInstance();
                
                // Mock the render method to throw
                vi.spyOn(instance, 'render').mockImplementation(() => {
                    throw new Error('Test render error');
                });
                
                const result = markdown.render('test content');
                
                expect(result).toContain('Markdown Preview Error');
                expect(result).toContain('Test render error');
                expect(result).toContain('The content will still be saved correctly');
                expect(spy).toHaveBeenCalled();
                
                spy.mockRestore();
            });

            it('should handle unexpected markdown syntax errors', () => {
                const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
                
                const markdown = useMarkdown();
                const instance = markdown.getInstance();
                
                // Mock the render method to throw error with 'unexpected' in message
                vi.spyOn(instance, 'render').mockImplementation(() => {
                    throw new Error('unexpected token at position 5');
                });
                
                const result = markdown.render('test content');
                
                expect(result).toContain('Markdown Preview Error');
                expect(result).toContain('Invalid markdown syntax detected');
                expect(spy).toHaveBeenCalled();
                
                spy.mockRestore();
            });

            it('should handle memory/stack overflow errors', () => {
                const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
                
                const markdown = useMarkdown();
                const instance = markdown.getInstance();
                
                // Mock the render method to throw memory error
                vi.spyOn(instance, 'render').mockImplementation(() => {
                    throw new Error('memory limit exceeded');
                });
                
                const result = markdown.render('test content');
                
                expect(result).toContain('Markdown Preview Error');
                expect(result).toContain('Content too complex to render');
                expect(spy).toHaveBeenCalled();
                
                spy.mockRestore();
            });

            it('should handle stack overflow errors', () => {
                const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
                
                const markdown = useMarkdown();
                const instance = markdown.getInstance();
                
                // Mock the render method to throw stack error
                vi.spyOn(instance, 'render').mockImplementation(() => {
                    throw new Error('Maximum call stack size exceeded');
                });
                
                const result = markdown.render('test content');
                
                expect(result).toContain('Markdown Preview Error');
                expect(result).toContain('Content too complex to render');
                expect(spy).toHaveBeenCalled();
                
                spy.mockRestore();
            });
        });
    });

    describe('renderInline', () => {
        it('should render inline markdown', () => {
            const result = markdown.renderInline('**bold** text');
            expect(result).toContain('<strong>bold</strong>');
            expect(result).not.toContain('<p>'); // No paragraph wrapper
        });

        it('should render links inline', () => {
            const result = markdown.renderInline('[Link](https://example.com)');
            expect(result).toContain('<a href="https://example.com">Link</a>');
        });

        it('should handle null input', () => {
            const result = markdown.renderInline(null);
            expect(result).toBe('');
        });

        it('should handle undefined input', () => {
            const result = markdown.renderInline(undefined);
            expect(result).toBe('');
        });

        it('should handle empty string', () => {
            const result = markdown.renderInline('');
            expect(result).toBe('');
        });

        it('should not include block elements', () => {
            const result = markdown.renderInline('# Heading');
            expect(result).not.toContain('<h1>');
        });

        it('should handle errors gracefully', () => {
            const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
            
            const markdown = useMarkdown();
            const instance = markdown.getInstance();
            
            vi.spyOn(instance, 'renderInline').mockImplementation(() => {
                throw new Error('Inline render error');
            });
            
            const result = markdown.renderInline('test');
            expect(result).toBe('test'); // Falls back to original content
            expect(spy).toHaveBeenCalled();
            
            spy.mockRestore();
        });
    });

    describe('getInstance', () => {
        it('should return MarkdownIt instance', () => {
            const instance = markdown.getInstance();
            expect(instance).toBeDefined();
            expect(typeof instance.render).toBe('function');
            expect(typeof instance.renderInline).toBe('function');
        });

        it('should return the same instance (singleton)', () => {
            const instance1 = markdown.getInstance();
            const instance2 = markdown.getInstance();
            expect(instance1).toBe(instance2);
        });

        it('should return instance with correct configuration', () => {
            const instance = markdown.getInstance();
            // Check that HTML is disabled
            const htmlTest = instance.render('<div>test</div>');
            expect(htmlTest).not.toContain('<div>test</div>');
            expect(htmlTest).toContain('&lt;div&gt;');
        });
    });

    describe('composable reusability', () => {
        it('should share instance within same composable instance', () => {
            const markdown1 = useMarkdown();
            
            const instance1 = markdown1.getInstance();
            const instance2 = markdown1.getInstance();
            
            // Within same composable call, should return same instance
            expect(instance1).toBe(instance2);
        });

        it('should produce consistent results across calls', () => {
            const markdown1 = useMarkdown();
            const markdown2 = useMarkdown();
            
            const result1 = markdown1.render('# Test');
            const result2 = markdown2.render('# Test');
            
            expect(result1).toBe(result2);
        });
    });
});
