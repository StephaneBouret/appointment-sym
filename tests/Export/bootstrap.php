<?php
require_once dirname(__DIR__).'/AdminUser/bootstrap.php';
require_once __DIR__.'/ExportIoFaults.php';
if (!\Doctrine\DBAL\Types\Type::hasType(\App\Doctrine\Type\UtcDateTimeImmutableType::NAME)) {
    \Doctrine\DBAL\Types\Type::addType(\App\Doctrine\Type\UtcDateTimeImmutableType::NAME, \App\Doctrine\Type\UtcDateTimeImmutableType::class);
}
