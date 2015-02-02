<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento\Patcher;

class Bootstrap
{
    protected $magentoBasePath;

    public function __construct($magentoBasePath)
    {
        $this->magentoBasePath = $magentoBasePath;
    }


    public function patch()
    {
        $this->splitOriginalMage();
        $this->generateBootstrapFile();
    }

    protected function getAppPath()
    {
        return $this->magentoBasePath . '/app';
    }

    protected function splitOriginalMage()
    {
        $appPath = $this->getAppPath();
        if (file_exists($appPath . '/Mage.class.php')) {
            return;
        }

        $mageFileContent = file($appPath . '/Mage.php');

        $mageClassFile = '';
        $mageBootstrapFile = '';
        $isBootstrapPart = false;
        foreach ($mageFileContent as $row) {
            if (strpos($row, 'define') === 0) {
                $isBootstrapPart = true;
            }
            if ($isBootstrapPart) {
                $mageBootstrapFile .= $row;
            } else {
                $mageClassFile .= $row;
            }
            if (strpos($row, 'Varien_Autoload') === 0) {
                $isBootstrapPart = false;
            }
        }
        $mageClassFile .= PHP_EOL;
        $mageBootstrapFile .= PHP_EOL;
        file_put_contents($appPath . '/Mage.class.php', $mageClassFile);
        file_put_contents($appPath . '/Mage.bootstrap.php', $mageBootstrapFile);

        $mageFileReplacement
            = <<<php
<?php
require __DIR__ . '/bootstrap.php;

php;
        file_put_contents($appPath . '/Mage.php', $mageFileReplacement);


    }

    protected function generateBootstrapFile()
    {
        $appPath = $this->getAppPath();
        $bootstrapFile
            = <<<php
<?php
require __DIR__ . 'Mage.bootstrap.php';
require __DIR__ . 'Mage.class.php';

php;
        if (!file_exists($appPath . '/bootstrap.php')) {
            file_put_contents($appPath . '/bootstrap.php', $bootstrapFile);
        }
    }
}
