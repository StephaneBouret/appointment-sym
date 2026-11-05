<?php

namespace App\Tests\Regression;

use App\Service\PdfGeneratorService;
use App\Tests\Prestations\TestKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

final class InvoiceProbe extends AbstractExtension
{
    public static int $renders = 0;
    public static int $pdfCalls = 0;

    public function getFunctions(): array
    {
        return [new TwigFunction('record_invoice_render', static function (): string {
            ++self::$renders;
            return 'Synthetic invoice';
        })];
    }
}

final class SyntheticPdfGenerator extends PdfGeneratorService
{
    public function __construct() {}

    public function getStreamResponse(string $html, string $filename): Response
    {
        ++InvoiceProbe::$pdfCalls;
        return new Response($html, 200, ['Content-Type' => 'application/pdf']);
    }
}

final class RegressionKernel extends TestKernel
{
    public function getCacheDir(): string { return $this->getProjectDir().'/var/regression-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/regression-tests/log'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        $container->services()->set(PdfGeneratorService::class, SyntheticPdfGenerator::class);
        $container->services()->set('test.invoice_loader', ArrayLoader::class)
            ->args([['appointment/pdf.html.twig' => '{{ record_invoice_render() }}']])
            ->tag('twig.loader', ['priority' => 100]);
        $container->services()->set(InvoiceProbe::class)->tag('twig.extension');
    }
}
