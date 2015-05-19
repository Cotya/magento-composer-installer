<?php

namespace MagentoHackathon\Composer\Magento\Patcher;

use MagentoHackathon\Composer\Magento\ProjectConfig;

class Bootstrap
{
    /**
     * @var ProjectConfig
     */
    private $config;

    public function __construct(ProjectConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return ProjectConfig
     */
    private function getConfig()
    {
        return $this->config;
    }

    private function canApplyPatch()
    {
        $mageClassPath = $this->getConfig()->getMagentoRootDir() . '/app/Mage.php';

        return $this->getConfig()->mustApplyBootstrapPatch() &&
        is_file($mageClassPath) &&
        is_writable($mageClassPath);
    }

    /**
     * @return bool
     */
    public function patch()
    {
        if ($this->canApplyPatch()) {
            $this->splitOriginalMage();
            $this->generateNewMageFile();
            return true;
        }
        return false;
    }

    protected function getAppPath()
    {
        return $this->getConfig()->getMagentoRootDir() . '/app';
    }

    protected function splitOriginalMage()
    {
        $appPath = $this->getAppPath();
        if (file_exists($appPath . '/Mage.class.php')) {
            return;
        }

        $mageFileContent = file($appPath . '/Mage.php');

        $mageClassFile = '';
        $mageBootstrapFile = '<?php' . PHP_EOL;
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

    }

    protected function generateNewMageFile()
    {
        $appPath = $this->getAppPath();
        $mageFileReplacement
            = <<<php
<?php
require __DIR__ . '/Mage.class.php';
require __DIR__ . '/Mage.bootstrap.php';

php;
        file_put_contents($appPath . '/Mage.php', $mageFileReplacement);
    }
}
