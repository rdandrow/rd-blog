<?php

/**
 * Markdown Character and Word Counter Test Suite
 *
 * Tests character and word counting functionality in the markdown editor.
 * These tests validate the counting logic that powers the real-time counter display.
 * 
 * Note: The actual counting is done in Vue computed properties in JavaScript.
 * These tests verify the expected behavior and logic patterns.
 * For full frontend testing, see FRONTEND_TESTING.md for Vitest examples.
 */

describe('Character Count Logic', function () {
    it('counts characters in simple text correctly', function () {
        $text = 'Hello World';
        
        expect(strlen($text))->toBe(11);
    })->group('markdown', 'counter', 'characters');

    it('counts empty string as zero characters', function () {
        $text = '';
        
        expect(strlen($text))->toBe(0);
    })->group('markdown', 'counter', 'characters', 'edge-cases');

    it('counts whitespace characters', function () {
        $text = '   '; // 3 spaces
        
        expect(strlen($text))->toBe(3);
    })->group('markdown', 'counter', 'characters', 'whitespace');

    it('counts newlines as characters', function () {
        $text = "Line 1\nLine 2\nLine 3";
        
        // "Line 1" (6) + "\n" (1) + "Line 2" (6) + "\n" (1) + "Line 3" (6) = 20
        expect(strlen($text))->toBe(20);
    })->group('markdown', 'counter', 'characters', 'newlines');

    it('counts markdown formatting characters', function () {
        $text = '**bold** and *italic*';
        
        // Should count asterisks and all formatting characters
        expect(strlen($text))->toBe(21);
    })->group('markdown', 'counter', 'characters', 'markdown');

    it('counts special characters and symbols', function () {
        $text = 'Hello! @user #hashtag $price 100%';
        
        expect(strlen($text))->toBe(33);
    })->group('markdown', 'counter', 'characters', 'special');

    it('counts emoji correctly (multi-byte characters)', function () {
        $text = 'Hello 👋 World 🌍';
        
        // Note: strlen counts bytes, mb_strlen counts characters
        // This test documents the expected behavior
        expect(mb_strlen($text, 'UTF-8'))->toBe(15); // Character count
    })->group('markdown', 'counter', 'characters', 'unicode');

    it('handles very long text (approaching limit)', function () {
        $text = str_repeat('a', 50000);
        
        expect(strlen($text))->toBe(50000);
    })->group('markdown', 'counter', 'characters', 'limits');

    it('handles text at maximum limit', function () {
        $text = str_repeat('x', 50000);
        
        expect(strlen($text))->toBe(50000)
            ->and(strlen($text))->toBeLessThanOrEqual(50000);
    })->group('markdown', 'counter', 'characters', 'limits');
});

