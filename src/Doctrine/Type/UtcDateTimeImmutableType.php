<?php

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;

/** Appointment times use timezone-free SQL DATETIME columns containing UTC. */
final class UtcDateTimeImmutableType extends DateTimeImmutableType
{
    public const NAME = 'utc_datetime_immutable';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = $value->setTimezone(new \DateTimeZone('UTC'));
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTimeImmutable
    {
        $utc = new \DateTimeZone('UTC');
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeImmutable) {
            return $value->setTimezone($utc);
        }

        $date = \DateTimeImmutable::createFromFormat('!'.$platform->getDateTimeFormatString(), $value, $utc);
        if ($date !== false) {
            return $date;
        }
        try {
            return (new \DateTimeImmutable($value, $utc))->setTimezone($utc);
        } catch (\Exception $exception) {
            throw InvalidFormat::new($value, self::class, $platform->getDateTimeFormatString(), $exception);
        }
    }
}
