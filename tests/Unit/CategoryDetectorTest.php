<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Services\CategoryDetector;

beforeEach(function () {
    $this->detector = new CategoryDetector();
});

it('detects Application category', function () {
    expect($this->detector->detect('APP_NAME'))->toBe('Application');
    expect($this->detector->detect('APP_ENV'))->toBe('Application');
    expect($this->detector->detect('LOG_CHANNEL'))->toBe('Application');
});

it('detects Database category', function () {
    expect($this->detector->detect('DB_HOST'))->toBe('Database');
    expect($this->detector->detect('DB_CONNECTION'))->toBe('Database');
    expect($this->detector->detect('DB_PASSWORD'))->toBe('Database');
});

it('detects Mail category', function () {
    expect($this->detector->detect('MAIL_MAILER'))->toBe('Mail');
    expect($this->detector->detect('MAILGUN_SECRET'))->toBe('Mail');
    expect($this->detector->detect('SES_KEY'))->toBe('Mail');
});

it('detects Cache category', function () {
    expect($this->detector->detect('CACHE_DRIVER'))->toBe('Cache');
    expect($this->detector->detect('MEMCACHED_HOST'))->toBe('Cache');
});

it('detects Queue category', function () {
    expect($this->detector->detect('QUEUE_CONNECTION'))->toBe('Queue');
});

it('detects Broadcast category', function () {
    expect($this->detector->detect('BROADCAST_DRIVER'))->toBe('Broadcast');
    expect($this->detector->detect('PUSHER_APP_KEY'))->toBe('Broadcast');
});

it('detects Redis category', function () {
    expect($this->detector->detect('REDIS_HOST'))->toBe('Redis');
    expect($this->detector->detect('REDIS_PASSWORD'))->toBe('Redis');
});

it('detects AWS category', function () {
    expect($this->detector->detect('AWS_ACCESS_KEY_ID'))->toBe('AWS');
    expect($this->detector->detect('AWS_SECRET_ACCESS_KEY'))->toBe('AWS');
});

it('detects Stripe category', function () {
    expect($this->detector->detect('STRIPE_KEY'))->toBe('Stripe');
    expect($this->detector->detect('STRIPE_SECRET'))->toBe('Stripe');
});

it('falls back to Custom for unknown keys', function () {
    expect($this->detector->detect('MY_CUSTOM_VAR'))->toBe('Custom');
    expect($this->detector->detect('SOME_API_KEY'))->toBe('Custom');
    expect($this->detector->detect('UNKNOWN'))->toBe('Custom');
});

it('allows custom category rules via config', function () {
    $detector = new CategoryDetector(['CUSTOM_' => 'MyCategory']);
    expect($detector->detect('CUSTOM_VALUE'))->toBe('MyCategory');
});
