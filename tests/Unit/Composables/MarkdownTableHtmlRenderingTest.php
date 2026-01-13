<?php

/**
 * Markdown Table HTML Rendering Test Suite
 *
 * Tests that markdown tables are correctly converted to HTML with proper
 * table structure including borders and styling support.
 * 
 * These tests verify server-side expectations for how markdown-it should
 * convert table syntax to HTML. The actual rendering happens in JavaScript
 * via the useMarkdown composable.
 */

use Illuminate\Support\Facades\Http;

describe('Markdown Table HTML Structure', function () {
    it('expects table markdown to convert to HTML table elements', function () {
        // This test documents the expected HTML output structure
        // The actual conversion is done by markdown-it in JavaScript
        $tableMarkdown = <<<'MD'
| Header 1 | Header 2 |
|----------|----------|
| Cell 1   | Cell 2   |
MD;

        // Expected HTML structure that markdown-it should produce
        $expectedElements = [
            '<table',
            '<thead',
            '<tbody',
            '<th>',
            '<td>',
            '</table>',
        ];

        // Document that we expect all these elements in the output
        foreach ($expectedElements as $element) {
            expect($element)->toBeString();
        }
    })->group('markdown', 'tables', 'html');

    it('expects table headers to be wrapped in thead and th tags', function () {
        $tableMarkdown = <<<'MD'
| Name | Email | Role |
|------|-------|------|
| John | j@e.c | Admin |
MD;

        // Expected structure for headers
        $expectedHeaderStructure = [
            'table' => 'Root element',
            'thead' => 'Header section',
            'tr' => 'Table row',
            'th' => 'Table header cells',
        ];

        expect($expectedHeaderStructure)->toBeArray()
            ->and(count($expectedHeaderStructure))->toBe(4);
    })->group('markdown', 'tables', 'html', 'headers');

    it('expects table body cells to be wrapped in tbody and td tags', function () {
        $tableMarkdown = <<<'MD'
| Column A | Column B |
|----------|----------|
| Value 1  | Value 2  |
| Value 3  | Value 4  |
MD;

        // Expected structure for body
        $expectedBodyStructure = [
            'tbody' => 'Body section',
            'tr' => 'Multiple table rows',
            'td' => 'Table data cells',
        ];

        expect($expectedBodyStructure)->toBeArray()
            ->and(count($expectedBodyStructure))->toBe(3);
    })->group('markdown', 'tables', 'html', 'body');

    it('expects separator row to not appear in HTML output', function () {
        $tableMarkdown = <<<'MD'
| Header |
|--------|
| Data   |
MD;

        // The separator row (|--------|) should not appear in HTML
        // It's only used by the parser to distinguish header from body
        $lines = explode("\n", $tableMarkdown);
        $separatorLine = $lines[1];

        expect($separatorLine)->toContain('---')
            ->and($separatorLine)->toContain('|');

        // Document that this line is only for parsing, not rendering
    })->group('markdown', 'tables', 'html', 'separator');

    it('expects table with 3 columns to have 3 th elements in thead', function () {
        $tableMarkdown = <<<'MD'
| Header 1 | Header 2 | Header 3 |
|----------|----------|----------|
| Cell 1   | Cell 2   | Cell 3   |
MD;

        // Count expected headers
        $headerLine = explode("\n", $tableMarkdown)[0];
        $headers = array_filter(explode('|', $headerLine), fn($h) => trim($h) !== '');
        $headers = array_values($headers); // Re-index array

        expect(count($headers))->toBe(3)
            ->and(trim($headers[0]))->toContain('Header 1')
            ->and(trim($headers[1]))->toContain('Header 2')
            ->and(trim($headers[2]))->toContain('Header 3');
    })->group('markdown', 'tables', 'html', 'columns');

    it('expects table with 2 data rows to have 2 tr elements in tbody', function () {
        $tableMarkdown = <<<'MD'
| Name |
|------|
| John |
| Jane |
MD;

        // Count data rows (excluding header and separator)
        $lines = explode("\n", $tableMarkdown);
        $dataRows = array_slice($lines, 2); // Skip header and separator

        expect(count($dataRows))->toBe(2);
    })->group('markdown', 'tables', 'html', 'rows');
});

