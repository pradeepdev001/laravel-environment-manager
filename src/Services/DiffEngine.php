<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

class DiffEngine
{
    public const STATUS_ADDED    = 'added';
    public const STATUS_REMOVED  = 'removed';
    public const STATUS_MODIFIED = 'modified';
    public const STATUS_UNCHANGED = 'unchanged';

    /**
     * Compute the diff between two key-value maps.
     *
     * @param  array<string, string>  $old
     * @param  array<string, string>  $new
     * @return array<string, array{status: string, old: ?string, new: ?string}>
     */
    public function diff(array $old, array $new): array
    {
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        sort($allKeys);

        $result = [];

        foreach ($allKeys as $key) {
            $hasOld = array_key_exists($key, $old);
            $hasNew = array_key_exists($key, $new);

            if ($hasOld && ! $hasNew) {
                $result[$key] = [
                    'status' => self::STATUS_REMOVED,
                    'old'    => $old[$key],
                    'new'    => null,
                ];
            } elseif (! $hasOld && $hasNew) {
                $result[$key] = [
                    'status' => self::STATUS_ADDED,
                    'old'    => null,
                    'new'    => $new[$key],
                ];
            } elseif ($old[$key] !== $new[$key]) {
                $result[$key] = [
                    'status' => self::STATUS_MODIFIED,
                    'old'    => $old[$key],
                    'new'    => $new[$key],
                ];
            } else {
                $result[$key] = [
                    'status' => self::STATUS_UNCHANGED,
                    'old'    => $old[$key],
                    'new'    => $new[$key],
                ];
            }
        }

        return $result;
    }

    /**
     * Returns only the changed entries (added, removed, modified).
     *
     * @param  array<string, string>  $old
     * @param  array<string, string>  $new
     * @return array<string, array{status: string, old: ?string, new: ?string}>
     */
    public function changesOnly(array $old, array $new): array
    {
        return array_filter(
            $this->diff($old, $new),
            fn ($entry) => $entry['status'] !== self::STATUS_UNCHANGED
        );
    }

    /**
     * Whether two env maps are identical.
     *
     * @param  array<string, string>  $a
     * @param  array<string, string>  $b
     */
    public function isIdentical(array $a, array $b): bool
    {
        return empty($this->changesOnly($a, $b));
    }

    /**
     * Mask sensitive values in a diff result.
     *
     * @param  array<string, array{status: string, old: ?string, new: ?string}>  $diff
     * @return array<string, array{status: string, old: ?string, new: ?string}>
     */
    public function maskSensitive(array $diff, SensitivityDetector $detector): array
    {
        foreach ($diff as $key => &$entry) {
            if ($detector->isSensitive($key)) {
                $entry['old'] = $entry['old'] !== null ? '••••••••' : null;
                $entry['new'] = $entry['new'] !== null ? '••••••••' : null;
            }
        }

        return $diff;
    }
}
