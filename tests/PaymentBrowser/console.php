<?php

namespace App\Tests\PaymentBrowser;

require __DIR__.'/runtime.php';
try {
    $kernel = boot();
    $container = $kernel->getContainer()->get('test.service_container');
    $db = $container->get('doctrine')->getConnection();
    echo 'COMMAND EFFECTIVE TARGET: '.json_encode(guard($db), JSON_THROW_ON_ERROR)."\n";
    $action = $argv[1] ?? 'status';
    if ($action === 'complete-fixture-avatars') {
        $em = $container->get('doctrine')->getManager();
        foreach ($em->getRepository(\App\Entity\User::class)->findAll() as $user) {
            if (!$user->getAvatar()) {
                $avatar = new \App\Entity\Avatar($user);
                $avatar->setImageName('local-fixture.svg');
                $em->persist($avatar);
            }
        }
        $em->flush();
        completeCompany($em);
        echo "Missing fictitious avatars/company completed.\n";
    } elseif ($action === 'status') {
        echo json_encode(snapshot($kernel), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
    } elseif ($action === 'price-modal-proof') {
        // Only the existing fictitious paid appointment; restore the catalog even if CDP fails.
        $em = $container->get('doctrine')->getManager();
        $appointment = $em->find(\App\Entity\Appointment::class, 1);
        ensure($appointment && $appointment->getPayment()->amount === 15000 && $appointment->getPayment()->verifiedAt !== null, 'Expected paid fixture missing');
        $type = $appointment->getType();
        $original = $type->getPrice();
        ensure($original === 15000 && $type->getName() === 'Prestation fictive — recette paiement', 'Unexpected catalog fixture');
        $before = snapshot($kernel);
        $proof = ['target' => guard($db), 'paid_amount' => 15000, 'temporary_catalog_price' => 18000];
        try {
            $type->setPrice(18000);
            $em->flush();
            $process = proc_open([getenv('USERPROFILE').'/scoop/apps/python/current/python.exe', __DIR__.'/browser.py', 'cancel-amount-proof'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
            ensure(is_resource($process), 'Browser helper unavailable');
            $output = stream_get_contents($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            ensure(proc_close($process) === 0, 'Browser modal check failed');
            $proof['browser'] = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        } finally {
            guard($db);
            $type->setPrice($original);
            $em->flush();
            $em->refresh($type);
            $proof['restored_catalog_price'] = $type->getPrice();
            $after = snapshot($kernel);
            $proof['appointments_unchanged'] = $before['appointments'] === $after['appointments'];
            $proof['mail_unchanged'] = $before['captured_mail_count'] === $after['captured_mail_count'];
            saveState('proof-cancel-price-'.($argv[2] ?? 'final').'.json', $proof);
            echo json_encode($proof, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
        }
        ensure($proof['browser']['amountCents'] === (int) ($argv[3] ?? 15000) && $proof['restored_catalog_price'] === 15000 && $proof['appointments_unchanged'] && $proof['mail_unchanged'], 'Unexpected cancellation modal result');
    } elseif ($action === 'block-return') {
        ensure(isset($argv[2]) && ctype_digit($argv[2]) && $db->fetchOne('SELECT id FROM appointment WHERE id = ?', [(int) $argv[2]]), 'An existing fixture ID is required');
        saveState('blocked-return.json', [(int) $argv[2]]);
        echo "Return blocked for the specified appointment before controller execution.\n";
    } elseif ($action === 'unblock-return') {
        saveState('blocked-return.json', []);
    } elseif ($action === 'notifications') {
        $command = $container->get(\App\Command\PaymentNotificationsCommand::class);
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--retry' => true]);
        $code = $command->run($input, new \Symfony\Component\Console\Output\ConsoleOutput());
        echo json_encode(snapshot($kernel), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
        exit($code);
    } elseif ($action === 'replay') {
        ensure(stripeReady() && isset($argv[2]) && ctype_digit($argv[2]), 'TEST configuration and fixture ID required');
        $id = $db->fetchOne('SELECT payment_intent_id FROM appointment WHERE id = ?', [(int) $argv[2]]);
        $matches = array_filter(array_map(fn ($line) => json_decode($line, true), file(state().'/webhook-proof.ndjson', FILE_IGNORE_NEW_LINES)), fn ($entry) => $entry['intent'] === $id);
        ensure(count($matches) > 0, 'No received event for this associated PaymentIntent');
        $keys = readState('stripe-private.json');
        $event = (new \Stripe\StripeClient($keys['secret']))->events->retrieve(array_values($matches)[0]['event']);
        ensure($event->livemode === false && $event->type === 'payment_intent.succeeded' && $event->data->object->id === $id, 'Wrong Stripe TEST event');
        $body = $event->toJSON();
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, $keys['webhook']);
        $response = \Symfony\Component\HttpClient\HttpClient::create()->request('POST', 'http://127.0.0.1:8097/stripe/webhook', ['headers' => ['Stripe-Signature' => $signature, 'Content-Type' => 'application/json'], 'body' => $body]);
        ensure($response->getStatusCode() === 204, 'Replay rejected');
        echo 'Locally re-signed replay of actual Stripe TEST event '.$event->id."\n";
        echo json_encode(snapshot($kernel), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
    } elseif (in_array($action, ['stripe-proof', 'stripe-3ds-proof'], true)) {
        ensure(stripeReady() && isset($argv[2]) && ctype_digit($argv[2]), 'TEST configuration and fixture ID required');
        $id = $db->fetchOne('SELECT payment_intent_id FROM appointment WHERE id = ?', [(int) $argv[2]]);
        ensure(is_string($id) && str_starts_with($id, 'pi_'), 'No associated PaymentIntent');
        $intent = $container->get(\App\Stripe\StripeService::class)->retrievePaymentIntent($id);
        ensure($intent->livemode === false, 'Live object rejected');
        $proof = ['id' => $intent->id, 'livemode' => $intent->livemode, 'status' => $intent->status, 'amount' => $intent->amount, 'amount_received' => $intent->amount_received, 'currency' => $intent->currency, 'appointment_id' => $intent->metadata['appointment_id'], 'last_error' => $intent->last_payment_error?->code];
        if ($action === 'stripe-3ds-proof') {
            ensure(is_string($intent->latest_charge), 'No Stripe charge yet');
            $charge = (new \Stripe\StripeClient(readState('stripe-private.json')['secret']))->charges->retrieve($intent->latest_charge);
            ensure($charge->livemode === false && $charge->payment_intent === $id, 'Wrong associated TEST charge');
            $authentication = $charge->payment_method_details?->card?->three_d_secure;
            $proof['charge'] = ['id' => $charge->id, 'paid' => $charge->paid, 'status' => $charge->status,
                'amount_captured' => $charge->amount_captured, 'three_d_secure' => ['result' => $authentication?->result,
                    'authentication_flow' => $authentication?->authentication_flow, 'version' => $authentication?->version]];
            $proof['application'] = snapshot($kernel);
            ensure($intent->status === 'succeeded' && $authentication?->result === 'authenticated', '3DS success not established');
        }
        saveState('stripe-proof-'.$argv[2].'-'.gmdate('His').'.json', $proof);
        echo json_encode($proof, JSON_THROW_ON_ERROR)."\n";
    } else { throw new \RuntimeException('Unknown action'); }
} catch (\Throwable $error) {
    fwrite(STDERR, $error::class.' at '.basename($error->getFile()).':'.$error->getLine()."\n"); exit(1);
}
