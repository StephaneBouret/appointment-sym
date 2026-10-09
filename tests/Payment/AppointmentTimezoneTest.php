<?php

namespace App\Tests\Payment;

use App\Doctrine\Listener\NormalizeAppointmentTimezoneListener;
use Doctrine\ORM\Events;
use PHPUnit\Framework\Attributes\DataProvider;

final class AppointmentTimezoneTest extends PaymentTestCase
{
    private string $previousTimezone;

    protected function setUp(): void
    {
        $this->previousTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');
        parent::setUp();
        $this->em->getEventManager()->addEventListener([Events::prePersist, Events::preUpdate], new NormalizeAppointmentTimezoneListener());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        date_default_timezone_set($this->previousTimezone);
    }

    public static function seasons(): iterable
    {
        yield 'October CEST' => ['2026-10-12', '07:00:00'];
        yield 'winter CET' => ['2027-01-12', '08:00:00'];
    }

    #[DataProvider('seasons')]
    public function testPaymentRefreshesPreserveAppointmentInstant(string $day, string $utcTime): void
    {
        $start = new \DateTimeImmutable($day.' 09:00:00', new \DateTimeZone('Europe/Paris'));
        $this->appointment->getType()->setPrice(9000)->setDuration(75);
        $this->appointment->setStartAt($start)->setEndAt($start->modify('+75 minutes'));
        $this->em->flush();
        $trace = [];
        $record = function (string $step) use (&$trace): void {
            $trace[$step] = $this->em->getConnection()->fetchOne('SELECT start_at FROM appointment WHERE id = ?', [$this->appointment->getId()]);
        };
        $record('initial');
        self::assertTrue($this->payments->acceptTerms($this->appointment));
        $record('consent');
        $intent = $this->intent(overrides: ['amount' => 9000, 'amount_received' => 9000]);
        $this->stripe->method('createAppointmentIntent')->willReturn($intent);
        $this->payments->getOrCreateIntent($this->appointment);
        $record('intent');
        self::assertSame('confirmed', $this->payments->confirmWebhook($intent));
        $record('confirmation');
        $this->notifications->deliver($this->appointment);
        $record('notification');
        self::assertSame(1, $this->mailCount());
        self::assertSame(array_fill_keys(array_keys($trace), $day.' '.$utcTime), $trace);
        $this->em->refresh($this->appointment);
        self::assertSame($start->getTimestamp(), $this->appointment->getStartAt()->getTimestamp());
        self::assertSame($start->getTimestamp() + 4500, $this->appointment->getEndAt()->getTimestamp());
        self::assertSame('UTC', $this->appointment->getStartAt()->getTimezone()->getName());
        $this->assertSlotsAndDisplays($day);
    }

