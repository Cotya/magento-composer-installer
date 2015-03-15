<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer;

use MagentoHackathon\Composer\Magento\ProjectConfig;

class Helper
{
    /**
     * @var \SplFileInfo
     */
    protected $projectRoot;

    /**
     * @var ProjectConfig
     */
    protected $magentoProjectConfig;

    /**
     * @param \SplFileInfo $projectRoot
     */
    public function __construct(\SplFileInfo $projectRoot)
    {
        if (!file_exists($projectRoot->getPathname().'/composer.json')) {
            throw new \InvalidArgumentException('no composer.json found in project root');
        }
        $this->projectRoot = $projectRoot;
        
        $reader = new \Eloquent\Composer\Configuration\ConfigurationReader;
        $composerJsonObject = $reader->read($this->projectRoot.'/composer.json');
        $this->magentoProjectConfig = new ProjectConfig(
            (array)$composerJsonObject->extra(),
            (array)$composerJsonObject
        );
    }
    
    public function getVendorDirectory()
    {
        /*
        $reader = new \Eloquent\Composer\Configuration\ConfigurationReader;
        $composerJsonObject = $reader->read($this->projectRoot.'/composer.json');
        return $composerJsonObject->vendorName();
        */
        return new \SplFileInfo($this->projectRoot.'/vendor');
    }
    
    public function getInstalledPackages()
    {
        
        $installedJsonObject = json_decode(file_get_contents(
            $this->getVendorDirectory()->getPathname().'/composer/installed.json'
        ), true);
        return $installedJsonObject;
    }

    /**
     * @return ProjectConfig
     */
    public function getMagentoProjectConfig()
    {
        return $this->magentoProjectConfig;
    }
    
    public function getPackageByName($name)
    {
        $result = null;
        foreach ($this->getInstalledPackages() as $package) {
            if ($package['name'] == $name) {
                $result = $package;
                break;
            }
        }
        return $result;
    }
    
    public static function initMagentoRootDir(
        ProjectConfig $projectConfig,
        \Composer\IO\IOInterface $io,
        \Composer\Util\Filesystem $filesystem,
        $vendorDir
    ) {
        if (false === $projectConfig->hasMagentoRootDir()) {
            $projectConfig->setMagentoRootDir(
                $io->ask(
                    sprintf('please define your magento root dir [%s]', ProjectConfig::DEFAULT_MAGENTO_ROOT_DIR),
                    ProjectConfig::DEFAULT_MAGENTO_ROOT_DIR
                )
            );
        }

        $magentoRootDirPath = $projectConfig->getMagentoRootDir();
        $magentoRootDir = new \SplFileInfo($magentoRootDirPath);

        if (!is_dir($magentoRootDirPath)
            && $io->askConfirmation(
                'magento root dir "' . $magentoRootDirPath . '" missing! create now? [Y,n] '
            )
        ) {
            $filesystem->ensureDirectoryExists($magentoRootDir);
            $io->write('magento root dir "' . $magentoRootDirPath . '" created');
        }

        if (!is_dir($magentoRootDirPath)) {
            $dir = self::joinFilePath($vendorDir, $magentoRootDirPath);
        }
    }

    /**
     * join 2 paths
     *
     * @param        $path1
     * @param        $path2
     * @param        $delimiter
     * @param bool   $prependDelimiter
     * @param string $additionalPrefix
     *
     * @internal param $url1
     * @internal param $url2
     *
     * @return string
     */
    public static function joinPath($path1, $path2, $delimiter, $prependDelimiter = false, $additionalPrefix = '')
    {
        $prefix = $additionalPrefix . $prependDelimiter ? $delimiter : '';

        return $prefix . join(
            $delimiter,
            array(
                explode($path1, $delimiter),
                explode($path2, $delimiter)
            )
        );
    }

    /**
     * @param $path1
     * @param $path2
     *
     * @return string
     */
    public static function joinFilePath($path1, $path2)
    {
        return self::joinPath($path1, $path2, DIRECTORY_SEPARATOR, true);
    }
}