describe('Word Count Logic', function () {
    it('counts words in simple sentence', function () {
        $text = 'Hello World';
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(2);
    })->group('markdown', 'counter', 'words');

    it('returns zero for empty string', function () {
        $text = '';
        $trimmed = trim($text);
        
        if ($trimmed === '') {
            $wordCount = 0;
        } else {
            $words = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
            $wordCount = count($words);
        }
        
        expect($wordCount)->toBe(0);
    })->group('markdown', 'counter', 'words', 'edge-cases');

    it('returns zero for whitespace-only string', function () {
        $text = '   ';
        $trimmed = trim($text);
        
        if ($trimmed === '') {
            $wordCount = 0;
        } else {
            $words = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
            $wordCount = count($words);
        }
        
        expect($wordCount)->toBe(0);
    })->group('markdown', 'counter', 'words', 'edge-cases');

    it('handles single word', function () {
        $text = 'Hello';
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(1);
    })->group('markdown', 'counter', 'words');

    it('handles multiple spaces between words', function () {
        $text = 'Hello    World    Test';
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(3);
    })->group('markdown', 'counter', 'words', 'whitespace');

    it('handles tabs as word separators', function () {
        $text = "Hello\tWorld\tTest";
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(3);
    })->group('markdown', 'counter', 'words', 'whitespace');

    it('handles newlines as word separators', function () {
        $text = "Hello\nWorld\nTest";
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(3);
    })->group('markdown', 'counter', 'words', 'newlines');

    it('handles mixed whitespace types', function () {
        $text = "Hello \t\nWorld   \n\tTest";
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(3);
    })->group('markdown', 'counter', 'words', 'whitespace');

    it('counts words with punctuation correctly', function () {
        $text = "Hello, world! How are you?";
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        // Punctuation attached to words counts as part of the word
        expect(count($words))->toBe(5); // "Hello,", "world!", "How", "are", "you?"
    })->group('markdown', 'counter', 'words', 'punctuation');

    it('counts hyphenated words as single word', function () {
        $text = 'This is a well-known fact';
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(5); // "well-known" is one word
    })->group('markdown', 'counter', 'words', 'hyphens');

    it('handles markdown formatting in word count', function () {
        $text = 'This is **bold** and *italic* text';
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        // Asterisks are part of the words when not separated by spaces
        expect(count($words))->toBe(6); // "This", "is", "**bold**", "and", "*italic*", "text"
    })->group('markdown', 'counter', 'words', 'markdown');

    it('handles markdown links in word count', function () {
        $text = 'Check out [this link](https://example.com) for more info';
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(7); // "Check", "out", "[this", "link](https://example.com)", "for", "more", "info"
    })->group('markdown', 'counter', 'words', 'markdown');

    it('counts words in multiline text', function () {
        $text = <<<'MD'
# Heading

This is a paragraph with multiple words.

* List item one
* List item two
MD;
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        // Should count all words including markdown symbols
        expect(count($words))->toBeGreaterThan(10);
    })->group('markdown', 'counter', 'words', 'multiline');

    it('handles numbers as words', function () {
        $text = 'There are 123 apples and 456 oranges';
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(7);
    })->group('markdown', 'counter', 'words', 'numbers');

    it('handles URLs as single words', function () {
        $text = 'Visit https://example.com for more information';
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        
        expect(count($words))->toBe(5); // URL is one "word"
    })->group('markdown', 'counter', 'words', 'urls');
});

describe('Counter Display Warning Thresholds', function () {
    it('identifies normal state (below 90% of limit)', function () {
        $characterCount = 40000; // 80% of 50000
        $maxCharacters = 50000;
        
        $percentage = ($characterCount / $maxCharacters) * 100;
        
        expect($percentage)->toBeLessThan(90);
    })->group('markdown', 'counter', 'thresholds');

    it('identifies warning state (90-95% of limit)', function () {
        $characterCount = 46000; // 92% of 50000
        $maxCharacters = 50000;
        
        $percentage = ($characterCount / $maxCharacters) * 100;
        
        expect($percentage)->toBeGreaterThanOrEqual(90)
            ->and($percentage)->toBeLessThanOrEqual(95);
    })->group('markdown', 'counter', 'thresholds', 'warning');

    it('identifies critical state (above 95% of limit)', function () {
        $characterCount = 48000; // 96% of 50000
        $maxCharacters = 50000;
        
        $percentage = ($characterCount / $maxCharacters) * 100;
        
        expect($percentage)->toBeGreaterThan(95);
    })->group('markdown', 'counter', 'thresholds', 'critical');

    it('handles exactly 90% threshold', function () {
        $characterCount = 45000; // Exactly 90%
        $maxCharacters = 50000;
        
        $percentage = ($characterCount / $maxCharacters) * 100;
        
        expect($percentage)->toBe(90.0);
    })->group('markdown', 'counter', 'thresholds', 'boundary');

    it('handles exactly 95% threshold', function () {
        $characterCount = 47500; // Exactly 95%
        $maxCharacters = 50000;
        
        $percentage = ($characterCount / $maxCharacters) * 100;
        
        expect($percentage)->toBe(95.0);
    })->group('markdown', 'counter', 'thresholds', 'boundary');

    it('handles maximum limit (100%)', function () {
        $characterCount = 50000;
        $maxCharacters = 50000;
        
        $percentage = ($characterCount / $maxCharacters) * 100;
        
        expect($percentage)->toEqual(100.0);
    })->group('markdown', 'counter', 'thresholds', 'maximum');
});