    private function assertSlotsAndDisplays(string $day): void
    {
        $paris = new \DateTimeZone('Europe/Paris');
        $start = new \DateTimeImmutable($day.' 09:00:00', $paris);
        $registry = $this->createStub(\Doctrine\Persistence\ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);
        $repo = new \App\Repository\AppointmentRepository($registry);
        self::assertTrue($repo->hasOverlap($start->setTimezone(new \DateTimeZone('UTC')), $start->modify('+75 minutes')->setTimezone(new \DateTimeZone('UTC'))));
        self::assertFalse($repo->hasOverlap($start->modify('+75 minutes')->setTimezone(new \DateTimeZone('UTC')), $start->modify('+150 minutes')->setTimezone(new \DateTimeZone('UTC'))));
        $rows = $repo->findByDate($start);
        self::assertCount(1, $rows);
        $settingsRepo = $this->createStub(\App\Repository\ScheduleSettingRepository::class);
        $settingsRepo->method('findOneBy')->willReturn(null);
        $slots = new \App\Service\SlotService($repo,
            $this->createStub(\App\Repository\UnavailableDayRepository::class),
            $this->createStub(\App\Repository\UnavailabilityRepository::class),
            new \App\Service\ScheduleSettingService($settingsRepo));
        // Exercise actual overlap generation, independently of today's opening barrier.
        $generate = new \ReflectionMethod($slots, 'generateSlots');
        $available = $generate->invoke($slots, $this->appointment->getType(), $start, '09:00', '12:00', $rows, []);
        self::assertNotEmpty($available);
        foreach ($available as $slot) {
            self::assertGreaterThanOrEqual($start->modify('+75 minutes')->getTimestamp(), $slot['start']->getTimestamp());
        }
        self::assertSame('10:15', $available[0]['start']->format('H:i'));

        $twig = new \Twig\Environment(new \Twig\Loader\ChainLoader([
            new \Twig\Loader\ArrayLoader(['base.html.twig' => '{% block body %}{% endblock %}']),
            new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2).'/templates'),
        ]));
        $twig->addExtension(new \Twig\Extra\Intl\IntlExtension());
        $twig->addExtension(new \App\Twig\AmountExtension());
        $twig->addFilter(new \Twig\TwigFilter('phone_number_format', static fn () => ''));
        foreach (['path','url','asset','absolute_url','is_granted','csrf_token','form_start','form_end','form_errors','form_widget','vich_uploader_asset'] as $function) {
            $twig->addFunction(new \Twig\TwigFunction($function, static fn () => ''));
        }
        // Explicit display zones must also work with a UTC Twig default (as on 8097).
        $twig->getExtension(\Twig\Extension\CoreExtension::class)->setTimezone('UTC');
        $context = ['appointment' => $this->appointment, 'appointments' => [$this->appointment],
            'user' => $this->appointment->getUser(), 'type' => $this->appointment->getType(),
            'company' => null, 'tz' => 'Europe/Paris', 'startAt' => $this->appointment->getStartAt(),
            'endAt' => $this->appointment->getEndAt(), 'confirmationForm' => ['agreeTerms' => null]];
        foreach (['appointment/checkout.html.twig', 'appointment/payment.html.twig', 'appointment/list.html.twig', 'appointment/pdf.html.twig',
            'emails/appointment_success_user.html.twig', 'emails/appointment_success_admin.html.twig',
            'emails/appointment_reminder.html.twig', 'emails/appointment_reschedule_user.html.twig',
            'emails/appointment_confirmation.html.twig'] as $template) {
            $html = $twig->render($template, $context);
            self::assertStringContainsString('09:00', $html, $template);
            self::assertStringContainsString('10:15', $html, $template);
            if ($template === 'appointment/checkout.html.twig') {
                self::assertStringContainsString($start->format('d/m/Y').' 09:00', $html);
            }
        }
        $form = \Symfony\Component\Form\Forms::createFormFactory()->create(\Symfony\Component\Form\Extension\Core\Type\DateTimeType::class,
            $this->appointment->getStartAt(), ['widget' => 'single_text', 'input' => 'datetime_immutable', 'model_timezone' => 'UTC', 'view_timezone' => 'Europe/Paris']);
        self::assertSame($day.'T09:00', $form->getViewData());
        $form->submit($day.'T09:00');
        self::assertSame($start->getTimestamp(), $form->getData()->getTimestamp());

        // Use the actual admin configuration (custom DBAL types are not auto-detected by EasyAdmin).
        $admin = (new \ReflectionClass(\App\Controller\Admin\AppointmentCrudController::class))->newInstanceWithoutConstructor();
        $crud = $admin->configureCrud(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::new())->getAsDto();
        self::assertSame('Europe/Paris', $crud->getTimezone());
        $formatter = new \EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter();
        self::assertStringContainsString('09:00', $formatter->formatDateTime($this->appointment->getStartAt(), 'long', 'short', '', $crud->getTimezone(), locale: 'fr'));
        foreach ($admin->configureFields('index') as $field) {
            $dto = $field->getAsDto();
            if ($dto->getProperty() !== 'startAt') { continue; }
            $options = array_intersect_key($dto->getFormTypeOptions(), array_flip(['input','widget','model_timezone','view_timezone']));
            $adminForm = \Symfony\Component\Form\Forms::createFormFactory()->create(\Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, $this->appointment->getStartAt(), $options);
            self::assertSame($day.'T09:00', $adminForm->getViewData());
            $adminForm->submit($day.'T09:00');
            self::assertInstanceOf(\DateTimeImmutable::class, $adminForm->getData());
            self::assertSame($start->getTimestamp(), $adminForm->getData()->getTimestamp());
            break;
        }
    }
}
