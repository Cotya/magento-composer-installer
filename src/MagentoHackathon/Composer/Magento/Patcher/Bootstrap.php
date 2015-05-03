<?php

namespace MagentoHackathon\Composer\Magento\Patcher;

use Composer\IO\NullIO;
use Composer\IO\IOInterface;
use MagentoHackathon\Composer\Magento\ProjectConfig;

class Bootstrap
{
    /**
     * String inserted as a PHP comment, before and after the patch code.
     */
    const PATCH_MARK = 'AUTOLOADER PATCH';

    /**
     * @var ProjectConfig
     */
    private $config;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @param ProjectConfig $config
     */
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

    /**
     * @return bool
     */
    private function isPatchAlreadyApplied()
    {
        return strpos(file_get_contents($this->getAppPath() . '/Mage.php'), self::PATCH_MARK) !== false;
    }

    /**
     * @return bool
     */
    private function canApplyPatch()
    {
        $mageClassPath = $this->getAppPath() . '/Mage.php';

        $result = true;
        $message = "<info>Autoloader patch to {$mageClassPath} was applied successfully</info>";

        if (!is_file($mageClassPath)) {
            $message = "<error>{$mageClassPath} is not a file</error>";
            $result = false;
        } elseif (!is_readable($mageClassPath)) {
            $message = "<error>{$mageClassPath} is not readable</error>";
            $result = false;
        } elseif (!is_writable($mageClassPath)) {
            $message = "<error>{$mageClassPath} is not writable</error>";
            $result = false;
        } elseif ($this->isPatchAlreadyApplied()) {
            $message = "<comment>{$mageClassPath} was already patched</comment>";
            $result = false;
        } elseif (!$this->getConfig()->mustApplyBootstrapPatch()) {
            $message = "<comment>Magento autoloader patching skipped because of configuration flag</comment>";
            $result = false;
        }

        $this->getIo()->write($message);

        return $result;
    }

    /**
     * @return bool
     */
    public function patch()
    {
        return $this->canApplyPatch() ? $this->writeComposerAutoloaderPatch(): false;
    }

    /**
     * @return string
     */
    protected function getAppPath()
    {
        return $this->getConfig()->getMagentoRootDir() . '/app';
    }

    /**
     * @return bool
     */
    protected function writeComposerAutoloaderPatch()
    {
        $appPath = $this->getAppPath();

        $mageFileContent = file($appPath . '/Mage.php');

        $mageFileBootstrapPart = '';
        $mageFileClassDeclarationPart = '';
        $isBootstrapPart = true;

        foreach ($mageFileContent as $row) {
            if ($isBootstrapPart) {
                $mageFileBootstrapPart .= $row;
            } else {
                $mageFileClassDeclarationPart .= $row;
            }
            if (strpos($row, 'Varien_Autoload') === 0) {
                $isBootstrapPart = false;
            }
        }

        $mageFileReplacement = $mageFileBootstrapPart . PHP_EOL
                             . $this->getAutoloaderPatchString() . PHP_EOL
                             . $mageFileClassDeclarationPart;

        return file_put_contents($appPath . '/Mage.php', $mageFileReplacement) !== false;
    }

    /**
     * @param IOInterface $io
     */
    public function setIo(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * @return IOInterface
     */
    public function getIo()
    {
        if (!$this->io) {
            $this->io = new NullIO();
        }
        return $this->io;
    }

    /**
     * @return string
     */
    private function getAutoloaderPatchString()
    {
        $patchMark = self::PATCH_MARK;
        return <<<PATCH
/** $patchMark **/
\$autoloaderPath = '{$this->getVendorAutoloaderPath()}';
if (file_exists(\$autoloaderPath)) {
    Mage::register('COMPOSER_CLASSLOADER', require_once \$autoloaderPath);
}
/** $patchMark **/
PATCH;
    }

    /**
     * @return string
     */
    private function getVendorAutoloaderPath()
    {
        return $this->getConfig()->getVendorDir() . '/autoload.php';
    }
}
