<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Exceptions\EnvFileNotFoundException;
use Pradeepdev\EnvironmentManager\Services\EnvParser;
use Pradeepdev\EnvironmentManager\Services\EnvWriter;

beforeEach(function () {
    $this->parser = new EnvParser;
});

it('parses simple key=value pairs', function () {
    $lines = $this->parser->parseContents("APP_NAME=Laravel\nAPP_ENV=local\n");

    $map = $this->parser->toKeyMap($lines);
    expect($map)->toHaveKey('APP_NAME')
        ->and($map['APP_NAME']->value)->toBe('Laravel')
        ->and($map['APP_ENV']->value)->toBe('local');
});

it('preserves comment lines', function () {
    $lines = $this->parser->parseContents("# This is a comment\nAPP_NAME=Test\n");

    expect($lines[0]->isComment())->toBeTrue()
        ->and($lines[0]->raw)->toBe('# This is a comment');
});

it('preserves blank lines', function () {
    $lines = $this->parser->parseContents("APP_NAME=Test\n\nAPP_ENV=local\n");

    expect($lines[1]->isBlank())->toBeTrue();
});

it('strips double-quoted values', function () {
    $lines = $this->parser->parseContents('APP_NAME="My App"');

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_NAME']->value)->toBe('My App')
        ->and($map['APP_NAME']->quoteStyle)->toBe('"');
});

it('strips single-quoted values', function () {
    $lines = $this->parser->parseContents("APP_NAME='My App'");

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_NAME']->value)->toBe('My App')
        ->and($map['APP_NAME']->quoteStyle)->toBe("'");
});

it('handles empty values', function () {
    $lines = $this->parser->parseContents('APP_KEY=');

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_KEY']->value)->toBe('');
});

it('handles empty quoted values', function () {
    $lines = $this->parser->parseContents('APP_KEY=""');

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_KEY']->value)->toBe('');
});

it('strips inline comments from unquoted values', function () {
    $lines = $this->parser->parseContents('APP_ENV=local # development only');

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_ENV']->value)->toBe('local')
        ->and($map['APP_ENV']->inlineComment)->toBe('# development only');
});

it('does not strip inline comments inside quoted values', function () {
    $lines = $this->parser->parseContents('APP_NAME="My App # not a comment"');

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_NAME']->value)->toBe('My App # not a comment');
});

it('throws EnvFileNotFoundException for missing file', function () {
    $this->parser->parseFile('/nonexistent/path/.env');
})->throws(EnvFileNotFoundException::class);

it('parses a file with multiple variable types', function () {
    $content = implode("\n", [
        '# App',
        'APP_NAME=Laravel',
        'APP_DEBUG=true',
        'APP_PORT=8080',
        'APP_KEY=',
        '',
        '# Database',
        'DB_CONNECTION=mysql',
    ]);

    $lines = $this->parser->parseContents($content);
    $map   = $this->parser->toKeyMap($lines);

    expect($map)->toHaveKeys(['APP_NAME', 'APP_DEBUG', 'APP_PORT', 'APP_KEY', 'DB_CONNECTION'])
        ->and($map['APP_DEBUG']->value)->toBe('true')
        ->and($map['APP_PORT']->value)->toBe('8080');
});

it('round-trips: parse then write produces equivalent keys', function () {
    $content = "APP_NAME=Laravel\n# comment\nDB_HOST=127.0.0.1\n\nAPP_ENV=local\n";

    $lines  = $this->parser->parseContents($content);
    $writer = new EnvWriter;
    $output = $writer->linesToString($lines);
    $lines2 = $this->parser->parseContents($output);

    $map1 = $this->parser->toKeyMap($lines);
    $map2 = $this->parser->toKeyMap($lines2);

    expect(array_keys($map1))->toEqual(array_keys($map2));
    foreach ($map1 as $key => $line) {
        expect($map2[$key]->value)->toBe($line->value);
    }
});

it('handles values with equals signs', function () {
    $lines = $this->parser->parseContents('APP_KEY=base64:abc==');

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_KEY']->value)->toBe('base64:abc==');
});

it('handles empty .env content gracefully', function () {
    $lines = $this->parser->parseContents('');

    expect($lines)->toBeArray();
});

it('skips malformed lines without fatal error', function () {
    // line with no = sign is malformed
    $lines = $this->parser->parseContents("INVALID_LINE\nAPP_NAME=Test");

    $map = $this->parser->toKeyMap($lines);
    expect($map)->toHaveKey('APP_NAME');
});

it('returns all keys', function () {
    $lines = $this->parser->parseContents("A=1\nB=2\nC=3\n");
    expect($this->parser->getKeys($lines))->toEqual(['A', 'B', 'C']);
});
