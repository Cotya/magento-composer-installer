#!/usr/bin/env php
<?php

echo 'please use 
`composer.phar run-script post-install-cmd -vvv -- --redeploy` to initiate a redeploy of modules'.PHP_EOL;
exit(1);

if (file_exists(__DIR__.'/../autoload.php')) {
    include __DIR__.'/../autoload.php';
} elseif (file_exists(__DIR__.'/../../../autoload.php')) {
    include __DIR__.'/../../../autoload.php';
} elseif (file_exists(__DIR__.'/../vendor/autoload.php')) {
    include __DIR__.'/../vendor/autoload.php';
} else {
    fwrite(STDERR, "did not find autoload.php");
}


use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new \MagentoHackathon\Composer\Magento\Command\DeployCommand());
$application->add(new \MagentoHackathon\Composer\Magento\Command\DeployAllCommand());
$application->run();


