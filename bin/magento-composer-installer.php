#!/usr/bin/env php
<?php

if (file_exists(__DIR__.'/../autoload.php')) {
    include __DIR__.'/../autoload.php';
} elseif (file_exists(__DIR__.'/../vendor/autoload.php')) {
    include __DIR__.'/../vendor/autoload.php';
} else {
    fwrite(STDERR, "did not find autoload.php");
}


use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new \MagentoHackathon\Composer\Magento\Command\DeployCommand());
$application->run();


