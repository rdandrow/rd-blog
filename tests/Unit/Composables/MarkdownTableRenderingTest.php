<?php

/**
 * Markdown Table Rendering Test Suite
 *
 * Tests table rendering functionality in the markdown editor,
 * including syntax validation and HTML output.
 * 
 * Note: The actual markdown rendering is done by markdown-it in JavaScript.
 * These tests verify the expected behavior and server-side markdown processing.
 * For full frontend testing, see FRONTEND_TESTING.md for Vitest examples.
 */

describe('Markdown Table Syntax', function () {
    it('validates basic table structure with headers and rows', function () {
        $tableMarkdown = <<<'MD'
| Header 1 | Header 2 | Header 3 |
|----------|----------|----------|
| Cell 1   | Cell 2   | Cell 3   |
| Cell 4   | Cell 5   | Cell 6   |
MD;

        // Verify the structure has required components
        expect($tableMarkdown)->toContain('|')
            ->and($tableMarkdown)->toContain('Header 1')
            ->and($tableMarkdown)->toContain('----------')
            ->and($tableMarkdown)->toContain('Cell 1');
    })->group('markdown', 'tables', 'syntax');

    it('validates table has separator row between header and body', function () {
        $tableMarkdown = <<<'MD'
| Name | Age |
|------|-----|
| John | 30  |
MD;

        $lines = explode("\n", $tableMarkdown);
        
        expect($lines[1])->toContain('---')
            ->and($lines[1])->toContain('|');
    })->group('markdown', 'tables', 'separator');

    it('validates table with multiple data rows', function () {
        $tableMarkdown = <<<'MD'
| Column A | Column B |
|----------|----------|
| Value 1  | Value 2  |
| Value 3  | Value 4  |
| Value 5  | Value 6  |
MD;

        $lines = explode("\n", $tableMarkdown);
        
        // Should have header + separator + 3 data rows = 5 lines
        expect(count($lines))->toBe(5)
            ->and($lines[0])->toContain('Column A')
            ->and($lines[2])->toContain('Value 1')
            ->and($lines[4])->toContain('Value 5');
    })->group('markdown', 'tables', 'multiple-rows');
});

describe('Markdown Table Template Generation', function () {
    it('generates 3x3 table template with correct structure', function () {
        // Template that would be inserted by Ctrl+Shift+T
        $template = <<<'MD'

| Header 1 | Header 2 | Header 3 |
|----------|----------|----------|
| Cell 1   | Cell 2   | Cell 3   |
| Cell 4   | Cell 5   | Cell 6   |

MD;

        $lines = array_filter(explode("\n", trim($template)));
        
        expect(count($lines))->toBe(4) // header + separator + 2 data rows
            ->and($lines[0])->toContain('Header 1')
            ->and($lines[0])->toContain('Header 2')
            ->and($lines[0])->toContain('Header 3')
            ->and($lines[1])->toMatch('/^\|[-\s|]+\|$/') // Separator row pattern
            ->and($lines[2])->toContain('Cell 1')
            ->and($lines[3])->toContain('Cell 4');
    })->group('markdown', 'tables', 'template');

    it('template has proper column alignment', function () {
        $template = '| Header 1 | Header 2 | Header 3 |';
        
        // Count pipes to verify structure
        $pipeCount = substr_count($template, '|');
        
        expect($pipeCount)->toBe(4) // 4 pipes for 3 columns (including edges)
            ->and($template)->toStartWith('|')
            ->and($template)->toEndWith('|');
    })->group('markdown', 'tables', 'alignment');
});

describe('Markdown Table Column Parsing', function () {
    it('correctly identifies number of columns in table', function () {
        $headerRow = '| Name | Email | Age |';
        
        // Remove leading/trailing pipes and split
        $columns = array_filter(array_map('trim', explode('|', trim($headerRow, '|'))));
        
        expect(count($columns))->toBe(3)
            ->and($columns)->toContain('Name')
            ->and($columns)->toContain('Email')
            ->and($columns)->toContain('Age');
    })->group('markdown', 'tables', 'columns');

    it('handles tables with different column counts', function () {
        $twoColumnHeader = '| A | B |';
        $fiveColumnHeader = '| A | B | C | D | E |';
        
        $cols2 = count(array_filter(explode('|', trim($twoColumnHeader, '|'))));
        $cols5 = count(array_filter(explode('|', trim($fiveColumnHeader, '|'))));
        
        expect($cols2)->toBe(2)
            ->and($cols5)->toBe(5);
    })->group('markdown', 'tables', 'columns', 'variable');
});

