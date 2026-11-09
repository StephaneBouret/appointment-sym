<?php

namespace App\Tests\AdminUser;

use App\Controller\Admin\UserCrudController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Templating\Helper\PhoneNumberHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserCrudHooksTest extends TestCase
{
    public static function usersAndHooks(): iterable
    {
        foreach (['persistEntity', 'updateEntity'] as $hook) {
            yield $hook.' basic' => [$hook, 'alice', 'martin', [], 'Alice', 'MARTIN'];
            yield $hook.' admin' => [$hook, 'mArIe', 'duPont', ['ROLE_ADMIN'], 'MArIe', 'DUPONT'];
            yield $hook.' accented guest' => [$hook, 'éLODIE', 'dupré', ['ROLE_GUEST', 'ROLE_USER'], 'éLODIE', 'DUPRé'];
        }
    }

    #[DataProvider('usersAndHooks')]
    public function testHooksKeepCasingRolesAndSingleOrderedPersistence(string $hook, string $firstname, string $lastname, array $roles, string $expectedFirstname, string $expectedLastname): void
    {
        $controller = new UserCrudController(new PhoneNumberHelper(PhoneNumberUtil::getInstance()));
        $user = (new User())->setFirstname($firstname)->setLastname($lastname)->setRoles($roles);
        $effectiveRoles = $user->getRoles();
        $storedRoles = new \ReflectionProperty(User::class, 'roles');
        $calls = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::identicalTo($user))
            ->willReturnCallback(function (object $entity) use (&$calls, $expectedFirstname, $expectedLastname, $effectiveRoles, $storedRoles, $roles): void {
                self::assertSame([], $calls);
                self::assertSame($expectedFirstname, $entity->getFirstname());
                self::assertSame($expectedLastname, $entity->getLastname());
                self::assertSame($effectiveRoles, $entity->getRoles());
                // getRoles already adds ROLE_USER; the hook must not rewrite the stored roles.
                self::assertSame($roles, $storedRoles->getValue($entity));
                $calls[] = 'persist';
            });
        $em->expects(self::once())->method('flush')->willReturnCallback(function () use (&$calls): void {
            self::assertSame(['persist'], $calls);
            $calls[] = 'flush';
        });
        $controller->$hook($em, $user);
        self::assertSame(['persist', 'flush'], $calls);
    }
}
