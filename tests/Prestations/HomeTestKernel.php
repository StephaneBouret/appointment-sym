<?php

namespace App\Tests\Prestations;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** Reuses the isolated SQLite, mail doubles and HTTP protections. */
final class HomeTestKernel extends TestKernel
{
    public function __construct(string $environment, bool $debug, private readonly string $mode = 'configured')
    {
        if (!in_array($mode, ['configured', 'absent', 'missing', 'disabled', 'alternate'], true)) {
            throw new \InvalidArgumentException('Unknown homepage test configuration.');
        }
        parent::__construct($environment, $debug);
    }

    public function getCacheDir(): string { return $this->getProjectDir().'/var/prestations-tests/home/'.$this->mode; }
    protected function getContainerClass(): string { return parent::getContainerClass().'_'.$this->mode; }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new class($this->mode) implements CompilerPassInterface {
            public function __construct(private readonly string $mode) {}

            public function process(ContainerBuilder $container): void
            {
                $parameter = 'app.home_featured_appointment_type_id';
                if ($this->mode === 'absent') {
                    $container->getParameterBag()->remove($parameter);
                } elseif ($this->mode !== 'configured') {
                    $container->setParameter($parameter, match ($this->mode) {
                        'missing' => 999999,
                        'disabled' => null,
                        'alternate' => 2,
                    });
                }
            }
        });
    }
}
