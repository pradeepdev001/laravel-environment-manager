<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationDispatcher
{
    public function __construct(
        private readonly array $config,
    ) {}

    /**
     * Dispatch a notification about an environment change.
     */
    public function dispatch(
        string $action,
        string $key,
        bool $sensitive = false,
        string $environment = '',
    ): void {
        $shouldNotify = ($this->config['on_env_update'] ?? false) || $sensitive;

        if (! $shouldNotify) {
            return;
        }

        $payload = [
            'action'      => $action,
            'key'         => $key,
            'value'       => $sensitive ? '[REDACTED]' : null,
            'environment' => $environment ?: config('app.env'),
            'timestamp'   => now()->toIso8601String(),
        ];

        $this->notifyMail($payload, $key, $sensitive);
        $this->notifySlack($payload, $key, $sensitive);
        $this->notifyTeams($payload, $key, $sensitive);
        $this->notifyWebhook($payload);
    }

    private function notifyMail(array $payload, string $key, bool $sensitive): void
    {
        $mailConfig = $this->config['mail'] ?? [];
        if (empty($mailConfig['enabled']) || empty($mailConfig['recipients'])) {
            return;
        }

        try {
            $subject = $sensitive
                ? "[ENV] Sensitive variable changed: {$key}"
                : "[ENV] Environment variable updated: {$key}";

            foreach ((array) $mailConfig['recipients'] as $recipient) {
                Mail::raw(
                    "Action: {$payload['action']}\nKey: {$payload['key']}\nEnvironment: {$payload['environment']}\nTimestamp: {$payload['timestamp']}",
                    function ($msg) use ($recipient, $subject) {
                        $msg->to($recipient)->subject($subject);
                    },
                );
            }
        } catch (\Throwable $e) {
            Log::error("EnvironmentManager: Mail notification failed: {$e->getMessage()}");
        }
    }

    private function notifySlack(array $payload, string $key, bool $sensitive): void
    {
        $slackConfig = $this->config['slack'] ?? [];
        if (empty($slackConfig['enabled']) || empty($slackConfig['webhook_url'])) {
            return;
        }

        try {
            $emoji = $sensitive ? ':rotating_light:' : ':env:';
            Http::post($slackConfig['webhook_url'], [
                'text' => "{$emoji} *[ENV Manager]* {$payload['action']} on `{$key}` in `{$payload['environment']}` at {$payload['timestamp']}",
            ]);
        } catch (\Throwable $e) {
            Log::error("EnvironmentManager: Slack notification failed: {$e->getMessage()}");
        }
    }

    private function notifyTeams(array $payload, string $key, bool $sensitive): void
    {
        $teamsConfig = $this->config['teams'] ?? [];
        if (empty($teamsConfig['enabled']) || empty($teamsConfig['webhook_url'])) {
            return;
        }

        try {
            Http::post($teamsConfig['webhook_url'], [
                '@type'    => 'MessageCard',
                '@context' => 'http://schema.org/extensions',
                'summary'  => "ENV Manager: {$payload['action']} on {$key}",
                'sections' => [[
                    'activityTitle'    => "ENV Manager: {$payload['action']}",
                    'activitySubtitle' => "Key: `{$key}` | Env: `{$payload['environment']}`",
                    'activityText'     => "Timestamp: {$payload['timestamp']}",
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::error("EnvironmentManager: Teams notification failed: {$e->getMessage()}");
        }
    }

    private function notifyWebhook(array $payload): void
    {
        $webhookConfig = $this->config['webhook'] ?? [];
        if (empty($webhookConfig['enabled']) || empty($webhookConfig['url'])) {
            return;
        }

        try {
            Http::post($webhookConfig['url'], $payload);
        } catch (\Throwable $e) {
            Log::error("EnvironmentManager: Webhook notification failed: {$e->getMessage()}");
        }
    }
}
