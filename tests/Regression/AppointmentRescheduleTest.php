<?php

namespace App\Tests\Regression;

use App\Controller\Appointment\AppointmentRescheduleController;
use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Entity\ScheduleSetting;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Event\AppointmentRescheduledEvent;
use App\Repository\AppointmentRepository;
use App\Repository\ScheduleSettingRepository;
use App\Repository\UnavailabilityRepository;
use App\Repository\UnavailableDayRepository;
use App\Service\ScheduleSettingService;
use App\Service\SlotService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

final class AppointmentRescheduleTest extends TestCase
{
    public static function cases(): iterable
    {
        yield 'missing start' => ['missing-start', 409];
        yield 'missing end' => ['missing-end', 409];
        yield 'both missing' => ['missing-both', 409];
        yield 'other owner before date checks' => ['other', 404];
        yield 'invalid CSRF before date checks' => ['csrf', 400];
        yield 'canceled' => ['canceled', 400];
        yield 'past appointment' => ['past', 422];
        yield 'original notice too short' => ['old-notice', 422];
        yield 'new notice too short' => ['new-notice', 422];
        yield 'unavailable slot' => ['unavailable', 409];
        yield 'valid winter UTC to Paris' => ['valid-winter', 200];
        yield 'valid summer UTC to Paris' => ['valid-summer', 200];
    }

    #[DataProvider('cases')]
    public function testReportPreservesGuardsAndOriginalDates(string $case, int $status): void
    {
        $owner = (new User())->setEmail('owner@example.test')->setPassword('unused-synthetic-hash');
        $type = (new AppointmentType())->setDuration(60);
        $appointment = (new Appointment())->setUser($owner)->setType($type)->setStatus(AppointmentStatus::CONFIRMED);
        (new \ReflectionProperty(Appointment::class, 'id'))->setValue($appointment, 42);
        $year = (int) date('Y') + 1;
        $month = $case === 'valid-summer' ? '08' : '02';
        $desired = new \DateTimeImmutable("$year-$month-15 10:00:00", new \DateTimeZone('Europe/Paris'));
        $oldStart = $desired->modify('-3 days')->setTimezone(new \DateTimeZone('UTC'));
        $oldEnd = $oldStart->modify('+1 hour');
        if ($case === 'past') { $oldStart = new \DateTimeImmutable('-1 day'); }
        if ($case === 'old-notice') { $oldStart = new \DateTimeImmutable('+1 hour'); }
        $appointment->setStartAt($oldStart)->setEndAt($oldEnd);
        if (in_array($case, ['missing-start', 'missing-both', 'other', 'csrf'], true)) {
            (new \ReflectionProperty(Appointment::class, 'startAt'))->setValue($appointment, null);
        }
        if (in_array($case, ['missing-end', 'missing-both'], true)) {
            (new \ReflectionProperty(Appointment::class, 'endAt'))->setValue($appointment, null);
        }
        if ($case === 'canceled') { $appointment->setStatus(AppointmentStatus::CANCELED); }
        if ($case === 'new-notice') { $desired = new \DateTimeImmutable('+1 hour'); }
        if ($case === 'unavailable') { $desired = $desired->setTime(23, 0); }
        $before = serialize($appointment);

        // Same controller wiring pattern as PaymentControllersTest; no database or transport.
        $stack = new RequestStack();
        $sessionRequest = Request::create('/');
        $sessionRequest->setSession(new Session(new MockArraySessionStorage()));
        $stack->push($sessionRequest);
        $csrf = new CsrfTokenManager(null, new SessionTokenStorage($stack));
        $token = $csrf->getToken('reschedule_appointment_42')->getValue();
        $storage = new TokenStorage();
        $storage->setToken(new UsernamePasswordToken($case === 'other' ? new User() : $owner, 'main', ['ROLE_USER']));
        $container = new Container();
        $container->set('security.token_storage', $storage);
        $container->set('security.csrf.token_manager', $csrf);

        $settingsRepo = $this->createStub(ScheduleSettingRepository::class);
        $settingsRepo->method('findOneBy')->willReturnCallback(static function (array $criteria): ?ScheduleSetting {
            $value = match ($criteria['setting_key']) {
                'open_days' => '1,2,3,4,5,6,7', 'opening_delay_hours' => '0', default => null,
            };
            return $value === null ? null : (new ScheduleSetting())->setValue($value);
        });
        $settings = new ScheduleSettingService($settingsRepo);
        $repo = $this->createStub(AppointmentRepository::class);
        $repo->method('find')->willReturn($appointment);
        $availabilityRepo = $this->createMock(AppointmentRepository::class);
        $availabilityRepo->expects(in_array($case, ['valid-winter', 'valid-summer', 'unavailable'], true) ? self::once() : self::never())
            ->method('findByDate')->willReturn([]);
        $days = $this->createStub(UnavailableDayRepository::class);
        $days->method('isUnavailable')->willReturn(false);
        $unavailability = $this->createStub(UnavailabilityRepository::class);
        $unavailability->method('hasAllDay')->willReturn(false);
        $unavailability->method('findForDate')->willReturn([]);
        $slots = new SlotService($availabilityRepo, $days, $unavailability, $settings);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($status === 200 ? self::once() : self::never())->method('flush');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        if ($status === 200) {
            $dispatcher->expects(self::once())->method('dispatch')->with(
                self::callback(static fn ($event) => $event instanceof AppointmentRescheduledEvent
                    && $event->getAppointment() === $appointment
                    && $event->getOldStartAt() === $oldStart && $event->getOldEndAt() === $oldEnd),
                AppointmentRescheduledEvent::NAME,
            )->willReturnCallback(static fn (object $event): object => $event);
        } else {
            // Notifications are event subscribers: no dispatch means no notification path.
            $dispatcher->expects(self::never())->method('dispatch');
        }
        $controller = new AppointmentRescheduleController($em);
        $controller->setContainer($container);
        $request = Request::create('/rendez-vous/reschedule', 'POST', server: [
            'HTTP_X_CSRF_TOKEN' => $case === 'csrf' ? 'invalid' : $token,
        ], content: json_encode(['id' => 42, 'startIsoTz' => $desired->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM)], JSON_THROW_ON_ERROR));
        $response = $controller($request, $repo, $slots, $settings, $dispatcher);
        self::assertSame($status, $response->getStatusCode(), $response->getContent());
        $json = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        if ($status !== 200) {
            self::assertSame($before, serialize($appointment), 'A refusal must not mutate any entity field.');
            self::assertNotEmpty($json['error']);
            if (str_starts_with($case, 'missing')) {
                self::assertSame('Les dates initiales du rendez-vous sont incomplètes. Veuillez contacter le support avant de le reporter.', $json['error']);
            }
        } else {
            self::assertTrue($json['ok']);
            self::assertSame($desired->getTimestamp(), $appointment->getStartAt()->getTimestamp());
            self::assertSame($desired->modify('+1 hour')->getTimestamp(), $appointment->getEndAt()->getTimestamp());
            self::assertSame('Europe/Paris', $appointment->getStartAt()->getTimezone()->getName());
            self::assertSame(AppointmentStatus::CONFIRMED, $appointment->getStatus());
        }
    }
}
