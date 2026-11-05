<?php

namespace App\Tests\Regression;

use App\Entity\User;
use App\Service\PasswordResetService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final class PasswordResetExpirationTest extends TestCase
{
    public static function dates(): iterable
    {
        yield 'missing' => [null, true];
        yield 'recent' => ['2030-06-01 11:00:00.000000', false];
        yield 'expired' => ['2030-06-01 08:00:00.000000', true];
        yield 'one microsecond before expiry' => ['2030-06-01 09:00:00.000001', false];
        yield 'exact equality remains valid' => ['2030-06-01 09:00:00.000000', false];
        yield 'one microsecond after expiry' => ['2030-06-01 08:59:59.999999', true];
    }

    #[DataProvider('dates')]
    public function testExpirationKeepsStrictBoundary(?string $created, bool $expired): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');
        $mailer = $this->createMock(SendMailService::class);
        $mailer->expects(self::never())->method('sendMail');
        $service = new PasswordResetService($this->createStub(TokenGeneratorInterface::class), $em,
            $this->createStub(UrlGeneratorInterface::class), $mailer);
        $user = (new User())->setResetToken('synthetic')->setCreatedTokenAt($created ? new \DateTimeImmutable($created) : null);
        self::assertSame($expired, $service->isTokenExpired($user, 3, new \DateTimeImmutable('2030-06-01 12:00:00.000000')));
    }
}
