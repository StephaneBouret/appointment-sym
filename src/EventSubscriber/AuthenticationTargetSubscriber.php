<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/** Validate return destinations before either authentication firewall consumes them. */
final class AuthenticationTargetSubscriber implements EventSubscriberInterface
{
    use TargetPathTrait;

    public static function getSubscribedEvents(): array
    {
        // After routing (32) and session initialization, before the firewall (8).
        return [KernelEvents::REQUEST => ['onRequest', 9]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!in_array($route, ['app_login', 'app_register', 'app_login_success', '2fa_login', '2fa_login_check'], true)) {
            return;
        }

        $session = $request->getSession();
        $target = $this->normalize($this->getTargetPath($session, 'main'), $request);
        if (in_array($route, ['app_login', 'app_register'], true)) {
            $target = $this->normalize($request->query->all()['target'] ?? null, $request) ?? $target;
        }
        if ($route === 'app_login') {
            // Symfony also accepts _target_path on login POST/GET. Validate it here
            // and use the same session target for login, registration and email 2FA.
            $submitted = $request->request->all()['_target_path'] ?? $request->query->all()['_target_path'] ?? null;
            $target = $this->normalize($submitted, $request) ?? $target;
            $request->request->remove('_target_path');
            $request->query->remove('_target_path');
        }

        $this->removeTargetPath($session, 'main');
        if ($target !== null) {
            $this->saveTargetPath($session, 'main', $target);
        }
    }

    private function normalize(mixed $target, Request $request): ?string
    {
        if (!is_string($target) || $target === '' || strlen($target) > 2048) {
            return null;
        }
        // Symfony saves absolute URLs for protected requests. Only accept the
        // current origin and turn them into root-relative paths.
        $origin = $request->getSchemeAndHttpHost();
        if (str_starts_with($target, $origin.'/')) {
            $target = substr($target, strlen($origin));
        }

        $decoded = $target;
        for ($i = 0; $i < 8; ++$i) {
            if (!str_starts_with($decoded, '/') || str_starts_with($decoded, '//')
                || preg_match('/[\\\\\x00-\x20\x7f]/', $decoded)) {
                return null;
            }
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                return $target;
            }
            $decoded = $next;
        }

        return null;
    }
}
