<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use InvalidArgumentException;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\DeployManager;
use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;
use MagentoHackathon\Composer\Magento\Deploystrategy\Link;
use MagentoHackathon\Composer\Magento\Deploystrategy\None;
use MagentoHackathon\Composer\Magento\Deploystrategy\Symlink;
use MagentoHackathon\Composer\Magento\MapParser;
use MagentoHackathon\Composer\Magento\ModmanParser;
use MagentoHackathon\Composer\Magento\PackageXmlParser;
use MagentoHackathon\Composer\Magento\Parser;
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
     * The base directory of the modman packages
     *
     * @var \SplFileInfo
     */
    protected $modmanRootDir = null;

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
     * @var array Path mapping prefixes that need to be translated (i.e. to
     * use a public directory as the web server root).
     */
    protected $_pathMappingTranslations = array();

    /**
     * Initializes Magento Module installer
     *
     * @param \Composer\IO\IOInterface $io
     * @param \Composer\Composer       $composer
     * @param string                   $type
     *
     * @throws \ErrorException
     */
    public function __construct(IOInterface $io, Composer $composer, $type = 'magento-module')
    {
        parent::__construct($io, $composer, $type);
        $this->initializeVendorDir();

        $this->annoy($io);

        $this->config = new ProjectConfig($composer->getPackage()->getExtra());

        $this->initModmanRootDir();
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

        if ($this->getConfig()->hasPathMappingTranslations()) {
            $this->_pathMappingTranslations = $this->getConfig()->getPathMappingTranslations();
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

    protected function initModmanRootDir() {
        if($this->getConfig()->hasModmanRootDir()) {
            $modmanRootDir = $this->getConfig()->getModmanRootDir();

            if(!is_dir($modmanRootDir)) {
                $modmanRootDir = $this->joinFilePath($this->vendorDir, $modmanRootDir);
            }

            if(!is_dir($modmanRootDir)) {
                throw new \ErrorException(sprintf('modman root dir "%s" is not valid', $modmanRootDir));
            }

            $this->modmanRootDir = new \SplFileInfo($modmanRootDir);
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
     * Returns the strategy class used for deployment
     *
     * @param \Composer\Package\PackageInterface $package
     * @param string                             $strategy
     *
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function getDeployStrategy(PackageInterface $package, $strategy = null)
    {
        if (null === $strategy) {
            $strategy = $this->deployStrategy;
        }

        if ($this->getConfig()->hasDeployStrategyOverwrite()) {
            $moduleSpecificDeployStrategys = $this->getConfig()->getDeployStrategyOverwrite();

            if (isset($moduleSpecificDeployStrategys[$package->getName()])) {
                $strategy = $moduleSpecificDeployStrategys[$package->getName()];
            }
        }

        $targetDir = $this->getTargetDir();
        $sourceDir = $this->getSourceDir($package);
        switch ($strategy) {
            case 'copy':
                $impl = new Copy($sourceDir, $targetDir);
                break;
            case 'link':
                $impl = new Link($sourceDir, $targetDir);
                break;
            case 'none':
                $impl = new None($sourceDir, $targetDir);
                break;
            case 'symlink':
            default:
                $impl = new Symlink($sourceDir, $targetDir);
        }
        // Inject isForced setting from extra config
        $impl->setIsForced($this->isForced);
        $impl->setIgnoredMappings($this->getModuleSpecificDeployIgnores($package));

        return $impl;
    }
    
    protected function getModuleSpecificDeployIgnores($package)
    {

        $moduleSpecificDeployIgnores = array();
        if ($this->getConfig()->hasMagentoDeployIgnore()) {
            $magentoDeployIgnore = $this->getConfig()->getMagentoDeployIgnore();
            if (isset($magentoDeployIgnore['*'])) {
                $moduleSpecificDeployIgnores = $magentoDeployIgnore['*'];
            }
            if (isset($magentoDeployIgnore[$package->getName()])) {
                $moduleSpecificDeployIgnores = array_merge(
                    $moduleSpecificDeployIgnores,
                    $magentoDeployIgnore[$package->getName()]
                );
            }
        }
        return $moduleSpecificDeployIgnores;
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

        $strategy = $this->getDeployStrategy($package);
        $strategy->setMappings($this->getParser($package)->getMappings());
        $deployManagerEntry = new Entry();
        $deployManagerEntry->setPackageName($package->getName());
        $deployManagerEntry->setDeployStrategy($strategy);
        $this->deployManager->addPackage($deployManagerEntry);
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
        $initialStrategy = $this->getDeployStrategy($initial);
        $initialStrategy->setMappings($this->getParser($initial)->getMappings());
        $initialStrategy->clean();

        parent::update($repo, $initial, $target);

        $targetStrategy = $this->getDeployStrategy($target);
        $targetStrategy->setMappings($this->getParser($target)->getMappings());
        $deployManagerEntry = new Entry();
        $deployManagerEntry->setPackageName($target->getName());
        $deployManagerEntry->setDeployStrategy($targetStrategy);
        $this->deployManager->addPackage($deployManagerEntry);
    }

    /**
     * Uninstalls specific package.
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $strategy = $this->getDeployStrategy($package);
        $strategy->setMappings($this->getParser($package)->getMappings());
        $strategy->clean();

        parent::uninstall($repo, $package);
    }

    /**
     * Returns the modman parser for the vendor dir
     *
     * @param PackageInterface $package
     *
     * @return Parser
     * @throws \ErrorException
     */
    public function getParser(PackageInterface $package)
    {
        $extra = $package->getExtra();
        $moduleSpecificMap = $this->composer->getPackage()->getExtra();
        if (isset($moduleSpecificMap['magento-map-overwrite'])) {
            $moduleSpecificMap = $this->transformArrayKeysToLowerCase($moduleSpecificMap['magento-map-overwrite']);
            if (isset($moduleSpecificMap[$package->getName()])) {
                $map = $moduleSpecificMap[$package->getName()];
            }
        }

        if (isset($map)) {
            $parser = new MapParser($map, $this->_pathMappingTranslations);

            return $parser;
        } elseif (isset($extra['map'])) {
            $parser = new MapParser($extra['map'], $this->_pathMappingTranslations);

            return $parser;
        } elseif (isset($extra['package-xml'])) {
            $parser = new PackageXmlParser(
                $this->getSourceDir($package), $extra['package-xml'], $this->_pathMappingTranslations
            );

            return $parser;
        } elseif (file_exists($this->getSourceDir($package) . '/modman')) {
            $parser = new ModmanParser($this->getSourceDir($package), $this->_pathMappingTranslations);

            return $parser;
        } else {
            throw new \ErrorException('Unable to find deploy strategy for module: no known mapping');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {

        if (!is_null($this->modmanRootDir) && true === $this->modmanRootDir->isDir()) {
            $targetDir = $package->getTargetDir();
            if (!$targetDir) {
                list($vendor, $targetDir) = explode('/', $package->getPrettyName());
            }
            $installPath = $this->modmanRootDir . '/' . $targetDir;
        } else {
            $installPath = parent::getInstallPath($package);
        }

        // Make install path absolute. This is needed in the symlink deploy strategies.
        if (DIRECTORY_SEPARATOR !== $installPath[0] && $installPath[1] !== ':') {
            $installPath = getcwd() . "/$installPath";
        }

        return $installPath;
    }

    public function transformArrayKeysToLowerCase($array)
    {
        $arrayNew = array();
        foreach ($array as $key => $value) {
            $arrayNew[strtolower($key)] = $value;
        }

        return $arrayNew;
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
     * joinFilePath
     *
     * joins 2 Filepaths and replaces the Directory Separators
     * with the Systems Directory Separator
     *
     * @param $path1
     * @param $path2
     *
     * @return string
     */
    public function joinFilePath($path1, $path2)
    {
        $prefix = $this->startsWithDs($path1) ? DIRECTORY_SEPARATOR : '';
        $suffix = $this->endsWithDs($path2) ? DIRECTORY_SEPARATOR : '';

        return $prefix . implode(
            DIRECTORY_SEPARATOR,
            array_merge(
                preg_split('/\\\|\//', $path1, null, PREG_SPLIT_NO_EMPTY),
                preg_split('/\\\|\//', $path2, null, PREG_SPLIT_NO_EMPTY)
            )
        ) . $suffix;
    }

    /**
     * startsWithDs
     *
     * @param $path
     *
     * @return bool
     */
    protected function startsWithDs($path)
    {
        return strrpos($path, '/', -strlen($path)) !== FALSE
            || strrpos($path, '\\', -strlen($path)) !== FALSE;
    }

    /**
     * endsWithDs
     *
     * @param $path
     *
     * @return bool
     */
    protected function endsWithDs($path)
    {
        return strpos($path, '/', strlen($path) - 1) !== FALSE
            || strpos($path, '\\', strlen($path) - 1) !== FALSE;
    }

    /**
     * print Debug Message
     *
     * @param $message
     */
    protected function writeDebug($message, $varDump = null)
    {
        if ($this->io->isDebug()) {
            $this->io->write($message);

            if (!is_null($varDump)) {
                var_dump($varDump);
            }
        }
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
}
