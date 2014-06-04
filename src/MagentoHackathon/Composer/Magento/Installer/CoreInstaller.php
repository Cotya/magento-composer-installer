<?php
/**
 * CoreInstaller.php
 */

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use InvalidArgumentException;

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

    const MAGENTO_REMOVE_DEV_FLAG = 'magento-remove-dev';

    const MAGENTO_MAINTANANCE_FLAG = 'maintenance.flag';

    const MAGENTO_CACHE_PATH = 'var/cache';

    const MAGENTO_ROOT_DIR_TMP_SUFFIX = '_tmp';

    const MAGENTO_ROOT_DIR_BACKUP_SUFFIX = '_bkup';

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
            $this->io->write('Skipping core update...');

            return false;
        }
        $tmpDir = $this->magentoRootDir->getPathname() . self::MAGENTO_ROOT_DIR_TMP_SUFFIX;
        $this->filesystem->ensureDirectoryExists($tmpDir);
        $this->originalMagentoRootDir = clone $this->magentoRootDir;
        $this->magentoRootDir = new \SplFileInfo($tmpDir);

        return true;
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
     * Add all the files which are to be deployed
     * to the .gitignore file, if it doesn't
     * exist then create a new one
     *
     * @param PackageInterface $package
     * @param string           $ignoreFile
     */
    public function appendGitIgnore(PackageInterface $package, $ignoreFile)
    {
        parent::appendGitIgnore($package, $ignoreFile);
        $this->prepareMagentoCore();
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
        $persistentFolders = array('media', 'var');
        copy(
            $backupDir . DIRECTORY_SEPARATOR . $this->_magentoLocalXmlPath,
            $rootDir . DIRECTORY_SEPARATOR . $this->_magentoLocalXmlPath
        );
        foreach ($persistentFolders as $folder) {
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
     * toggle magento maintenance flag
     *
     * @param bool $active
     */
    public function toggleMagentoMaintenanceMode($active = false)
    {
        if (($targetDir = $this->getTargetDir()) && !$this->noMaintenanceMode) {
            $flagPath = $targetDir . DIRECTORY_SEPARATOR . self::MAGENTO_MAINTANANCE_FLAG;
            if ($active) {
                $this->io->write("Adding magento maintenance flag...");
                file_put_contents($flagPath, '*');
            } elseif (file_exists($flagPath)) {
                $this->io->write("Removing magento maintenance flag...");
                unlink($flagPath);
            }
        }
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
} 