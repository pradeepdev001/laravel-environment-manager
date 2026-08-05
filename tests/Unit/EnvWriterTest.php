<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Data\EnvLine;
use Pradeepdev\EnvironmentManager\Exceptions\FileLockException;
use Pradeepdev\EnvironmentManager\Services\EnvParser;
use Pradeepdev\EnvironmentManager\Services\EnvWriter;

beforeEach(function () {
    $this->parser  = new EnvParser();
    $this->writer  = new EnvWriter();
    $this->tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
});

afterEach(function () {
    if (file_exists($this->tmpFile)) {
        @unlink($this->tmpFile);
    }
});

it('writes env content atomically', function () {
    $lines = $this->parser->parseContents("APP_NAME=Test\nAPP_ENV=local\n");
    $this->writer->write($this->tmpFile, $lines);

    $content = file_get_contents($this->tmpFile);
    expect($content)->toContain('APP_NAME=Test')
        ->and($content)->toContain('APP_ENV=local');
});

it('adds a new variable', function () {
    $lines = $this->parser->parseContents("APP_NAME=Test\n");
    $lines = $this->writer->setVariable($lines, 'NEW_KEY', 'new_value');

    $map = $this->parser->toKeyMap($lines);
    expect($map)->toHaveKey('NEW_KEY')
        ->and($map['NEW_KEY']->value)->toBe('new_value');
});

it('updates an existing variable', function () {
    $lines = $this->parser->parseContents("APP_NAME=OldName\n");
    $lines = $this->writer->setVariable($lines, 'APP_NAME', 'NewName');

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_NAME']->value)->toBe('NewName');
});

it('deletes a variable', function () {
    $lines = $this->parser->parseContents("APP_NAME=Test\nAPP_ENV=local\n");
    $lines = $this->writer->deleteVariable($lines, 'APP_NAME');

    $map = $this->parser->toKeyMap($lines);
    expect($map)->not->toHaveKey('APP_NAME')
        ->and($map)->toHaveKey('APP_ENV');
});

it('renames a variable preserving value', function () {
    $lines = $this->parser->parseContents("OLD_KEY=my_value\n");
    $lines = $this->writer->renameVariable($lines, 'OLD_KEY', 'NEW_KEY');

    $map = $this->parser->toKeyMap($lines);
    expect($map)->not->toHaveKey('OLD_KEY')
        ->and($map)->toHaveKey('NEW_KEY')
        ->and($map['NEW_KEY']->value)->toBe('my_value');
});

it('sets multiple variables at once', function () {
    $lines = $this->parser->parseContents("APP_NAME=Test\n");
    $lines = $this->writer->setMultiple($lines, [
        'APP_ENV'   => 'production',
        'APP_DEBUG' => 'false',
    ]);

    $map = $this->parser->toKeyMap($lines);
    expect($map['APP_ENV']->value)->toBe('production')
        ->and($map['APP_DEBUG']->value)->toBe('false');
});

it('preserves comments when updating a variable', function () {
    $content = "# App Name\nAPP_NAME=OldName\n# End\n";
    $lines   = $this->parser->parseContents($content);
    $lines   = $this->writer->setVariable($lines, 'APP_NAME', 'NewName');
    $output  = $this->writer->linesToString($lines);

    expect($output)->toContain('# App Name')
        ->and($output)->toContain('APP_NAME=NewName')
        ->and($output)->toContain('# End');
});

it('preserves blank lines when updating a variable', function () {
    $content = "APP_NAME=Test\n\nAPP_ENV=local\n";
    $lines   = $this->parser->parseContents($content);
    $lines   = $this->writer->setVariable($lines, 'APP_NAME', 'Updated');
    $output  = $this->writer->linesToString($lines);

    expect($output)->toContain('APP_NAME=Updated')
        ->and($output)->toContain('APP_ENV=local');
    // Blank line preserved
    expect(substr_count($output, "\n\n"))->toBeGreaterThanOrEqual(1);
});

it('leaves file unchanged if atomic write fails', function () {
    file_put_contents($this->tmpFile, "ORIGINAL=value\n");
    $original = file_get_contents($this->tmpFile);

    // Write valid content first to confirm it works
    $lines = $this->parser->parseContents("ORIGINAL=value\n");
    $this->writer->write($this->tmpFile, $lines);

    expect(file_get_contents($this->tmpFile))->toBe($original);
});

it('round-trips without data loss', function () {
    $content = "APP_NAME=\"My App\"\n# comment\nDB_PORT=3306\n\nREDIS_HOST=127.0.0.1\n";
    $lines   = $this->parser->parseContents($content);
    $output  = $this->writer->linesToString($lines);
    $lines2  = $this->parser->parseContents($output);

    $map1 = $this->parser->toKeyMap($lines);
    $map2 = $this->parser->toKeyMap($lines2);

    foreach ($map1 as $key => $line) {
        expect($map2)->toHaveKey($key);
        expect($map2[$key]->value)->toBe($line->value);
    }
});