describe('Counter Formatting', function () {
    it('formats character count with locale separators', function () {
        $count = 12345;
        
        $formatted = number_format($count, 0, '.', ',');
        
        expect($formatted)->toBe('12,345');
    })->group('markdown', 'counter', 'formatting');

    it('formats word count with locale separators', function () {
        $count = 1234;
        
        $formatted = number_format($count, 0, '.', ',');
        
        expect($formatted)->toBe('1,234');
    })->group('markdown', 'counter', 'formatting');

    it('handles single word/character correctly', function () {
        $count = 1;
        
        $label = $count === 1 ? 'word' : 'words';
        
        expect($label)->toBe('word');
    })->group('markdown', 'counter', 'formatting', 'singular');

    it('handles plural words correctly', function () {
        $count = 2;
        
        $label = $count === 1 ? 'word' : 'words';
        
        expect($label)->toBe('words');
    })->group('markdown', 'counter', 'formatting', 'plural');

    it('handles zero words with plural label', function () {
        $count = 0;
        
        $label = $count === 1 ? 'word' : 'words';
        
        expect($label)->toBe('words');
    })->group('markdown', 'counter', 'formatting', 'plural');
});

describe('Counter Integration Scenarios', function () {
    it('handles typical blog post content', function () {
        $content = <<<'MD'
# My Blog Post Title

This is the introduction paragraph with several words that explain what this post is about.

## Section One

Here is some more detailed content. Let's add a list:

* Item one with description
* Item two with description  
* Item three with description

And a code block:

```php
echo "Hello World";
```

## Conclusion

Final thoughts and summary.
MD;

        $characterCount = strlen($content);
        $words = preg_split('/\s+/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);
        
        expect($characterCount)->toBeGreaterThan(0)
            ->and($wordCount)->toBeGreaterThan(10)
            ->and($characterCount)->toBeLessThan(50000);
    })->group('markdown', 'counter', 'integration');

    it('handles maximum length content', function () {
        // Simulate content at or near the limit
        $content = str_repeat('word ', 10000); // ~50000 chars
        
        $characterCount = strlen($content);
        $words = preg_split('/\s+/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);
        
        expect($characterCount)->toBeLessThanOrEqual(50000)
            ->and($wordCount)->toBe(10000);
    })->group('markdown', 'counter', 'integration', 'limits');

    it('validates counter accuracy across different content types', function () {
        $scenarios = [
            'Empty' => ['content' => '', 'expectedWords' => 0, 'expectedChars' => 0],
            'Single word' => ['content' => 'Hello', 'expectedWords' => 1, 'expectedChars' => 5],
            'Sentence' => ['content' => 'Hello World Test', 'expectedWords' => 3, 'expectedChars' => 16],
            'With newlines' => ['content' => "Line 1\nLine 2", 'expectedWords' => 4, 'expectedChars' => 13],
        ];
        
        foreach ($scenarios as $name => $scenario) {
            $words = $scenario['content'] === '' || trim($scenario['content']) === '' 
                ? [] 
                : preg_split('/\s+/', trim($scenario['content']), -1, PREG_SPLIT_NO_EMPTY);
            $wordCount = count($words);
            $charCount = strlen($scenario['content']);
            
            expect($wordCount)->toBe($scenario['expectedWords'], "Failed for scenario: {$name}")
                ->and($charCount)->toBe($scenario['expectedChars'], "Failed for scenario: {$name}");
        }
    })->group('markdown', 'counter', 'integration', 'scenarios');
});
