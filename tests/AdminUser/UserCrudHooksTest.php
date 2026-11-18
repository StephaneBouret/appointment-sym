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
    private UserCrudController $controller;

    protected function setUp(): void
    {
        $this->controller = new UserCrudController(new PhoneNumberHelper(PhoneNumberUtil::getInstance()));
        UserCrudWriteObserver::$calls = [];
    }

    public static function usersAndHooks(): iterable
    {
        foreach (['persistEntity', 'updateEntity'] as $hook) {
            yield $hook.' basic' => [$hook, 'alice', 'martin', [], 'Alice', 'MARTIN'];
            yield $hook.' admin' => [$hook, 'mArIe', 'duPont', ['ROLE_ADMIN'], 'MArIe', 'DUPONT'];
            yield $hook.' accented guest' => [$hook, 'éLODIE', 'dupré', ['ROLE_GUEST', 'ROLE_USER'], 'éLODIE', 'DUPRé'];
            yield $hook.' surrounding spaces' => [$hook, ' alice ', ' duPont ', [], ' alice ', ' DUPONT '];
        }
    }

    #[DataProvider('usersAndHooks')]
    public function testHooksKeepCasingRolesAndSingleOrderedPersistence(string $hook, string $firstname, string $lastname, array $roles, string $expectedFirstname, string $expectedLastname): void
    {
        $user = (new User())->setFirstname($firstname)->setLastname($lastname)->setRoles($roles);
        $effectiveRoles = $user->getRoles();
        $storedRoles = new \ReflectionProperty(User::class, 'roles');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::identicalTo($user))
            ->willReturnCallback(function (object $entity) use ($expectedFirstname, $expectedLastname, $effectiveRoles, $storedRoles, $roles): void {
                self::assertSame($expectedFirstname, $entity->getFirstname());
                self::assertSame($expectedLastname, $entity->getLastname());
                self::assertSame($effectiveRoles, $entity->getRoles());
                // getRoles already adds ROLE_USER; the hook must not rewrite the stored roles.
                self::assertSame($roles, $storedRoles->getValue($entity));
            });
        $em->expects(self::once())->method('flush');
        $this->controller->$hook(new UserCrudWriteObserver($em), $user);
        self::assertSame(['persist', 'flush'], UserCrudWriteObserver::$calls);
    }

    public static function incompleteNamesAndHooks(): iterable
    {
        foreach (['persistEntity', 'updateEntity'] as $hook) {
            foreach (['null' => null, 'empty' => '', 'spaces' => '   '] as $case => $value) {
                yield "$hook firstname $case" => [$hook, $value, 'duPont', 'firstname'];
                yield "$hook lastname $case" => [$hook, 'mArIe', $value, 'lastname'];
            }
            yield "$hook both null" => [$hook, null, null, 'firstname'];
        }
    }

    #[DataProvider('incompleteNamesAndHooks')]
    public function testIncompleteNamesAreRejectedBeforeMutationOrWrite(string $hook, ?string $firstname, ?string $lastname, string $field): void
    {
        $user = (new User())->setRoles(['ROLE_GUEST']);
        if ($firstname !== null) {
            $user->setFirstname($firstname);
        }
        if ($lastname !== null) {
            $user->setLastname($lastname);
        }
        $storedRoles = new \ReflectionProperty(User::class, 'roles');
        $rolesBefore = $storedRoles->getValue($user);
        $effectiveRolesBefore = $user->getRoles();
        $observer = new UserCrudWriteObserver($this->createStub(EntityManagerInterface::class));
        $deprecations = [];
        set_error_handler(static function (int $severity, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);
        try {
            $this->controller->$hook($observer, $user);
            self::fail('Expected the hook to reject '.$field);
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString($field, $exception->getMessage());
        } finally {
            restore_error_handler();
        }
        self::assertSame([], $deprecations);
        self::assertSame($firstname, $user->getFirstname());
        self::assertSame($lastname, $user->getLastname());
        self::assertSame($rolesBefore, $storedRoles->getValue($user));
        self::assertSame($effectiveRolesBefore, $user->getRoles());
        self::assertSame([], UserCrudWriteObserver::$calls);
    }
}
