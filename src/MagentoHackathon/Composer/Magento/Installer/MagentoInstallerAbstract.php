<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use InvalidArgumentException;
use MagentoHackathon\Composer\Magento\DeployManager;
use MagentoHackathon\Composer\Magento\Factory\EntryFactory;
use MagentoHackathon\Composer\Magento\ProjectConfig;

/**
 * Composer Magento Installer
 */
abstract class MagentoInstallerAbstract extends LibraryInstaller implements InstallerInterface
{
    /**
     * the Default base directory of the magento installation
     */
    const DEFAULT_MAGENTO_ROOT_DIR = 'root';

    /**
     * The base directory of the magento installation
     *
     * @var \SplFileInfo
     */
    protected $magentoRootDir = null;

    /**
     * If set overrides existing files
     *
     * @var bool
     */
    protected $isForced = false;

    /**
     * The module's base directory
     *
     * @var string
     */
    protected $sourceDir;

    /**
     * @var string
     */
    protected $_deployStrategy = 'symlink';

    const MAGENTO_REMOVE_DEV_FLAG = 'magento-remove-dev';
    const MAGENTO_MAINTANANCE_FLAG = 'maintenance.flag';
    const MAGENTO_CACHE_PATH = 'var/cache';
    const MAGENTO_ROOT_DIR_TMP_SUFFIX = '_tmp';
    const MAGENTO_ROOT_DIR_BACKUP_SUFFIX = '_bkup';

    protected $noMaintenanceMode = false;

    /**
     * @var \SplFileInfo
     */
    protected $originalMagentoRootDir = null;

    /**
     * @var \SplFileInfo
     */
    protected $backupMagentoRootDir = null;
    protected $removeMagentoDev = false;
    protected $keepMagentoCache = false;
    protected $_magentoLocalXmlPath = 'app/etc/local.xml';
    protected $_defaultEnvFilePaths
        = array(
            'app/etc/local.xml'
        );
    protected $_magentoDevDir = 'dev';
    protected $_magentoWritableDirs
        = array(
            'app/etc',
            'media',
            'var'
        );
    protected $deployStrategy = 'symlink';

    /**
     * @var DeployManager
     */
    protected $deployManager;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var EntryFactory
     */
    protected $entryFactory;

    /**
     * Initializes Magento Module installer
     *
     * @param \Composer\IO\IOInterface $io
     * @param \Composer\Composer $composer
     * @param EntryFactory $entryFactory
     * @param string $type
     *
     * @throws \ErrorException
     */
    public function __construct(
        IOInterface $io,
        Composer $composer,
        EntryFactory $entryFactory,
        $type = 'magento-module'
    ) {
        parent::__construct($io, $composer, $type);
        $this->initializeVendorDir();

        $this->annoy($io);

        $this->config = new ProjectConfig($composer->getPackage()->getExtra());
        $this->entryFactory = $entryFactory;
        $this->initMagentoRootDir();

        if ($this->getConfig()->hasDeployStrategy()) {
            $this->deployStrategy = $this->getConfig()->getDeployStrategy();
        }

        if ((is_null($this->magentoRootDir) || false === $this->magentoRootDir->isDir())
            && $this->deployStrategy != 'none'
        ) {
            $dir = $this->magentoRootDir instanceof \SplFileInfo ? $this->magentoRootDir->getPathname() : '';
            $io->write("<error>magento root dir \"{$dir}\" is not valid</error>", true);
            $io->write(
                '<comment>You need to set an existing path for "magento-root-dir" in your composer.json</comment>', true
            );
            $io->write(
                '<comment>For more information please read about the "Usage" in the README of the installer Package</comment>',
                true
            );
            throw new \ErrorException("magento root dir \"{$dir}\" is not valid");
        }

        if ($this->getConfig()->hasMagentoForce()) {
            $this->isForced = $this->getConfig()->getMagentoForce();
        }

        if ($this->getConfig()->hasDeployStrategy()) {
            $this->setDeployStrategy($this->getConfig()->getDeployStrategy());
        }
    }

