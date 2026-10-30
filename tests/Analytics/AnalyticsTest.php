<?php

namespace App\Tests\Analytics;

use App\Analytics\AnalyticsConfig;
use App\Entity\User;
use App\Tests\Prestations\HomeFixtures;
use App\Tests\Prestations\HomeTestKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AnalyticsTest extends TestCase
{
    public function testConfigurationRequiresAnExplicitSwitchAndValidId(): void
    {
        $router = $this->createStub(UrlGeneratorInterface::class);
        foreach ([['0', 'G-TEST123456'], ['1', ''], ['1', 'invalid'], ['yes', 'G-TEST123456'], ['1', "G-TEST123456\n"]] as [$enabled, $id]) {
            self::assertFalse((new AnalyticsConfig($router, $enabled, $id))->isEnabled());
        }
        self::assertTrue((new AnalyticsConfig($router, '1', 'G-TEST123456'))->isEnabled());
    }

    public function testOnlyNamedPublicRoutesAndExplicitParametersAreExported(): void
    {
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(fn ($route, $params = []) => '/'.$route.(isset($params['id']) ? '/'.$params['id'] : ''));
        $config = new AnalyticsConfig($router, '1', 'G-TEST123456');
        foreach (['app_login', 'app_register', 'app_profile', 'app_appointment_ajax_form', 'app_reset_password', 'admin', 'unknown'] as $route) {
            self::assertNull($config->page(new Request(attributes: ['_route' => $route, 'token' => 'SECRET'])));
        }
        foreach (['../../reset/SECRET', '1?email=secret', '01', '0', '99999999999'] as $id) {
            self::assertNull($config->page(new Request(attributes: ['_route' => 'app_appointment_type_detail', 'id' => $id])));
        }
        $request = Request::create('/reset/SECRET?email=private@example.test');
        $request->attributes->add(['_route' => 'app_appointment_type_detail', 'id' => '12']);
        self::assertSame(['path' => '/app_appointment_type_detail/12', 'title' => 'Fiche prestation'], $config->page($request));
        $request->setMethod('POST');
        self::assertNull($config->page($request));
        self::assertNull($config->page(new Request(attributes: ['_route' => 'seo_local_page', 'slug' => 'secret'])));
    }

    public function testRenderedPublicPrivateAndAuthenticatedReservationBoundaries(): void
    {
        require_once dirname(__DIR__).'/Prestations/bootstrap.php';
        $_SERVER['GA4_ENABLED'] = $_ENV['GA4_ENABLED'] = '1';
        $_SERVER['GA4_MEASUREMENT_ID'] = $_ENV['GA4_MEASUREMENT_ID'] = 'G-TEST123456';
        $kernel = new HomeTestKernel('test', true);
        $kernel->boot();
        $em = $kernel->getContainer()->get('test.service_container')->get('doctrine')->getManager();
        try {
            HomeFixtures::create($em);
            $client = new KernelBrowser($kernel);
            $client->disableReboot();
            foreach (['/', '/rendez-vous/types', '/rendez-vous/types/1', '/politique-de-confidentialite'] as $path) {
                $page = $client->request('GET', $path.'?token=NEVER_SEND#fragment');
                self::assertSame(200, $client->getResponse()->getStatusCode());
                $data = json_decode($page->filter('[data-analytics-config]')->attr('data-analytics-config'), true);
                self::assertSame($path, $data['page']['path']);
                self::assertStringNotContainsString('NEVER_SEND', json_encode($data));
                self::assertSame(0, $page->filter('script[src*="googletagmanager"]')->count());
            }
            $page = $client->request('GET', '/login?token=PRIVATE');
            self::assertSame('reload', $page->filter('meta[name="turbo-visit-control"]')->attr('content'));
            self::assertNull(json_decode($page->filter('[data-analytics-config]')->attr('data-analytics-config'), true)['page']);
            $client->request('GET', '/audience/frame');
            self::assertSame('no-referrer', $client->getResponse()->headers->get('Referrer-Policy'));
            $client->request('GET', '/audience/frame?token=PRIVATE');
            self::assertSame(404, $client->getResponse()->getStatusCode());
            foreach ([false, true] as $authenticated) {
                if ($authenticated) {
                    $user = (new User())->setEmail('synthetic@example.test')->setFirstname('Test')->setLastname('Test')->setPassword('unused')->setRoles(['ROLE_USER'])
                        ->setAdress('1 rue de test')->setPostalCode('75001')->setCity('Paris')
                        ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'));
                    $avatar = new \App\Entity\Avatar($user);
                    $avatar->setImageName('synthetic-avatar.png');
                    $user->setAvatar($avatar);
                    $em->persist($user);
                    $em->flush();
                    $client->loginUser($user);
                }
                $page = $client->request('GET', '/rendez-vous/types/1');
                $link = $page->selectLink('Réserver');
                self::assertSame('reserve_click', $link->attr('data-analytics-event'));
                self::assertSame('false', $link->attr('data-turbo'));
                self::assertNull($link->attr('data-action'));
                self::assertSame('/rendez-vous/type/1/form', $link->attr('href'));
            }
        } finally {
            $em->getConnection()->close();
            $kernel->shutdown();
            unset($_SERVER['GA4_ENABLED'], $_ENV['GA4_ENABLED'], $_SERVER['GA4_MEASUREMENT_ID'], $_ENV['GA4_MEASUREMENT_ID']);
        }
    }
}
