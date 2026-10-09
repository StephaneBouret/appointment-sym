<?php

namespace App\Tests\Payment;

use App\Doctrine\Type\UtcDateTimeImmutableType;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use PHPUnit\Framework\TestCase;

final class UtcDateTimeTypeTest extends TestCase
{
    public function testMysqlDeclarationUnchangedAndConversionsIndependentOfPhpTimezone(): void
    {
        $platform = new MySQL84Platform();
        $type = new UtcDateTimeImmutableType();
        self::assertSame((new DateTimeImmutableType())->getSQLDeclaration([], $platform), $type->getSQLDeclaration([], $platform));
        self::assertNull($type->convertToPHPValue(null, $platform));
        self::assertNull($type->convertToDatabaseValue(null, $platform));
        $previous = date_default_timezone_get();
        try {
            foreach (['UTC', 'Europe/Paris', 'America/New_York'] as $zone) {
                date_default_timezone_set($zone);
                foreach (['2026-10-12' => '07:00:00', '2027-01-12' => '08:00:00'] as $day => $utcTime) {
                    $paris = new \DateTimeImmutable($day.' 09:00:00', new \DateTimeZone('Europe/Paris'));
                    $raw = $type->convertToDatabaseValue($paris, $platform);
                    self::assertSame($day.' '.$utcTime, $raw);
                    self::assertSame($paris->getTimestamp(), $type->convertToPHPValue($raw, $platform)->getTimestamp());
                    self::assertSame('UTC', $type->convertToPHPValue($paris, $platform)->getTimezone()->getName());
                    self::assertSame('09:00', $paris->format('H:i')); // immutable input not changed
                }
            }
        } finally { date_default_timezone_set($previous); }
    }

    public function testMutableInputIsRejected(): void
    {
        $this->expectException(InvalidType::class);
        (new UtcDateTimeImmutableType())->convertToDatabaseValue(new \DateTime(), new MySQL84Platform());
    }

    public function testMalformedStorageIsRejected(): void
    {
        $this->expectException(InvalidFormat::class);
        (new UtcDateTimeImmutableType())->convertToPHPValue('invalid-date', new MySQL84Platform());
    }
}
