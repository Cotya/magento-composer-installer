<?php
copy('https://getcomposer.org/installer', 'composer-setup.php');
echo hash_file('SHA384', 'composer-setup.php').PHP_EOL;
$command = 'php composer-setup.php';
if (getenv('COMPOSER_VERSION')) {
    if (getenv('COMPOSER_VERSION') === 'beta') {
        $command = ('php composer-setup.php --preview=beta');
    } elseif (getenv('COMPOSER_VERSION') === 'rc') {
        $command = ('php composer-setup.php --preview=rc');
    } elseif (getenv('COMPOSER_VERSION') === 'dev') {
        $command = ('php composer-setup.php --snapshot');
    } else {
        $command = ('php composer-setup.php --version='.getenv('COMPOSER_VERSION'));
    }
}
echo "execute: $command" . PHP_EOL;
passthru($command);
unlink('composer-setup.php');
