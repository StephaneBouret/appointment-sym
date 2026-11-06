<?php

namespace App\Tests\Slots;

use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Entity\ScheduleSetting;
use App\Entity\Unavailability;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use App\Repository\ScheduleSettingRepository;
use App\Repository\UnavailabilityRepository;
use App\Repository\UnavailableDayRepository;
use App\Service\ScheduleSettingService;
use App\Service\SlotService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SlotCollectionsTest extends TestCase
{
    private \DateTimeImmutable $day;

    protected function setUp(): void
    {
        // Well beyond the real opening barrier; no production clock override.
        $this->day = (new \DateTimeImmutable('next monday +14 days', new \DateTimeZone('Europe/Paris')))->setTime(0, 0);
    }

    /**
     * @param list<Appointment> $appointments
     * @param list<Unavailability> $unavailabilities
     * @param array<string, string> $overrides
     */
    private function service(array $appointments = [], array $unavailabilities = [], array $overrides = [], ?string $closure = null): SlotService
    {
        $settings = array_replace([
            'open_days' => '1,2,3,4,5,6,7', 'opening_delay_hours' => '0',
            'morning_start' => '09:00', 'morning_end' => '12:00',
            'afternoon_start' => '14:00', 'afternoon_end' => '16:00',
            'slot_buffer_minutes' => '0', 'fixed_slots' => '0', 'slot_step_minutes' => '60',
        ], $overrides);
        $settingsRepo = $this->createStub(ScheduleSettingRepository::class);
        $settingsRepo->method('findOneBy')->willReturnCallback(static function (array $criteria) use ($settings): ?ScheduleSetting {
            $key = $criteria['setting_key'];
            return isset($settings[$key]) ? (new ScheduleSetting())->setSettingKey($key)->setValue($settings[$key]) : null;
        });
        $appointmentsRepo = $this->createMock(AppointmentRepository::class);
        $appointmentsRepo->expects($closure === null ? self::once() : self::never())
            ->method('findByDate')->willReturn($appointments);
        $daysRepo = $this->createStub(UnavailableDayRepository::class);
        $daysRepo->method('isUnavailable')->willReturn($closure === 'unavailable-day');
        $unavailabilityRepo = $this->createMock(UnavailabilityRepository::class);
        $unavailabilityRepo->expects($closure === 'unavailable-day' ? self::never() : self::once())
            ->method('hasAllDay')->willReturn($closure === 'all-day');
        $unavailabilityRepo->expects($closure === null ? self::once() : self::never())
            ->method('findForDate')->willReturn($unavailabilities);

        return new SlotService($appointmentsRepo, $daysRepo, $unavailabilityRepo, new ScheduleSettingService($settingsRepo));
    }

    private function appointment(AppointmentStatus $status, string $start = '10:00', string $end = '11:00'): Appointment
    {
        return (new Appointment())->setStatus($status)
            ->setStartAt($this->day->modify($start))->setEndAt($this->day->modify($end));
    }

    private function unavailability(string $start = '10:00', string $end = '11:00'): Unavailability
    {
        return (new Unavailability())->setAllDay(false)
            ->setStartAt($this->day->modify($start))->setEndAt($this->day->modify($end));
    }

    /** @return list<string> */
    private function starts(SlotService $service): array
    {
        return array_map(static fn (array $slot): string => $slot['start']->format('H:i'),
            $service->getAvailableSlots((new AppointmentType())->setDuration(60), $this->day));
    }

    public function testEmptyCollectionsPreserveMorningAfternoonAndImmutableDates(): void
    {
        $slots = $this->service()->getAvailableSlots((new AppointmentType())->setDuration(60), $this->day);
        self::assertSame(['09:00', '10:00', '11:00', '14:00', '15:00'],
            array_map(static fn (array $slot): string => $slot['start']->format('H:i'), $slots));
        foreach ($slots as $slot) {
            self::assertSame(['start', 'end'], array_keys($slot));
            self::assertInstanceOf(\DateTimeImmutable::class, $slot['start']);
            self::assertInstanceOf(\DateTimeImmutable::class, $slot['end']);
            self::assertSame(3600, $slot['end']->getTimestamp() - $slot['start']->getTimestamp());
            self::assertSame('Europe/Paris', $slot['start']->getTimezone()->getName());
        }
    }

    public static function statuses(): iterable
    {
        yield 'pending blocks' => [AppointmentStatus::PENDING, ['09:00', '11:00', '14:00', '15:00']];
        yield 'confirmed blocks' => [AppointmentStatus::CONFIRMED, ['09:00', '11:00', '14:00', '15:00']];
        yield 'canceled does not block' => [AppointmentStatus::CANCELED, ['09:00', '10:00', '11:00', '14:00', '15:00']];
    }

    #[DataProvider('statuses')]
    public function testOnlyBlockingStatusesRemoveSlots(AppointmentStatus $status, array $expected): void
    {
        self::assertSame($expected, $this->starts($this->service([$this->appointment($status)])));
    }

    public function testMixedListStillBlocksWhenFilteringLeavesKeysOneAndThree(): void
    {
        $appointments = [
            $this->appointment(AppointmentStatus::CANCELED, '09:00', '10:00'),
            $this->appointment(AppointmentStatus::PENDING, '10:00', '11:00'),
            $this->appointment(AppointmentStatus::CANCELED, '14:00', '15:00'),
            $this->appointment(AppointmentStatus::CONFIRMED, '15:00', '16:00'),
        ];
        self::assertSame(['09:00', '11:00', '14:00'], $this->starts($this->service($appointments)));
    }

    public static function periodKinds(): iterable
    {
        yield 'appointment' => [true];
        yield 'unavailability' => [false];
    }

    #[DataProvider('periodKinds')]
    public function testExactContactAtBothBoundsDoesNotOverlapWithZeroBuffer(bool $appointment): void
    {
        $service = $appointment
            ? $this->service([$this->appointment(AppointmentStatus::CONFIRMED)])
            : $this->service([], [$this->unavailability()]);
        self::assertSame(['09:00', '11:00', '14:00', '15:00'], $this->starts($service));
    }

    public function testTimedUnavailabilityCanOverlapPartsOfTwoSlots(): void
    {
        self::assertSame(['09:00', '14:00', '15:00'],
            $this->starts($this->service([], [$this->unavailability('10:30', '11:15')])));
    }

    #[DataProvider('periodKinds')]
    public function testBufferExpandsAppointmentsOnlyWithoutMutatingDates(bool $appointment): void
    {
        $period = $appointment ? $this->appointment(AppointmentStatus::CONFIRMED) : $this->unavailability();
        $start = $period->getStartAt();
        $end = $period->getEndAt();
        $service = $this->service($appointment ? [$period] : [], $appointment ? [] : [$period], ['slot_buffer_minutes' => '15']);
        self::assertSame($appointment ? ['14:00', '15:00'] : ['09:00', '11:00', '14:00', '15:00'], $this->starts($service));
        self::assertSame($start, $period->getStartAt());
        self::assertSame($end, $period->getEndAt());
    }

    public static function missingDates(): iterable
    {
        foreach ([true, false] as $appointment) {
            $kind = $appointment ? 'appointment' : 'unavailability';
            yield $kind.' missing start' => [$appointment, false, true];
            yield $kind.' missing end' => [$appointment, true, false];
            yield $kind.' missing both' => [$appointment, false, false];
        }
    }

    #[DataProvider('missingDates')]
    public function testIncompletePeriodsAreSkippedAndLaterPeriodsStillBlock(bool $appointment, bool $hasStart, bool $hasEnd): void
    {
        $incomplete = $appointment ? (new Appointment())->setStatus(AppointmentStatus::CONFIRMED) : new Unavailability();
        if ($hasStart) { $incomplete->setStartAt($this->day->setTime(0, 0)); }
        if ($hasEnd) { $incomplete->setEndAt($this->day->setTime(23, 59)); }
        $service = $appointment
            ? $this->service([$incomplete, $this->appointment(AppointmentStatus::PENDING)])
            : $this->service([], [$incomplete, $this->unavailability()]);
        self::assertSame(['09:00', '11:00', '14:00', '15:00'], $this->starts($service));
    }

    public static function closures(): iterable
    {
        yield 'closed weekday' => ['closed-weekday'];
        yield 'unavailable day' => ['unavailable-day'];
        yield 'all-day unavailability' => ['all-day'];
    }

    #[DataProvider('closures')]
    public function testClosuresReturnNoSlotsBeforeReadingPeriodCollections(string $closure): void
    {
        // The selected date is Monday; weekend-only opening exercises the closed-day guard.
        $overrides = $closure === 'closed-weekday' ? ['open_days' => '6,7'] : [];
        self::assertSame([], $this->starts($this->service(overrides: $overrides, closure: $closure)));
    }
}
