<?php

namespace App\Tests\Slots;

use App\Entity\ScheduleSetting;
use App\Repository\AppointmentRepository;
use App\Repository\ScheduleSettingRepository;
use App\Repository\UnavailableDayRepository;
use App\Repository\UnavailabilityRepository;
use App\Service\ScheduleSettingService;
use App\Service\SlotService;
use App\Tests\Payment\PaymentTestCase;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\Persistence\ManagerRegistry;

final class SlotSchemaTest extends PaymentTestCase
{
    private function service(): SlotService
    {
        // PaymentTestCase guards its hard-coded SQLite memory connection before writes.
        $this->appointment->getType()->setDuration(75);
        $this->em->remove($this->appointment);
        foreach (['open_days' => '1,2,3,4,5', 'opening_delay_hours' => '48',
            'morning_start' => '09:00', 'morning_end' => '12:00',
            'afternoon_start' => '14:00', 'afternoon_end' => '18:00',
            'slot_buffer_minutes' => '15', 'fixed_slots' => '1'] as $key => $value) {
            $this->em->persist((new ScheduleSetting())->setSettingKey($key)->setValue($value));
        }
        $this->em->flush();
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);
        return new SlotService(new AppointmentRepository($registry), new UnavailableDayRepository($registry),
            new UnavailabilityRepository($registry), new ScheduleSettingService(new ScheduleSettingRepository($registry)));
    }

    public function testCurrentSchemaAllowsSlotsWithTheObservedPlanningRules(): void
    {
        $service = $this->service();
        $monday = new \DateTimeImmutable('next monday +14 days', new \DateTimeZone('Europe/Paris'));
        for ($day = 0; $day < 5; ++$day) {
            $slots = $service->getAvailableSlots($this->appointment->getType(), $monday->modify('+'.$day.' days'));
            self::assertSame(['09:00', '10:30', '14:00', '15:30'], array_map(fn ($s) => $s['start']->format('H:i'), $slots));
            foreach ($slots as $slot) {
                self::assertSame(75 * 60, $slot['end']->getTimestamp() - $slot['start']->getTimestamp());
            }
        }
    }

    public function testMissingPaymentColumnBreaksAvailabilityEvenWithoutReservations(): void
    {
        $service = $this->service();
        $db = $this->em->getConnection();
        $db->executeStatement('DROP INDEX uniq_appointment_payment_intent');
        $db->executeStatement('ALTER TABLE appointment DROP COLUMN payment_intent_id');
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('payment_intent_id');
        $service->getAvailableSlots($this->appointment->getType(), new \DateTimeImmutable('next monday +14 days', new \DateTimeZone('Europe/Paris')));
    }
}
