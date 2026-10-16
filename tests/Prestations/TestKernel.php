<?php

namespace App\Tests\Prestations;

use App\Entity\Avatar;
use App\Entity\User;
use App\Service\AvatarService;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\MockHttpClient;

final class CaptureAuthMailer implements AuthCodeMailerInterface
{
    public static array $codes = [];

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        self::$codes[$user->getEmailAuthRecipient()] = $user->getEmailAuthCode();
    }
}

final class SyntheticAvatarService extends AvatarService
{
    public function createAndAssignAvatar(User $user): Avatar
    {
        $avatar = new Avatar($user);
        $avatar->setImageName('synthetic-avatar.png');
        return $avatar;
    }
}

class TestKernel extends \App\Kernel
{
    public function getProjectDir(): string { return dirname(__DIR__, 2); }
    public function getCacheDir(): string { return $this->getProjectDir().'/var/prestations-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/prestations-tests/log'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import($this->getProjectDir().'/config/packages/*.yaml');
        $container->import($this->getProjectDir().'/config/services.yaml');
        $container->extension('framework', ['test' => true, 'mailer' => ['dsn' => 'null://null', 'message_bus' => false],
            'cache' => ['app' => 'cache.adapter.array', 'pools' => ['cache.rate_limiter' => ['adapter' => 'cache.adapter.array']]]]);
        $container->services()->set('auth_mailer', CaptureAuthMailer::class)->public();
        $container->services()->set(AvatarService::class, SyntheticAvatarService::class)->autowire();
        $container->services()->set('http_client', MockHttpClient::class)->args([]);
    }
}
