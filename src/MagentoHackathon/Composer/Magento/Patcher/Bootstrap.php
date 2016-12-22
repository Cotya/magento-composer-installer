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
     * @var string
     */
    private $mageClassFilePath;

    /**
     * @param string $mageClassFilePath Path to the Mage.php file which the patch will be applied on.
     * @param ProjectConfig $config
     */
    private function __construct($mageClassFilePath, ProjectConfig $config)
    {
        $this->setMageClassFilePath($mageClassFilePath);
        $this->config = $config;
    }

    /**
     * @param ProjectConfig $config
     * @return $this
     */
    public static function fromConfig(ProjectConfig $config)
    {
        return new self($config->getMagentoRootDir() . '/app/Mage.php', $config);
    }

    /**
     * @return ProjectConfig
     */
    private function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     * @throws \DomainException
     */
    private function getMageClassFilePath()
    {
        $mageFileCheck = true;

        if (!is_file($this->mageClassFilePath)) {
            $message = "{$this->mageClassFilePath} is not a file.";
            $mageFileCheck = false;
        } elseif (!is_readable($this->mageClassFilePath)) {
            $message = "{$this->mageClassFilePath} is not readable.";
            $mageFileCheck = false;
        } elseif (!is_writable($this->mageClassFilePath)) {
            $message = "{$this->mageClassFilePath} is not writable.";
            $mageFileCheck = false;
        }

        if (!$mageFileCheck) {
            throw new \DomainException($message);
        }

        return $this->mageClassFilePath;
    }

    /**
     * Path to the Mage.php file which the patch will be applied on.
     *
     * @param string $mageClassFilePath
     */
    private function setMageClassFilePath($mageClassFilePath)
    {
        $this->mageClassFilePath = $mageClassFilePath;
    }

    /**
     * @return bool
     */
    private function isPatchAlreadyApplied()
    {
        return strpos(file_get_contents($this->getMageClassFilePath()), self::PATCH_MARK) !== false;
    }

    /**
     * @return bool
     */
    public function canApplyPatch()
    {
        // check the config first
        if (!$this->getConfig()->mustApplyBootstrapPatch()) {
            $message = "<comment>Magento autoloader patching skipped because of configuration flag</comment>";
            $result = false;
        } elseif ($this->isPatchAlreadyApplied()) {
            $message = "<comment>{$this->getMageClassFilePath()} was already patched</comment>";
            $result = false;
        } else {
            $result = true;
            $message = "<info>Autoloader patch to {$this->getMageClassFilePath()} was applied successfully</info>";
        }

        $this->getIo()->write($message);

        return $result;
    }

    /**
     * @return bool
     */
    public function patch()
    {
        return $this->canApplyPatch() ? $this->writeComposerAutoloaderPatch() : false;
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
        $mageFileContent = file($this->getMageClassFilePath());

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

        return file_put_contents($this->getMageClassFilePath(), $mageFileReplacement) !== false;
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
            $this->io = new NullIO;
        }
        return $this->io;
    }

    /**
     * @return string
     */
    private function getAutoloaderPatchString()
    {
        $patchMark = self::PATCH_MARK;

        // get the vendor folder name from Config, in case it's changed
        $vendorFolderName = basename($this->getConfig()->getVendorDir());

        $autoloadPhp = $vendorFolderName . '/autoload.php';

        return <<<PATCH
/** $patchMark **/
if (file_exists(\$autoloaderPath = BP . DS . '{$autoloadPhp}') ||
    file_exists(\$autoloaderPath = BP . DS . '../{$autoloadPhp}')
) {
    require \$autoloaderPath;
}
/** $patchMark **/
PATCH;
    }
}
