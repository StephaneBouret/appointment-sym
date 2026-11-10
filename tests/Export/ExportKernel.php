<?php
namespace App\Tests\Export;

final class ExportKernel extends \App\Tests\Prestations\TestKernel
{
    public function getCacheDir(): string { return $this->getProjectDir().'/var/export-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/export-tests/log'; }
}
