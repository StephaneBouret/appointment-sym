<?php

namespace App\Tests\AdminUser;

use App\Tests\Prestations\TestKernel;

final class AdminUserKernel extends TestKernel
{
    public function getCacheDir(): string { return $this->getProjectDir().'/var/admin-user-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/admin-user-tests/log'; }
}
