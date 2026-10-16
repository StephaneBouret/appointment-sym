<?php

// Local-only browser harness; never use public/index.php or load environment files.
if (PHP_SAPI !== 'cli-server' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit;
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    http_response_code(405);
    exit;
}
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$public = realpath(dirname(__DIR__, 2).'/public');
$file = realpath($public.$path);
if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)
    && preg_match('/\.(?:css|js|png|webp|jpg|jpeg|svg|ico|woff2?|ttf)$/i', $file)) {
    return false;
}
if ($path !== '/' && !preg_match('#^/(?:assets/|rendez-vous/types(?:/\d+)?$)#', $path)) {
    http_response_code(404);
    exit;
}
require __DIR__.'/bootstrap.php';
$kernel = new \App\Tests\Prestations\HomeTestKernel('test', true);
$kernel->boot();
$em = $kernel->getContainer()->get('test.service_container')->get('doctrine')->getManager();
// Synthetic records only; the illustration is an existing read-only local asset.
\App\Tests\Prestations\HomeFixtures::create($em, 'ft-formes-compo-01-6acf4fd6cf5ea128098129.webp');
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
$em->getConnection()->close();
$kernel->shutdown();
