<?php

namespace App\Tests\Prestations;

use App\EventSubscriber\AuthenticationTargetSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AuthenticationTargetTest extends TestCase
{
    public static function destinations(): iterable
    {
        yield ['/rendez-vous/type/42/form', '/rendez-vous/type/42/form'];
        yield ['https://example.test/rendez-vous/type/42/form', '/rendez-vous/type/42/form'];
        yield ['/rendez-vous/types/42?source=local', '/rendez-vous/types/42?source=local'];
        foreach (['https://evil.test/path', '//evil.test', '/\\evil.test', '/%5cevil.test', '/%2fevil.test',
            '/%252fevil.test', '/%0d%0aLocation:https://evil.test', " /safe", "\t/safe", 'javascript:alert(1)',
            'https://example.test.evil.test/path', 'https://example.test@evil.test/path', ['bad']] as $invalid) {
            yield [$invalid, null];
        }
    }

    #[DataProvider('destinations')]
    public function testReturnDestinationsAreValidatedBeforeTheFirewall(mixed $destination, ?string $expected): void
    {
        foreach (['app_login', 'app_register', '2fa_login_check', 'app_login_success'] as $route) {
            $request = Request::create('https://example.test/login');
            $request->attributes->set('_route', $route);
            $request->setSession(new Session(new MockArraySessionStorage()));
            // The session can also contain a target saved by the firewall.
            $request->getSession()->set('_security.main.target_path', is_string($destination) ? $destination : null);
            $request->query->set('target', $destination);
            if ($route === 'app_login') {
                $request->request->set('_target_path', $destination);
            }
            $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
            (new AuthenticationTargetSubscriber())->onRequest($event);
            self::assertSame($expected, $request->getSession()->get('_security.main.target_path'));
            self::assertFalse($request->request->has('_target_path'));
        }
    }
}
