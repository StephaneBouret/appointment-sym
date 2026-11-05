<?php

namespace App\Tests\Regression;

use App\Enum\AppointmentStatus;
use App\Tests\Prestations\CaptureAuthMailer;
use PHPUnit\Framework\Attributes\DataProvider;

final class AppointmentPdfAccessTest extends RegressionWebTestCase
{
    private function adminUrl(): string { return '/rendez-vous/admin/pdf/'.$this->appointment->getId(); }
    private function customerUrl(): string { return '/rendez-vous/pdf/'.$this->appointment->getId(); }

    public function testAnonymousMustLoginBeforeAdminInvoice(): void
    {
        $this->client->request('GET', $this->adminUrl());
        $this->assertRedirectTo('/login');
        $this->assertNoInvoice();
        $page = $this->client->followRedirect();
        self::assertSame(1, $page->filter('#login_form')->count());
    }

    public static function clients(): iterable
    {
        yield 'owner' => ['owner'];
        yield 'other client' => ['other'];
    }

    #[DataProvider('clients')]
    public function testClientsCannotReadAdminInvoice(string $actor): void
    {
        $this->client->loginUser($this->$actor);
        $this->client->request('GET', $this->adminUrl());
        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        $this->assertNoInvoice();
    }

    public function testAdminMustCompleteRealTwoFactorChallengeBeforeInvoice(): void
    {
        $this->client->request('GET', $this->adminUrl());
        $this->assertRedirectTo('/login');
        $page = $this->client->followRedirect();
        $this->client->submit($page->filter('#login_form')->form([
            '_username' => $this->admin->getEmail(), '_password' => self::PASSWORD,
        ]));
        // Follow only known local authentication redirects until the real 2FA form.
        for ($i = 0; $i < 4 && $this->client->getResponse()->isRedirect(); ++$i) {
            self::assertContains($this->client->getResponse()->headers->get('Location'), [
                $this->adminUrl(), 'http://localhost'.$this->adminUrl(), '/2fa', 'http://localhost/2fa',
            ]);
            $page = $this->client->followRedirect();
        }
        self::assertSame(1, $page->filter('input[name="_auth_code"]')->count());
        $this->assertNoInvoice();
        // A user with ROLE_ADMIN in a partial token still has no PDF access.
        $this->client->request('GET', $this->adminUrl());
        $this->assertRedirectTo('/2fa');
        $this->assertNoInvoice();
        $page = $this->client->followRedirect();
        self::assertArrayHasKey($this->admin->getEmail(), CaptureAuthMailer::$codes);
        $this->client->submit($page->filter('form')->form([
            '_auth_code' => CaptureAuthMailer::$codes[$this->admin->getEmail()],
        ]));
        $this->assertRedirectTo($this->adminUrl());
        $this->client->followRedirect();
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));
        self::assertSame(1, InvoiceProbe::$renders);
        self::assertSame(1, InvoiceProbe::$pdfCalls);
    }

    public function testConfirmedOwnerCanReadCustomerInvoice(): void
    {
        $this->client->loginUser($this->owner);
        $this->client->request('GET', $this->customerUrl());
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame(1, InvoiceProbe::$renders);
        self::assertSame(1, InvoiceProbe::$pdfCalls);
    }

    public function testOtherClientCannotReadCustomerInvoice(): void
    {
        $this->client->loginUser($this->other);
        $this->client->request('GET', $this->customerUrl());
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
        $this->assertNoInvoice();
    }

    public static function unconfirmedStatuses(): iterable
    {
        yield 'pending' => [AppointmentStatus::PENDING];
        yield 'canceled' => [AppointmentStatus::CANCELED];
    }

    #[DataProvider('unconfirmedStatuses')]
    public function testOwnerCannotReadUnconfirmedCustomerInvoice(AppointmentStatus $status): void
    {
        $this->appointment->setStatus($status);
        $this->em->flush();
        $this->client->loginUser($this->owner);
        $this->client->request('GET', $this->customerUrl());
        self::assertSame(404, $this->client->getResponse()->getStatusCode());
        $this->assertNoInvoice();
    }
}
