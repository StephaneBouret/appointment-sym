<?php

namespace App\Tests\PaymentBrowser;

require __DIR__.'/runtime.php';
ensure(!is_file(state().'/target.json'), 'Environment already prepared; refusing to overwrite or reuse another database');
foreach (['', '/mail', '/sessions', '/log'] as $part) { if (!is_dir(state().$part)) { mkdir(state().$part, 0700, true); } }
$db = \Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_mysql', 'host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'password' => '']);
$identity = $db->fetchAssociative('SELECT DATABASE() AS db, @@hostname AS server, @@server_uuid AS uuid, @@port AS port, VERSION() AS version, @@default_storage_engine AS engine');
ensure($identity['db'] === null && (int) $identity['port'] === 3306 && $identity['engine'] === 'InnoDB', 'Provisioning target invalid');
$database = 'appointment_payment_browser_'.gmdate('Ymd').'_'.bin2hex(random_bytes(6));
ensure((int) $db->fetchOne('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]) === 0, 'Database already exists');
echo json_encode($identity, JSON_THROW_ON_ERROR)."\nNew isolated database: ".$database."\n";
$db->executeStatement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->close();
saveState('target.json', ['database' => $database, 'uuid' => $identity['uuid'], 'session_key' => bin2hex(random_bytes(32))]);
$kernel = boot();
$container = $kernel->getContainer()->get('test.service_container');
$em = $container->get('doctrine')->getManager();
echo 'APPLICATION CONNECTION: '.json_encode(guard($em->getConnection()), JSON_THROW_ON_ERROR)."\n";
(new \Doctrine\ORM\Tools\SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
$accounts = [];
foreach (['alice', 'bob', 'admin'] as $name) {
    $password = 'Recette-'.bin2hex(random_bytes(8));
    $user = (new \App\Entity\User())->setEmail($name.'@example.test')->setFirstname(ucfirst($name))->setLastname('Fictif')
        ->setAdress('1 rue fictive')->setPostalCode('75001')->setCity('Paris')->setIsVerified(true)
        ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0600000000', 'FR'))
        ->setRoles($name === 'admin' ? ['ROLE_ADMIN'] : ['ROLE_USER']);
    $user->setPassword($container->get('security.user_password_hasher')->hashPassword($user, $password));
    $em->persist($user);
    $avatar = new \App\Entity\Avatar($user);
    $avatar->setImageName('local-fixture.svg');
    $em->persist($avatar);
    $accounts[$name] = ['email' => $user->getEmail(), 'password' => $password];
}
$type = (new \App\Entity\AppointmentType())->setName('Prestation fictive — recette paiement')->setSlug('recette-paiement')
    ->setPrice(15000)->setDuration(90)->setDescription('Données fictives réservées à la recette locale.');
$em->persist($type);
foreach (['opening_delay_hours' => '0', 'open_days' => '1,2,3,4,5,6,7', 'morning_start' => '09:00', 'morning_end' => '12:00', 'afternoon_start' => '14:00', 'afternoon_end' => '19:00', 'slot_buffer_minutes' => '0', 'fixed_slots' => '0'] as $key => $value) {
    $em->persist((new \App\Entity\ScheduleSetting())->setSettingKey($key)->setValue($value));
}
$em->persist(new \App\Entity\Setting('maintenance', false));
$em->flush();
completeCompany($em);
saveState('accounts-private.json', $accounts);
saveState('fixtures.json', ['type_id' => $type->getId(), 'amount' => 15000, 'duration' => 90]);
echo "Ready: http://127.0.0.1:8097/_recette ; fictitious accounts kept in local accounts-private.json\n";
