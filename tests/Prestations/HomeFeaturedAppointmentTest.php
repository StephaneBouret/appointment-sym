<?php

namespace App\Tests\Prestations;

use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class HomeFeaturedAppointmentTest extends TestCase
{
    private HomeTestKernel $kernel;
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    private function start(string $mode = 'configured'): \App\Entity\AppointmentType
    {
        require_once __DIR__.'/bootstrap.php';
        $this->kernel = new HomeTestKernel('test', true, $mode);
        $this->kernel->boot();
        $this->em = $this->kernel->getContainer()->get('test.service_container')->get('doctrine')->getManager();
        $type = HomeFixtures::create($this->em);
        $this->client = new KernelBrowser($this->kernel);
        $this->client->disableReboot();
        return $type;
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            self::assertSame(0, $this->em->getRepository(Appointment::class)->count([]));
            $this->em->getConnection()->close();
        }
        if (isset($this->kernel)) {
            $this->kernel->shutdown();
        }
    }

    public function testConfiguredEntityAndPublicDestination(): void
    {
        $type = $this->start();
        $page = $this->client->request('GET', '/');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $block = $page->filter('#appointment');
        self::assertSame($type->getName(), $block->filter('h2')->text());
        self::assertStringContainsString('90 minutes', $block->text());
        self::assertStringContainsString('150,00', $block->text());
        self::assertSame('/images/appointments/synthetic-image.webp', $block->filter('img')->attr('src'));
        self::assertSame($type->getName(), $block->filter('img')->attr('alt'));
        self::assertStringNotContainsString('réservée à la fiche', $block->text());
        self::assertSame('/rendez-vous/types', $block->selectLink('Voir toutes les prestations')->attr('href'));
        $link = $block->selectLink('Découvrir la séance');
        self::assertSame('/rendez-vous/types/'.$type->getId(), $link->attr('href'));
        $detail = $this->client->click($link->link());
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame($type->getName(), $detail->filter('h1')->text());
    }

    public function testAdminDataChangesAreReflectedAndMissingImageHasNoBrokenTag(): void
    {
        $type = $this->start();
        $this->client->request('GET', '/');
        $type->setName('Nom administré modifié')->setDuration(105)->setPrice(18750)->setImageName(null);
        $this->em->flush();
        $this->em->clear();
        $block = $this->client->request('GET', '/')->filter('#appointment');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame('Nom administré modifié', $block->filter('h2')->text());
        self::assertStringContainsString('105 minutes', $block->text());
        self::assertStringContainsString('187,50', $block->text());
        self::assertStringNotContainsString('150,00', $block->text());
        self::assertSame(0, $block->filter('img')->count());
        self::assertSame(1, $block->filter('.col-12')->count());
    }

    #[DataProvider('fallbackModes')]
    public function testFallbackNeverSelectsAnArbitraryType(string $mode): void
    {
        $this->start($mode);
        if ($mode === 'absent') {
            self::assertFalse($this->kernel->getContainer()->hasParameter('app.home_featured_appointment_type_id'));
        }
        $page = $this->client->request('GET', '/');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame(0, $page->filter('#featured-appointment-title')->count());
        self::assertStringContainsString('Un accompagnement pour faire le point', $page->filter('#appointment')->text());
        self::assertSame(0, $page->selectLink('Découvrir la séance')->count());
        self::assertSame('/rendez-vous/types', $page->filter('#appointment')->selectLink('Prendre rendez-vous')->attr('href'));
    }

    public static function fallbackModes(): iterable
    {
        yield 'parameter removed' => ['absent'];
        yield 'entity not found' => ['missing'];
        yield 'parameter disabled' => ['disabled'];
    }

    public function testConfigurationCanSelectAnotherEntityWithoutCodeChanges(): void
    {
        $this->start('alternate');
        $block = $this->client->request('GET', '/')->filter('#appointment');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame('Autre séance synthétique', $block->filter('h2')->text());
        self::assertSame('/rendez-vous/types/2', $block->selectLink('Découvrir la séance')->attr('href'));
        self::assertStringContainsString('45 minutes', $block->text());
        self::assertStringContainsString('70,00', $block->text());
    }
}
