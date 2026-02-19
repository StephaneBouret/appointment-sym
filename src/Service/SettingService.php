<?php

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SettingService
{
    private const CACHE_PREFIX = 'settings.';

    public function __construct(
        private readonly SettingRepository $repo,
        private readonly CacheInterface $cache,
    ) {}

    public function getBool(string $key, bool $default = false, int $ttlSeconds = 300): bool
    {
        return $this->cache->get(self::CACHE_PREFIX . $key, function (ItemInterface $item) use ($key, $default, $ttlSeconds): bool {
            $item->expiresAfter($ttlSeconds);

            $setting = $this->repo->findOneBy(['settingKey' => $key]);
            return $setting ? (bool) $setting->getValue() : $default;
        });
    }

    public function isMaintenanceEnabled(): bool
    {
        return $this->getBool(Setting::KEY_MAINTENANCE, false, 300);
    }

    public function invalidate(string $key): void
    {
        $this->cache->delete(self::CACHE_PREFIX . $key);
    }

    public function invalidateMaintenance(): void
    {
        $this->invalidate(Setting::KEY_MAINTENANCE);
    }
}
