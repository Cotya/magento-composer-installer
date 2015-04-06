<?php
/**
 * CoreInstaller.php
 */

namespace MagentoHackathon\Composer\Magento\Installer;


use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use InvalidArgumentException;
use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Deploystrategy\Core;

/**
 * Class CoreInstaller
 *
 * @package MagentoHackathon\Composer\Magento\Installer
 */
class CoreInstaller extends MagentoInstallerAbstract
{
    /**
     * Package Type Definition
     */
    const PACKAGE_TYPE = 'magento-core';

    /**
     * relative magento cache path
     */
    const MAGENTO_CACHE_PATH = 'var/cache';

    /**
     * suffix for temporary root folder
     */
    const MAGENTO_ROOT_DIR_TMP_SUFFIX = '_tmp';

    /**
     * suffix for backup root folder
     */
    const MAGENTO_ROOT_DIR_BACKUP_SUFFIX = '_bkup';

    /**
     * relative path to magentos local.xml file
     */
    const MAGENTO_LOCAL_XML_PATH = 'app/etc/local.xml';

    /**
     * @var \SplFileInfo
     */
    protected $originalMagentoRootDir = null;

    /**
     * @var \SplFileInfo
     */
    protected $backupMagentoRootDir = null;

    /**
     * @var bool
     */
    protected $keepMagentoCache = false;

    /**
     * Directories that need write Permissions for the Web Server
     *
     * @var array
     */
    protected $magentoWritableDirs
        = array(
            'app/etc',
            'media',
            'var'
        );

    /**
     * @param IOInterface $io
     * @param Composer    $composer
     * @param string      $type
     *
     * @throws \ErrorException
     */
    public function __construct(IOInterface $io, Composer $composer, $type = self::PACKAGE_TYPE)
    {
        parent::__construct($io, $composer, $type);
    }

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     *
     * @return bool
     */
    public function supports($packageType)
    {
        return self::PACKAGE_TYPE === $packageType;
    }


    /**
     * Installs specific package
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);
    }

    /**
     * Updates specific package
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $initial already installed package version
     * @param PackageInterface             $target  updated version
     *
     * @throws InvalidArgumentException if $from package is not installed
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        parent::update($repo, $initial, $target);
    }



    /**
     * Returns the strategy class used for deployment
     * Magento Core is always Deployed with Copy Strategy
     *
     * @param \Composer\Package\PackageInterface $package
     * @param string                             $strategy
     *
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function getDeployStrategy(PackageInterface $package, $strategy = null)
    {
        $deployStrategy = new Core($this->getSourceDir($package), $this->getTargetDir());
        $deployStrategy->setIgnoredMappings($this->getModuleSpecificDeployIgnores($package));
        $deployStrategy->setIsForced($this->isForced);
        return $deployStrategy;
    }

    /**
     * Returns the installation path of a package
     * We don't use the parent class for this because Magento Core should never be installed in  the modman-root-dir
     *
     * @param  PackageInterface $package
     * @return string           path
     */
    public function getInstallPath(PackageInterface $package)
    {
        $targetDir = $package->getTargetDir();
        $installPath = $this->getPackageBasePath($package) . ($targetDir ? '/'.$targetDir : '');

        // Make install path absolute. This is needed in the symlink deploy strategies.
        if (DIRECTORY_SEPARATOR !== $installPath[0] && $installPath[1] !== ':') {
            $installPath = getcwd() . "/$installPath";
        }

        return $installPath;
    }
}
