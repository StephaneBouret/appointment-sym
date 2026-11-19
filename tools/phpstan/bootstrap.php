<?php
// Intentionally does not invoke Dotenv or load any existing application cache.
$safe = [
    'APP_ENV' => 'phpstan_diag', 'APP_DEBUG' => '1',
    'APP_SECRET' => 'diagnostic-not-a-real-secret',
    'APP_HOSTNAME' => 'http://diagnostic.invalid', 'DEFAULT_URI' => 'http://diagnostic.invalid',
    'DATABASE_URL' => 'mysql://diagnostic:diagnostic@127.0.0.1:1/diagnostic?serverVersion=8.0.37&charset=utf8mb4',
    'MAILER_DSN' => 'null://null', 'MAILER_DEFAULT_FROM' => 'diagnostic@example.invalid',
    'MESSENGER_TRANSPORT_DSN' => 'in-memory://',
    'GOOGLE_API_KEY' => 'diagnostic-disabled', 'GOOGLE_PLACE_ID' => 'diagnostic-disabled',
    'STRIPE_SECRET_KEY' => 'sk_test_diagnostic_disabled', 'STRIPE_PUBLIC_KEY' => 'pk_test_diagnostic_disabled',
    'STRIPE_WEBHOOK_SECRET' => 'whsec_diagnostic_disabled', 'GA4_ENABLED' => '0', 'GA4_MEASUREMENT_ID' => '',
];
foreach ($safe as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
date_default_timezone_set('UTC');
require_once dirname(__DIR__, 2).'/vendor/autoload.php';

// A connection wrapper blocks network while preserving the real PDO MySQL driver inference.
class DiagnosticNoNetworkConnection extends Doctrine\DBAL\Connection
{
    protected function connect(): Doctrine\DBAL\Driver\Connection
    {
        throw new LogicException('Database connection forbidden during PHPStan diagnostic.');
    }
}

class DiagnosticKernel extends App\Kernel
{
    public function getProjectDir(): string { return dirname(__DIR__, 2); }
    protected function build(Symfony\Component\DependencyInjection\ContainerBuilder $container): void
    {
        parent::build($container);
        $container->setParameter('container.dumper.inline_class_loader', false);
        $container->prependExtensionConfig('doctrine', ['dbal' => ['wrapper_class' => DiagnosticNoNetworkConnection::class]]);
    }
}

function diagnosticKernel(): DiagnosticKernel
{
    static $kernel;
    if (!$kernel) {
        $kernel = new DiagnosticKernel('phpstan_diag', true);
        $kernel->boot();
    }
    return $kernel;
}
