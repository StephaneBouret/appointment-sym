<?php

namespace App\Tests\Prestations;

use App\Entity\AppointmentType;
use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use libphonenumber\PhoneNumberUtil;

final class HomeFixtures
{
    public static function create(EntityManagerInterface $em, string $image = 'synthetic-image.webp'): AppointmentType
    {
        $params = $em->getConnection()->getParams();
        // Fail closed BEFORE schema creation or any data write, including browser previews.
        if (($params['driver'] ?? null) !== 'pdo_sqlite' || !($params['memory'] ?? false) || !empty($params['path'])) {
            throw new \LogicException('Homepage fixtures require a separate, in-memory SQLite database.');
        }
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $company = (new Company())->setName('Entreprise synthétique')->setSlug('synthetic')
            ->setAdress('1 rue de Test')->setPostalCode('75001')->setCity('Paris')
            ->setEmail('company@example.test')->setPhone(PhoneNumberUtil::getInstance()->parse('+33100000000'))
            ->setType(Company::TYPE_SAS)->setSiren('000000000')->setUrl('https://example.test')->setManager('Test');
        $em->persist($company);
        $type = (new AppointmentType())->setName('CAP SUR VOTRE IDENTITÉ')->setSlug('synthetic-cap')
            ->setDuration(90)->setPrice(15000)->setMinAge(18)->setParticipants(1)
            ->setIntroduction('Introduction synthétique réservée à la fiche.')
            ->setDescription('<p>Description synthétique réservée à la fiche.</p>');
        $type->setImageName($image);
        $em->persist($type);
        $em->flush();
        $em->persist((new AppointmentType())->setName('Autre séance synthétique')->setSlug('other')
            ->setDuration(45)->setPrice(7000));
        $em->flush();
        return $type;
    }
}
