<?php
copy('https://getcomposer.org/installer', 'composer-setup.php');
echo hash_file('SHA384', 'composer-setup.php').PHP_EOL;
if (getenv('COMPOSER_VERSION') === 'beta') {
    passthru('php composer-setup.php --preview=beta');
} elseif (getenv('COMPOSER_VERSION') === 'rc') {
    passthru('php composer-setup.php --preview=rc');
} elseif (getenv('COMPOSER_VERSION') === 'dev') {
    passthru('php composer-setup.php --snapshot');
} else {
    passthru('php composer-setup.php --version='.getenv('COMPOSER_VERSION'));
}
unlink('composer-setup.php');
