<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

class CategoryDetector
{
    private array $rules;

    public function __construct(array $configRules = [])
    {
        // Merge config rules with defaults; config takes precedence via array union
        $this->rules = $configRules ?: $this->defaultRules();
    }

    public function detect(string $key): string
    {
        $upperKey = strtoupper($key);

        foreach ($this->rules as $prefix => $category) {
            if (str_starts_with($upperKey, strtoupper($prefix))) {
                return $category;
            }
        }

        return 'Custom';
    }

    private function defaultRules(): array
    {
        return [
            'APP_'        => 'Application',
            'LOG_'        => 'Application',
            'DB_'         => 'Database',
            'MAIL_'       => 'Mail',
            'MAILGUN_'    => 'Mail',
            'SES_'        => 'Mail',
            'POSTMARK_'   => 'Mail',
            'CACHE_'      => 'Cache',
            'MEMCACHED_'  => 'Cache',
            'QUEUE_'      => 'Queue',
            'BROADCAST_'  => 'Broadcast',
            'PUSHER_'     => 'Broadcast',
            'ABLY_'       => 'Broadcast',
            'FILESYSTEM_' => 'Filesystem',
            'REDIS_'      => 'Redis',
            'AWS_'        => 'AWS',
            'STRIPE_'     => 'Stripe',
            'SESSION_'    => 'Session',
            'SANCTUM_'    => 'Security',
            'JWT_'        => 'Security',
        ];
    }
}
