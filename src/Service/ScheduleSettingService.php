<?php

namespace App\Service;

use App\Repository\ScheduleSettingRepository;

final class ScheduleSettingService
{
    public function __construct(private readonly ScheduleSettingRepository $repo) {}

    public function get(string $key, ?string $default = null): ?string
    {
        $setting = $this->repo->findOneBy(['setting_key' => $key]);
        return $setting?->getValue() ?? $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $v = $this->get($key);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function getCsvIntList(string $key, string $defaultCsv = ''): array
    {
        $raw = $this->get($key, $defaultCsv) ?? '';
        $parts = array_filter(array_map('trim', explode(',', $raw)), static fn($v) => $v !== '');
        $ints = array_values(array_filter(array_map('intval', $parts), static fn($v) => $v >= 0));
        return $ints; // ex: [1,2,3,4,5]
    }

    // Booléen robuste (1/0, true/false, yes/no)
    public function getBool(string $key, bool $default = false): bool
    {
        $v = strtolower((string) ($this->get($key) ?? ''));
        if ($v === '') return $default;
        return in_array($v, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
