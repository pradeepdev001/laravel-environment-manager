<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Services\CacheManager;

it('skips cache commands in local environment by default', function () {
    $manager = new CacheManager(
        ['config:clear', 'config:cache'],
        true,
        false,
        'local',
    );

    expect($manager->run())->toBe([]);
});

it('skips cache commands when cache_after_save is disabled', function () {
    $manager = new CacheManager(
        ['config:clear'],
        false,
        true,
        'production',
    );

    expect($manager->run())->toBe([]);
});