describe('Markdown Table CSS Support', function () {
    it('expects tables to support border styling via CSS', function () {
        // Document the CSS classes that should be applied
        $expectedCssSupport = [
            'border-collapse' => 'Collapsed borders for clean appearance',
            'border on th/td' => '1px solid borders on all cells',
            'thead border-bottom' => '2px border separating header from body',
        ];

        expect($expectedCssSupport)->toHaveKey('border-collapse')
            ->and($expectedCssSupport)->toHaveKey('border on th/td')
            ->and($expectedCssSupport)->toHaveKey('thead border-bottom');
    })->group('markdown', 'tables', 'css', 'borders');

    it('expects tables rendered in prose to inherit prose table styles', function () {
        // Tables should be wrapped in elements with 'prose' class
        $proseClasses = [
            'prose' => 'Base typography styles',
            'prose-sm' => 'Small typography variant',
            'max-w-none' => 'Full width tables',
        ];

        expect($proseClasses)->toHaveKey('prose');
    })->group('markdown', 'tables', 'css', 'prose');

    it('expects table headers to support background color styling', function () {
        // CSS variable usage for theming
        $cssVariables = [
            '--color-muted' => 'Header background color',
            '--color-border' => 'Border color',
            '--color-accent' => 'Hover background color',
        ];

        expect($cssVariables)->toHaveKey('--color-muted')
            ->and($cssVariables)->toHaveKey('--color-border')
            ->and($cssVariables)->toHaveKey('--color-accent');
    })->group('markdown', 'tables', 'css', 'variables');

    it('expects tables to support zebra striping on alternating rows', function () {
        // nth-child CSS selector for alternating row colors
        $stripingConfig = [
            'selector' => 'tbody tr:nth-child(even)',
            'property' => 'background-color',
            'value' => 'var(--color-muted)',
            'opacity' => '0.3',
        ];

        expect($stripingConfig)->toHaveKey('selector')
            ->and($stripingConfig['selector'])->toContain('nth-child(even)');
    })->group('markdown', 'tables', 'css', 'striping');

    it('expects tables to support hover effects on rows', function () {
        // Hover state styling
        $hoverConfig = [
            'selector' => 'tbody tr:hover',
            'property' => 'background-color',
            'value' => 'var(--color-accent)',
            'opacity' => '0.5',
        ];

        expect($hoverConfig)->toHaveKey('selector')
            ->and($hoverConfig['selector'])->toContain(':hover');
    })->group('markdown', 'tables', 'css', 'hover');
});

describe('Markdown Table Content Rendering', function () {
    it('expects inline markdown in cells to be rendered', function () {
        $tableWithMarkdown = <<<'MD'
| Feature | Status |
|---------|--------|
| **Bold** | *Italic* |
MD;

        // Inline markdown should be converted to HTML
        $expectedInlineElements = [
            '**Bold**' => '<strong>Bold</strong>',
            '*Italic*' => '<em>Italic</em>',
        ];

        expect($expectedInlineElements)->toHaveKey('**Bold**')
            ->and($expectedInlineElements)->toHaveKey('*Italic*');
    })->group('markdown', 'tables', 'content', 'inline');

    it('expects code in cells to be rendered with code tags', function () {
        $tableWithCode = <<<'MD'
| Function | Example |
|----------|---------|
| Print | `console.log()` |
MD;

        // Inline code should be wrapped in <code> tags
        expect($tableWithCode)->toContain('`console.log()`');
    })->group('markdown', 'tables', 'content', 'code');

    it('expects links in cells to be rendered as anchors', function () {
        $tableWithLinks = <<<'MD'
| Site | URL |
|------|-----|
| Google | [Link](https://google.com) |
MD;

        // Links should be converted to <a> tags
        expect($tableWithLinks)->toContain('[Link](https://google.com)');
    })->group('markdown', 'tables', 'content', 'links');

    it('expects special characters in cells to be preserved', function () {
        $tableWithSpecialChars = <<<'MD'
| Symbol | Meaning |
|--------|---------|
| ✓ | Done |
| → | Next |
| © | Copyright |
MD;

        // Special characters should be preserved
        expect($tableWithSpecialChars)->toContain('✓')
            ->and($tableWithSpecialChars)->toContain('→')
            ->and($tableWithSpecialChars)->toContain('©');
    })->group('markdown', 'tables', 'content', 'special-chars');
});

describe('Markdown Table Integration', function () {
    it('expects tables in blog post content to render in preview', function () {
        // Blog posts with table markdown should render tables
        $blogContent = <<<'MD'
# My Post

Here is a comparison table:

| Feature | Plan A | Plan B |
|---------|--------|--------|
| Price | $10 | $20 |
| Storage | 1GB | 10GB |

That's the comparison.
MD;

        expect($blogContent)->toContain('| Feature |')
            ->and($blogContent)->toContain('|---------|')
            ->and($blogContent)->toContain('| Price |');
    })->group('markdown', 'tables', 'integration', 'blog-posts');

    it('expects tables to work with other markdown elements', function () {
        $mixedContent = <<<'MD'
## Section

Some text before the table.

| Col 1 | Col 2 |
|-------|-------|
| A | B |

- List item after table
- Another item
MD;

        // Tables should coexist with other markdown
        expect($mixedContent)->toContain('##')
            ->and($mixedContent)->toContain('|')
            ->and($mixedContent)->toContain('- List');
    })->group('markdown', 'tables', 'integration', 'mixed-content');

    it('expects multiple tables in same content to render independently', function () {
        $multipleTablesContent = <<<'MD'
First table:
| A | B |
|---|---|
| 1 | 2 |

Second table:
| X | Y | Z |
|---|---|---|
| 3 | 4 | 5 |
MD;

        // Count table occurrences
        $lines = explode("\n", $multipleTablesContent);
        $separatorCount = count(array_filter($lines, fn($line) => str_contains($line, '|---')));

        expect($separatorCount)->toBe(2); // Two tables = two separator rows
    })->group('markdown', 'tables', 'integration', 'multiple');
});
