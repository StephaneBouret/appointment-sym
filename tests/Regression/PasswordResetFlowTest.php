<?php

namespace App\Tests\Regression;

use App\Entity\User;

final class PasswordResetFlowTest extends RegressionWebTestCase
{
    public function testMissingDateUsesExistingExpirationFlowWithoutChangingPassword(): void
    {
        $this->owner->setResetToken('missing-date')->setCreatedTokenAt(null);
        $this->em->flush();
        $password = $this->owner->getPassword();
        // POST data must never reach the password update when the date is absent.
        $this->client->request('POST', '/forgot-password/missing-date', [
            'reset_password_form' => ['plainPassword' => ['first' => 'Changed-9a!Example', 'second' => 'Changed-9a!Example']],
        ]);
        $this->assertRedirectTo('/forgot-password');
        $page = $this->client->followRedirect();
        self::assertStringContainsString('Votre demande de mot de passe a expiré', $page->text());
        $this->reloadOwner();
        self::assertSame($password, $this->owner->getPassword());
        self::assertSame('missing-date', $this->owner->getResetToken());
    }

    public function testConsumedTokenCannotBeUsedAgain(): void
    {
        $this->owner->setResetToken('consume-once')->setCreatedTokenAt(new \DateTimeImmutable());
        $this->em->flush();
        $oldPassword = $this->owner->getPassword();
        $page = $this->client->request('GET', '/forgot-password/consume-once');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->client->submit($page->filter('form')->form([
            'reset_password_form[plainPassword][first]' => 'Changed-9a!Example',
            'reset_password_form[plainPassword][second]' => 'Changed-9a!Example',
        ]));
        $this->assertRedirectTo('/login');
        $this->reloadOwner();
        $newPassword = $this->owner->getPassword();
        self::assertNotSame($oldPassword, $newPassword);
        self::assertNull($this->owner->getResetToken());
        self::assertNull($this->owner->getCreatedTokenAt());
        self::assertNull($this->em->getRepository(User::class)->findOneBy(['resetToken' => 'consume-once']));
        $this->client->request('POST', '/forgot-password/consume-once', [
            'reset_password_form' => ['plainPassword' => ['first' => 'Replay-7b!Example', 'second' => 'Replay-7b!Example']],
        ]);
        $this->assertRedirectTo('/login');
        $page = $this->client->followRedirect();
        self::assertStringContainsString('Jeton invalide', $page->text());
        $this->reloadOwner();
        self::assertSame($newPassword, $this->owner->getPassword());
    }

    private function reloadOwner(): void
    {
        // KernelBrowser resets Doctrine between requests, even with disableReboot().
        $id = $this->owner->getId();
        $this->em->clear();
        $this->owner = $this->em->find(User::class, $id);
    }
}
