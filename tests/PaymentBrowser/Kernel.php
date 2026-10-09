<?php

namespace App\Tests\PaymentBrowser;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

final class CaptureTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $mail = $message->getOriginalMessage();
        $data = ['subject' => $mail->getSubject(), 'to' => array_map(fn ($a) => $a->getAddress(), $mail->getTo()),
            'text' => $mail->getTextBody(), 'html' => $mail->getHtmlBody(), 'at' => gmdate('c')];
        file_put_contents(state().'/mail/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json', json_encode($data, JSON_THROW_ON_ERROR));
    }
    public function __toString(): string { return 'recette-local-capture'; }
}

final class LocalErrorController
{
    public function __invoke(\Throwable $exception): \Symfony\Component\HttpFoundation\Response
    {
        return new \Symfony\Component\HttpFoundation\Response($exception::class.' at '.basename($exception->getFile()).':'.$exception->getLine(), 500);
    }
}

final class Kernel extends \App\Kernel
{
    public function getProjectDir(): string { return dirname(__DIR__, 2); }
    public function getCacheDir(): string { return state().'/cache'; }
    public function getLogDir(): string { return state().'/log'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import($this->getProjectDir().'/config/packages/*.yaml');
        $container->import($this->getProjectDir().'/config/services.yaml');
        $container->extension('framework', ['test' => true, 'error_controller' => LocalErrorController::class, 'router' => ['default_uri' => 'http://127.0.0.1:8097'],
            'session' => ['name' => 'PAYMENT_RECETTE_SESSION', 'save_path' => state().'/sessions'],
            'mailer' => ['dsn' => 'null://null', 'message_bus' => false]]);
        // Override the aggregate transport itself: no SMTP transport can be reached.
        $container->services()->set('mailer.transports', CaptureTransport::class)
            ->args([new \Symfony\Component\DependencyInjection\Reference('event_dispatcher')])->public();
        $container->services()->set(LocalErrorController::class)->public()->tag('controller.service_arguments');
    }
}
