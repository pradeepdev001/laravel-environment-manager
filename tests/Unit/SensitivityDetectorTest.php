<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Services\SensitivityDetector;

beforeEach(function () {
    $this->detector = new SensitivityDetector;
});

it('flags APP_KEY as sensitive', function () {
    expect($this->detector->isSensitive('APP_KEY'))->toBeTrue();
});

it('flags password keys as sensitive', function () {
    expect($this->detector->isSensitive('DB_PASSWORD'))->toBeTrue();
    expect($this->detector->isSensitive('REDIS_PASSWORD'))->toBeTrue();
    expect($this->detector->isSensitive('MAIL_PASSWORD'))->toBeTrue();
});

it('flags secret keys as sensitive', function () {
    expect($this->detector->isSensitive('AWS_SECRET_ACCESS_KEY'))->toBeTrue();
    expect($this->detector->isSensitive('STRIPE_SECRET'))->toBeTrue();
    expect($this->detector->isSensitive('JWT_SECRET'))->toBeTrue();
});

it('flags api key patterns as sensitive', function () {
    expect($this->detector->isSensitive('GOOGLE_API_KEY'))->toBeTrue();
    expect($this->detector->isSensitive('MAILGUN_API_KEY'))->toBeTrue();
});

it('flags token patterns as sensitive', function () {
    expect($this->detector->isSensitive('SANCTUM_TOKEN'))->toBeTrue();
    expect($this->detector->isSensitive('PERSONAL_ACCESS_TOKEN'))->toBeTrue();
});

it('flags Stripe keys as sensitive', function () {
    expect($this->detector->isSensitive('STRIPE_KEY'))->toBeTrue();
    expect($this->detector->isSensitive('STRIPE_WEBHOOK_SECRET'))->toBeTrue();
});

it('does not flag safe keys as sensitive', function () {
    expect($this->detector->isSensitive('APP_NAME'))->toBeFalse();
    expect($this->detector->isSensitive('APP_ENV'))->toBeFalse();
    expect($this->detector->isSensitive('DB_HOST'))->toBeFalse();
    expect($this->detector->isSensitive('APP_URL'))->toBeFalse();
    expect($this->detector->isSensitive('MAIL_PORT'))->toBeFalse();
});

it('masks sensitive values', function () {
    expect($this->detector->mask('APP_KEY', 'secret123'))->toBe('••••••••');
});

it('does not mask non-sensitive values', function () {
    expect($this->detector->mask('APP_NAME', 'Laravel'))->toBe('Laravel');
});

it('reveals when reveal flag is true', function () {
    expect($this->detector->mask('APP_KEY', 'secret123', true))->toBe('secret123');
});

it('accepts custom patterns from config', function () {
    $detector = new SensitivityDetector(['MY_CUSTOM_*']);
    expect($detector->isSensitive('MY_CUSTOM_TOKEN'))->toBeTrue();
    expect($detector->isSensitive('MY_OTHER_KEY'))->toBeFalse();
});

it('is case-insensitive for key matching', function () {
    expect($this->detector->isSensitive('app_key'))->toBeTrue();
    expect($this->detector->isSensitive('db_password'))->toBeTrue();
});
