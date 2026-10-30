<?php

// Local, read-only HTTP harness. No private env files, existing DB, mail or Stripe.
if (PHP_SAPI !== 'cli-server' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403); exit;
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    http_response_code(405); exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$public = realpath(dirname(__DIR__, 2).'/public');
$file = realpath($public.$path);
if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)
    && preg_match('/\.(?:css|js|png|webp|jpg|jpeg|svg|ico|woff2?|ttf)$/i', $file)) return false;

if (!preg_match('#^/(?:$|assets/|rendez-vous/types(?:/[12])?$|audience/frame$|login$|politique-de-confidentialite$|rendez-vous/type/[12]/form$)#', $path)) {
    http_response_code(404); exit;
}
require dirname(__DIR__).'/Prestations/bootstrap.php';
$mode = $_COOKIE['analytics_test_mode'] ?? 'enabled';
$_SERVER['GA4_ENABLED'] = $_ENV['GA4_ENABLED'] = $mode === 'disabled' ? '0' : '1';
$_SERVER['GA4_MEASUREMENT_ID'] = $_ENV['GA4_MEASUREMENT_ID'] = match ($mode) {
    'missing' => '', 'invalid' => 'NOT-A-MEASUREMENT-ID', default => 'G-TEST123456',
};
$kernel = new \App\Tests\Prestations\HomeTestKernel('test', true);
$kernel->boot();
$em = $kernel->getContainer()->get('test.service_container')->get('doctrine')->getManager();
\App\Tests\Prestations\HomeFixtures::create($em);
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$client = new \Symfony\Bundle\FrameworkBundle\KernelBrowser($kernel);
$client->disableReboot();
if (($_COOKIE['analytics_test_user'] ?? '') === 'yes') {
    $user = (new \App\Entity\User())->setEmail('synthetic@example.test')->setFirstname('Test')->setLastname('Test')
        ->setAdress('1 rue de test')->setPostalCode('75001')->setCity('Paris')->setPassword('unused')
        ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'));
    $avatar = new \App\Entity\Avatar($user);
    $avatar->setImageName('synthetic-avatar.png');
    $user->setAvatar($avatar);
    $em->persist($user);
    $em->flush();
    $client->loginUser($user);
}
$client->request('GET', $request->getUri(), server: ['HTTP_X_REQUESTED_WITH' => $request->headers->get('X-Requested-With', '')]);
$response = $client->getResponse();
$response->send();
$kernel->terminate($request, $response);
$em->getConnection()->close();
$kernel->shutdown();
