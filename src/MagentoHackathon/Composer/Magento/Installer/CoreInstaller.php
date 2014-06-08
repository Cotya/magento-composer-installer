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
     * Directories that persist between Updates
     *
     * @var array
     */
    protected $persistentDirs
        = array(
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
        if (!$this->preInstallMagentoCore()) {
            $this->io->write('<error>Failed to process Pre Install operations. Aborting Core Install</error>');

            return;
        }

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
        if (false === $this->preUpdateMagentoCore()) {
            $this->io->write('<error>Failed to process Pre Update operations. Aborting Core Update</error>');

            return;
        }

        parent::update($repo, $initial, $target);

        $this->postUpdateMagentoCore();
    }

    /**
     * prepare Magento Core Update
     *
     * @return bool
     */
    protected function preUpdateMagentoCore()
    {
        if (!$this->io->askConfirmation(
            '<info>Are you sure you want to manipulate the Magento core installation</info> [<comment>Y,n</comment>]? ',
            true
        )
        ) {
            $this->io->write('<info>Skipping core update...</info>');

            return false;
        }
        $tmpDir = $this->getTmpDir();
        $this->filesystem->ensureDirectoryExists($tmpDir);
        $this->originalMagentoRootDir = clone $this->magentoRootDir;
        $this->magentoRootDir = new \SplFileInfo($tmpDir);

        return true;
    }

    protected function getTmpDir()
    {
        return $this->magentoRootDir->getPathname() . self::MAGENTO_ROOT_DIR_TMP_SUFFIX;
    }

    protected function getBkpDir()
    {
        return $this->magentoRootDir->getPathname() . self::MAGENTO_ROOT_DIR_BACKUP_SUFFIX;
    }

    /**
     * Install Magento core
     *
     * @internal param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
     * @internal param \Composer\Package\PackageInterface $package package instance
     *
     * @return bool
     */
    protected function preInstallMagentoCore()
    {
        if (!$this->io->askConfirmation(
            '<info>Are you sure you want to install the Magento core?</info><error>Attention: Your Magento root dir will be cleared in the process!</error> [<comment>Y,n</comment>] ',
            true
        )
        ) {
            $this->io->write('Skipping core installation...');

            return false;
        }
        $this->clearRootDir();

        return true;
    }

    /**
     * process post core update tasks
     */
    protected function postUpdateMagentoCore()
    {
        $tmpDir = $this->magentoRootDir->getPathname();
        $backupDir = $this->originalMagentoRootDir->getPathname() . self::MAGENTO_ROOT_DIR_BACKUP_SUFFIX;
        $this->backupMagentoRootDir = new \SplFileInfo($backupDir);

        $origRootDir = $this->originalMagentoRootDir->getPathName();
        $this->filesystem->rename($origRootDir, $backupDir);
        $this->filesystem->rename($tmpDir, $origRootDir);
        $this->magentoRootDir = clone $this->originalMagentoRootDir;

        $this->prepareMagentoCore();
        $this->cleanupPostUpdateMagentoCore();
    }

    /**
     * prepare Core
     */
    public function prepareMagentoCore()
    {
        $this->setMagentoPermissions();
        $this->redeployProject();
    }

    protected function clearRootDir()
    {
        $this->filesystem->removeDirectory($this->magentoRootDir->getPathname());
        $this->filesystem->ensureDirectoryExists($this->magentoRootDir->getPathname());
    }

    /**
     * clean up after core update
     */
    protected function cleanupPostUpdateMagentoCore()
    {
        $rootDir = $this->magentoRootDir->getPathname();
        $backupDir = $this->backupMagentoRootDir->getPathname();

        if (file_exists($backupDir . DIRECTORY_SEPARATOR . self::MAGENTO_LOCAL_XML_PATH)) {
            copy(
                $backupDir . DIRECTORY_SEPARATOR . self::MAGENTO_LOCAL_XML_PATH,
                $rootDir . DIRECTORY_SEPARATOR . self::MAGENTO_LOCAL_XML_PATH
            );
        }
        foreach ($this->persistentDirs as $folder) {
            $this->filesystem->removeDirectory($rootDir . DIRECTORY_SEPARATOR . $folder);
            $this->filesystem->rename(
                $backupDir . DIRECTORY_SEPARATOR . $folder, $rootDir . DIRECTORY_SEPARATOR . $folder
            );
        }
        if ($this->io->ask('Remove root backup? [Y,n] ', true)) {
            $this->filesystem->removeDirectory($backupDir);
            $this->io->write('Removed root backup!', true);
        } else {
            $this->io->write('Skipping backup removal...', true);
        }
        $this->clearMagentoCache();
    }

    /**
     * clear Magento Cache
     */
    public function clearMagentoCache()
    {
        if (($targetDir = $this->getTargetDir()) && !$this->keepMagentoCache) {
            $magentoCachePath = $targetDir . DIRECTORY_SEPARATOR . self::MAGENTO_CACHE_PATH;
            if ($this->filesystem->removeDirectory($magentoCachePath)) {
                $this->io->write('Magento cache cleared');
            }
        }
    }

    /**
     * some directories have to be writable for the server
     */
    protected function setMagentoPermissions()
    {
        foreach ($this->magentoWritableDirs as $dir) {
            if (!file_exists($this->getTargetDir() . DIRECTORY_SEPARATOR . $dir)) {
                $this->filesystem->ensureDirectoryExists($this->getTargetDir() . DIRECTORY_SEPARATOR . $dir);
            }
            $this->setPermissions($this->getTargetDir() . DIRECTORY_SEPARATOR . $dir, 0777, 0666);
        }
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
        return new Copy($this->getSourceDir($package), $this->getTargetDir());
    }

    /**
     * @param PackageInterface $package
     *
     * @throws \ErrorException
     */
    protected function addEntryToDeployManager(PackageInterface $package) {
        $targetStrategy = $this->getDeployStrategy($package);
        $targetStrategy->setMappings($this->getParser($package)->getMappings());
        $deployManagerEntry = new Entry();
        $deployManagerEntry->setPackageName($package->getName());
        $deployManagerEntry->setDeployStrategy($targetStrategy);
        $deployManagerEntry->getDeployStrategy()->deploy();
    }

    /**
     * @throws \ErrorException
     */
    protected function redeployProject()
    {
        $ioInterface = $this->io;
        // init repos
        $composer = $this->composer;
        $installedRepo = $composer->getRepositoryManager()->getLocalRepository();

        $im = $composer->getInstallationManager();

        /* @var ModuleInstaller $moduleInstaller */
        $moduleInstaller = $im->getInstaller("magento-module");

        /* @var PackageInterface $package */
        foreach ($installedRepo->getPackages() as $package) {

            if ($ioInterface->isVerbose()) {
                $ioInterface->write($package->getName());
                $ioInterface->write($package->getType());
            }

            if ($package->getType() != "magento-module") {
                continue;
            }
            if ($ioInterface->isVerbose()) {
                $ioInterface->write("package {$package->getName()} recognized");
            }

            $strategy = $moduleInstaller->getDeployStrategy($package);
            if ($ioInterface->getOption('verbose')) {
                $ioInterface->write("used " . get_class($strategy) . " as deploy strategy");
            }
            $strategy->setMappings($moduleInstaller->getParser($package)->getMappings());

            $strategy->deploy();
        }
    }
} 
