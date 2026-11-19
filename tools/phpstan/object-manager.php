<?php
require_once __DIR__.'/bootstrap.php';
return diagnosticKernel()->getContainer()->get('doctrine')->getManager();
