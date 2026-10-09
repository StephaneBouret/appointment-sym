<?php

namespace App\Tests\PaymentBrowser;

require __DIR__.'/runtime.php';
try {
    ensure(in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true), 'Loopback access only');
    ensure(($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1:8097', 'Unexpected host');
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path === '/images/avatars/local-fixture.svg') {
        header('Content-Type: image/svg+xml'); echo '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#4c6780"/><text x="30" y="68" font-size="50" fill="white">T</text></svg>'; return;
    }
    // Never fall through to public/index.php (it would load the normal environment).
    if (preg_match('#^/(assets|images|favicon|bundles)/#', $path)) {
        $file = realpath(dirname(__DIR__, 2).'/public'.$path);
        if ($file && str_starts_with($file, realpath(dirname(__DIR__, 2).'/public').DIRECTORY_SEPARATOR) && is_file($file) && !str_ends_with($file, '.php')) { return false; }
    }
    $kernel = boot();
    header('X-Robots-Tag: noindex, nofollow');
    if ($path === '/_recette/status') {
        header('Content-Type: application/json'); echo json_encode(snapshot($kernel), JSON_THROW_ON_ERROR); return;
    }
    if ($path === '/_recette') {
        $fixtures = readState('fixtures.json');
        echo '<meta name="viewport" content="width=device-width"><h1>Recette locale — Stripe TEST uniquement</h1>';
        echo '<p><a href="/login?target=/rendez-vous/types">Connexion</a> · <a href="/rendez-vous/types">Prestations</a> · <a href="/admin">Administration</a> · <a href="/_recette/mail">Courrier capturé</a> · <a href="/_recette/status">Preuves en base</a></p>';
        echo '<pre>'.htmlspecialchars(json_encode(snapshot($kernel), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</pre>'; return;
    }
    if ($path === '/_recette/mail') {
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
        echo '<meta charset="utf-8"><h1>Courrier capturé localement</h1>';
        foreach (array_reverse(glob(state().'/mail/*.json')) as $file) {
            $mail = json_decode(file_get_contents($file), true);
            echo '<h2>'.htmlspecialchars($mail['subject']).'</h2><p>'.htmlspecialchars(implode(', ', $mail['to'])).'</p>';
            echo '<pre>'.htmlspecialchars($mail['text'] ?? strip_tags($mail['html'] ?? '')).'</pre>';
        }
        return;
    }
    if (preg_match('#^/rendez-vous/terminate/(\d+)$#', $path, $match) && is_file(state().'/blocked-return.json')) {
        if (in_array((int) $match[1], readState('blocked-return.json'), true)) {
            file_put_contents(state().'/blocked-return-proof.ndjson', json_encode(['appointment' => (int) $match[1], 'at' => gmdate('c')])."\n", FILE_APPEND | LOCK_EX);
            http_response_code(451); echo 'Retour navigateur bloque volontairement pour la recette webhook seul.'; return;
        }
    }
    $needsStripe = str_starts_with($path, '/rendez-vous/pay/');
    if (preg_match('#^/rendez-vous/terminate/(\d+)$#', $path, $returnMatch)) {
        $needsStripe = (bool) $kernel->getContainer()->get('doctrine')->getConnection()->fetchOne('SELECT payment_intent_id FROM appointment WHERE id = ?', [(int) $returnMatch[1]]);
    }
    if ($needsStripe && !stripeReady()) {
        http_response_code(503); echo 'Configurer les cles Stripe TEST et le listener dans ConfigureStripe.ps1.'; return;
    }
    if ($path === '/stripe/webhook') {
        $body = file_get_contents('php://input');
        $event = json_decode($body, true);
        ensure(($event['livemode'] ?? null) === false, 'Only TEST events accepted');
    }
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $response = $kernel->handle($request);
    if ($path === '/stripe/webhook' && $response->getStatusCode() === 204) {
        $db = $kernel->getContainer()->get('doctrine')->getConnection();
        $appointment = $db->fetchAssociative('SELECT id, status, number, payment_notification_state FROM appointment WHERE payment_intent_id = ?', [$event['data']['object']['id'] ?? '']);
        if ($appointment) {
            file_put_contents(state().'/webhook-proof.ndjson', json_encode(['event' => $event['id'], 'intent' => $event['data']['object']['id'], 'livemode' => $event['livemode'], 'at' => gmdate('c'), 'appointment' => $appointment])."\n", FILE_APPEND | LOCK_EX);
        }
    }
    $response->send();
    $kernel->terminate($request, $response);
} catch (\Throwable $error) {
    http_response_code(503);
    // Class/location only: never leak request/client_secret/credentials in an error page.
    echo htmlspecialchars($error::class.' at '.basename($error->getFile()).':'.$error->getLine());
}
