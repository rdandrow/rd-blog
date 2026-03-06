<?php

declare(strict_types=1);

describe('FTS Language Guard Command', function () {

    test('passes when configured language matches index language', function () {
        config(['database.full_text_search.language' => 'english']);

        $this->artisan('db:check-fts-language')
            ->expectsOutput('✓ Full-text language is aligned.')
            ->assertExitCode(0);
    });

    test('fails when configured language differs from index language', function () {
        config(['database.full_text_search.language' => 'simple']);

        $this->artisan('db:check-fts-language')
            ->expectsOutput('Full-text language mismatch detected.')
            ->assertExitCode(1);
    });

});
