<?php

require_once __DIR__.'/bootstrap.php';

$kernel = diagnosticKernel();
$xml = $kernel->getBuildDir().'/'.$kernel->getContainer()->getParameter('kernel.container_class').'.xml';
if (!is_readable($xml)) {
    throw new RuntimeException('Missing PHPStan container XML: '.$xml);
}
