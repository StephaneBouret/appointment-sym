<?php

namespace App\Tests\AdminUser;

use App\Tests\Prestations\TestKernel;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class AdminUserKernel extends TestKernel
{
    public function getCacheDir(): string { return $this->getProjectDir().'/var/admin-user-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/admin-user-tests/log'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        // Keep the real CRUD controller and observe the boundary immediately before its hook.
        $container->services()->set(UserCrudWriteObserver::class)
            ->decorate('doctrine.orm.default_entity_manager')
            ->args([service('.inner')])
            ->tag('kernel.event_listener', ['event' => BeforeEntityUpdatedEvent::class, 'method' => 'beforeUpdateEntity']);
    }
}
