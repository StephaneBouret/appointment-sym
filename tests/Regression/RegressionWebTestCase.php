<?php

namespace App\Tests\Regression;

use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Entity\Avatar;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Tests\Prestations\CaptureAuthMailer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class RegressionWebTestCase extends TestCase
{
    protected RegressionKernel $kernel;
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;
    protected User $owner;
    protected User $other;
    protected User $admin;
    protected Appointment $appointment;
    protected const PASSWORD = 'Synthetic-Only-8z!V4q#L2';

    protected function setUp(): void
    {
        $this->kernel = new RegressionKernel('test', true);
        $this->kernel->boot();
        $container = $this->kernel->getContainer()->get('test.service_container');
        $this->em = $container->get('doctrine')->getManager();
        $params = $this->em->getConnection()->getParams();
        // Verify the destination BEFORE any schema or fixture write.
        self::assertSame('pdo_sqlite', $params['driver']);
        self::assertTrue($params['memory'] ?? false);
        self::assertEmpty($params['path'] ?? null);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->owner = $this->newUser('owner@example.test');
        $this->other = $this->newUser('other@example.test');
        $this->admin = $this->newUser('admin@example.test', ['ROLE_ADMIN']);
        $type = (new AppointmentType())->setName('Synthetic appointment')->setSlug('synthetic')
            ->setPrice(15000)->setDuration(60);
        $this->appointment = (new Appointment())->setUser($this->owner)->setType($type)
            ->setStatus(AppointmentStatus::CONFIRMED)
            ->setStartAt(new \DateTimeImmutable('+14 days'))->setEndAt(new \DateTimeImmutable('+14 days +1 hour'));
        $this->em->persist($type);
        $this->em->persist($this->appointment);
        $this->em->flush();
        $this->client = new KernelBrowser($this->kernel);
        $this->client->disableReboot();
        InvoiceProbe::$renders = InvoiceProbe::$pdfCalls = 0;
        CaptureAuthMailer::$codes = [];
    }

    private function newUser(string $email, array $roles = []): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles)->setFirstname('Camille')->setLastname('Synthetic')
            ->setAdress('1 rue de test')->setPostalCode('75001')->setCity('Paris')
            ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'));
        $avatar = new Avatar($user);
        $avatar->setImageName('synthetic-avatar.png');
        $user->setAvatar($avatar);
        $hasher = $this->kernel->getContainer()->get('test.service_container')->get('security.user_password_hasher');
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $this->em->persist($user);
        return $user;
    }

    protected function assertRedirectTo(string $path): void
    {
        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertContains($this->client->getResponse()->headers->get('Location'), [$path, 'http://localhost'.$path]);
    }

    protected function assertNoInvoice(): void
    {
        self::assertSame(0, InvoiceProbe::$renders, 'Denied access must not render the invoice template.');
        self::assertSame(0, InvoiceProbe::$pdfCalls, 'Denied access must not call the PDF generator.');
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
        $this->kernel->shutdown();
    }
}
