<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Service\AvatarService;
use libphonenumber\PhoneNumberUtil;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(protected UserPasswordHasherInterface $passwordHasher, protected AvatarService $avatarService) {}

    public static function getGroups(): array
    {
        return ['user'];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $phoneNumberUtil = PhoneNumberUtil::getInstance(); // Instance de PhoneNumberUtil

        $admin = new User();
        $hash = $this->passwordHasher->hashPassword($admin, "password");

        // Générer un numéro de téléphone pour l'admin
        $adminRawPhoneNumber = $faker->mobileNumber();
        $adminPhoneNumberObject = $phoneNumberUtil->parse($adminRawPhoneNumber, 'FR');

        $admin->setEmail("admin@gmail.com")
            ->setFirstname("Admin")
            ->setLastname("Admin")
            ->setRoles(['ROLE_ADMIN'])
            ->setAdress($faker->streetAddress())
            ->setPostalCode($faker->postcode())
            ->setCity($faker->city)
            ->setPhone($adminPhoneNumberObject)
            ->setPassword($hash);

        $manager->persist($admin);
        $this->avatarService->createAndAssignAvatar($admin);

        $users = [];
        for ($u = 0; $u < 5; $u++) {
            $user = new User();
            $hash = $this->passwordHasher->hashPassword($user, "password");

            // Générer un numéro de téléphone différent pour chaque utilisateur
            $rawPhoneNumber = $faker->mobileNumber();
            $phoneNumberObject = $phoneNumberUtil->parse($rawPhoneNumber, 'FR');

            $user->setEmail("user$u@gmail.com")
                ->setFirstname($faker->firstName())
                ->setLastname($faker->lastName())
                ->setAdress($faker->streetAddress())
                ->setPostalCode($faker->postcode())
                ->setCity($faker->city)
                ->setPhone($phoneNumberObject)
                ->setPassword($hash);

            $manager->persist($user);
            $this->avatarService->createAndAssignAvatar($user);
            $users[] = $user;
        }

        $manager->flush();
    }
}