    protected function initMagentoRootDir() {
        if (false === $this->getConfig()->hasMagentoRootDir()) {
            $this->getConfig()->setMagentoRootDir(
                $this->io->ask(
                    sprintf('please define your magento root dir [%s]', ProjectConfig::DEFAULT_MAGENTO_ROOT_DIR),
                    ProjectConfig::DEFAULT_MAGENTO_ROOT_DIR
                )
            );
        }

        $this->magentoRootDir = new \SplFileInfo($this->getConfig()->getMagentoRootDir());

        if (
            !is_dir($this->getConfig()->getMagentoRootDir())
            && $this->io->askConfirmation(
                'magento root dir "' . $this->getConfig()->getMagentoRootDir() . '" missing! create now? [Y,n] '
            )
        ) {
            $this->filesystem->ensureDirectoryExists($this->magentoRootDir);
            $this->io->write('magento root dir "' . $this->getConfig()->getMagentoRootDir() . '" created');
        }

        if (!is_dir($this->getConfig()->getMagentoRootDir())) {
            $dir = $this->joinFilePath($this->vendorDir, $this->getConfig()->getMagentoRootDir());
            $this->magentoRootDir = new \SplFileInfo($dir);
        }
    }



    /**
     * @param DeployManager $deployManager
     */
    public function setDeployManager(DeployManager $deployManager)
    {
        $this->deployManager = $deployManager;
    }

    /**
     * @param ProjectConfig $config
     */
    public function setConfig(ProjectConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return ProjectConfig
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return DeployManager
     */
    public function getDeployManager()
    {
        return $this->deployManager;
    }

    /**
     * @param string $strategy
     */
    public function setDeployStrategy($strategy)
    {
        $this->deployStrategy = $strategy;
    }

    /**
     * Return Source dir of package
     *
     * @param \Composer\Package\PackageInterface $package
     *
     * @return string
     */
    protected function getSourceDir(PackageInterface $package)
    {
        $this->filesystem->ensureDirectoryExists($this->vendorDir);

        return $this->getInstallPath($package);
    }

    /**
     * Return the absolute target directory path for package installation
     *
     * @return string
     */
    protected function getTargetDir()
    {
        $targetDir = realpath($this->magentoRootDir->getPathname());

        return $targetDir;
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
        $entry = $this->entryFactory->make($package, $this->getSourceDir($package));
        $this->deployManager->addPackage($entry);
    }

    /**
     * set permissions recursively
     *
     * @param string $path     Path to set permissions for
     * @param int    $dirmode  Permissions to be set for directories
     * @param int    $filemode Permissions to be set for files
     */
    protected function setPermissions($path, $dirmode, $filemode)
    {
        if (is_dir($path)) {
            if (!@chmod($path, $dirmode)) {
                $this->io->write(
                    'Failed to set permissions "%s" for directory "%s"', decoct($dirmode), $path
                );
            }
            $dh = opendir($path);
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') { // skip self and parent pointing directories
                    $fullpath = $path . '/' . $file;
                    $this->setPermissions($fullpath, $dirmode, $filemode);
                }
            }
            closedir($dh);
        } elseif (is_file($path)) {
            if (false == !@chmod($path, $filemode)) {
                $this->io->write(
                    'Failed to set permissions "%s" for file "%s"', decoct($filemode), $path
                );
            }
        }
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
        $entry = $this->entryFactory->make($initial, $this->getSourceDir($initial));
        $entry->getDeployStrategy()->clean();

        parent::update($repo, $initial, $target);

        $entry = $this->entryFactory->make($target, $this->getSourceDir($target));
        $this->deployManager->addPackage($entry);
    }

    /**
     * Uninstalls specific package.
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $entry = $this->entryFactory->make($package, $this->getSourceDir($package));
        $entry->getDeployStrategy()->clean();

        parent::uninstall($repo, $package);
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {

        $installPath = parent::getInstallPath($package);

        // Make install path absolute. This is needed in the symlink deploy strategies.
        if (DIRECTORY_SEPARATOR !== $installPath[0] && $installPath[1] !== ':') {
            $installPath = getcwd() . "/$installPath";
        }

        return $installPath;
    }

    /**
     * this function is for annoying people with messages.
     *
     * First usage: get people to vote about the future release of composer so later I can say "you wanted it this way"
     *
     * @param IOInterface $io
     */
    public function annoy(IOInterface $io)
    {

        /**
         * No <error> in future, as some people look for error lines inside of CI Applications, which annoys them
         */
        /*
        $io->write('<comment> time for voting about the future of the #magento #composer installer. </comment>', true);
        $io->write('<comment> https://github.com/magento-hackathon/magento-composer-installer/blob/discussion-master/Milestone/2/index.md </comment>', true);
        $io->write('<error> For the case you don\'t vote, I will ignore your problems till iam finished with the resulting release. </error>', true);
         *
         **/
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
    protected function joinPath($path1, $path2, $delimiter, $prependDelimiter = false, $additionalPrefix = '')
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
    protected function joinFilePath($path1, $path2)
    {
        return $this->joinPath($path1, $path2, DIRECTORY_SEPARATOR, true);
    }
}