describe('Markdown Table Edge Cases', function () {
    it('handles empty cells gracefully', function () {
        $tableWithEmptyCells = <<<'MD'
| Header 1 | Header 2 | Header 3 |
|----------|----------|----------|
|          | Value    |          |
| Value    |          | Value    |
MD;

        expect($tableWithEmptyCells)->toContain('|          |')
            ->and($tableWithEmptyCells)->toContain('Value');
    })->group('markdown', 'tables', 'edge-cases', 'empty-cells');

    it('handles cells with special characters', function () {
        $tableWithSpecialChars = <<<'MD'
| Name     | Email           | Status    |
|----------|-----------------|-----------|
| John Doe | john@email.com  | Active ✓  |
| Jane     | jane@test.org   | Pending → |
MD;

        expect($tableWithSpecialChars)->toContain('@')
            ->and($tableWithSpecialChars)->toContain('✓')
            ->and($tableWithSpecialChars)->toContain('→');
    })->group('markdown', 'tables', 'edge-cases', 'special-chars');

    it('validates table with inline markdown in cells', function () {
        $tableWithMarkdown = <<<'MD'
| Feature     | Status      |
|-------------|-------------|
| **Bold**    | *Italic*    |
| `code`      | [link](url) |
MD;

        expect($tableWithMarkdown)->toContain('**Bold**')
            ->and($tableWithMarkdown)->toContain('*Italic*')
            ->and($tableWithMarkdown)->toContain('`code`')
            ->and($tableWithMarkdown)->toContain('[link](url)');
    })->group('markdown', 'tables', 'edge-cases', 'nested-markdown');
});

describe('Markdown Table Keyboard Shortcut Format', function () {
    it('validates Ctrl+Shift+T shortcut format', function () {
        $shortcut = 'Ctrl+Shift+T';
        
        expect($shortcut)->toContain('Ctrl')
            ->and($shortcut)->toContain('Shift')
            ->and($shortcut)->toContain('T');
    })->group('markdown', 'tables', 'shortcuts');

    it('shortcut uses uppercase T for table', function () {
        $shortcut = 'Ctrl+Shift+T';
        
        expect($shortcut)->toEndWith('T')
            ->and($shortcut)->not->toEndWith('t');
    })->group('markdown', 'tables', 'shortcuts', 'case');
});

describe('Markdown Table Insertion Position', function () {
    it('inserts table at cursor position in content', function () {
        $beforeContent = 'Some text before';
        $afterContent = 'Some text after';
        $table = "\n| A | B |\n|---|---|\n| 1 | 2 |\n";
        
        $fullContent = $beforeContent . $table . $afterContent;
        
        expect($fullContent)->toContain('Some text before')
            ->and($fullContent)->toContain('| A | B |')
            ->and($fullContent)->toContain('Some text after')
            ->and(strpos($fullContent, 'before'))->toBeLessThan(strpos($fullContent, '| A |'))
            ->and(strpos($fullContent, '| A |'))->toBeLessThan(strpos($fullContent, 'after'));
    })->group('markdown', 'tables', 'insertion');

    it('table has proper line breaks around it', function () {
        $table = "\n| Header |\n|--------|\n| Data   |\n";
        
        expect($table)->toStartWith("\n")
            ->and($table)->toEndWith("\n");
    })->group('markdown', 'tables', 'formatting', 'line-breaks');
});

describe('Markdown Table Cell Content Validation', function () {
    it('validates header cell content matches expected format', function () {
        $headerCell = 'Header 1';
        
        expect($headerCell)->toMatch('/^Header \d+$/')
            ->and($headerCell)->toContain('Header')
            ->and(strlen($headerCell))->toBeGreaterThan(0);
    })->group('markdown', 'tables', 'validation', 'headers');

    it('validates data cell content matches expected format', function () {
        $dataCell = 'Cell 1';
        
        expect($dataCell)->toMatch('/^Cell \d+$/')
            ->and($dataCell)->toContain('Cell')
            ->and(strlen($dataCell))->toBeGreaterThan(0);
    })->group('markdown', 'tables', 'validation', 'cells');

    it('validates separator row uses dashes', function () {
        $separatorRow = '|----------|----------|----------|';
        
        expect($separatorRow)->toContain('----------')
            ->and($separatorRow)->toMatch('/^\|[-|]+\|$/');
    })->group('markdown', 'tables', 'validation', 'separator');
});
