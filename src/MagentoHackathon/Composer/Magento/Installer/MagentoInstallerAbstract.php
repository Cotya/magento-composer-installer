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
     * If set the deployed files will be added to the projects .gitignore file
     *
     * @var bool
     */
    protected $appendGitIgnore = false;

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

        $this->initMagentoRootDir();
        $this->initModmanRootDir();

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

        $this->appendGitIgnore = $this->getConfig()->hasAutoAppendGitignore();

        if ($this->getConfig()->hasPathMappingTranslations()) {
            $this->_pathMappingTranslations = $this->getConfig()->getPathMappingTranslations();
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
        $impl->setIgnoredMappings($moduleSpecificDeployIgnores);

        return $impl;
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

        if ($this->appendGitIgnore) {
            $this->appendGitIgnore($package, $this->getGitIgnoreFileLocation());
        }
    }

    /**
     * Get .gitignore file location
     *
     * @return string
     */
    public function getGitIgnoreFileLocation()
    {
        $ignoreFile = $this->magentoRootDir->getPathname() . '/.gitignore';

        return $ignoreFile;
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
        $contents = array();
        if (file_exists($ignoreFile)) {
            $contents = file($ignoreFile, FILE_IGNORE_NEW_LINES);
        }

        $additions = array();
        foreach ($this->getParser($package)->getMappings() as $map) {
            $dest = $map[1];
            $ignore = sprintf("/%s", $dest);
            $ignore = str_replace('/./', '/', $ignore);
            $ignore = str_replace('//', '/', $ignore);
            $ignore = rtrim($ignore, '/');
            if (!in_array($ignore, $contents)) {
                $ignoredMappings = $this->getDeployStrategy($package)->getIgnoredMappings();
                if (in_array($ignore, $ignoredMappings)) {
                    continue;
                }

                $additions[] = $ignore;
            }
        }

        if (!empty($additions)) {
            array_unshift($additions, '#' . $package->getName());
            $contents = array_merge($contents, $additions);
            file_put_contents($ignoreFile, implode("\n", $contents));
        }
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

        if ($this->appendGitIgnore) {
            $this->appendGitIgnore($target, $this->getGitIgnoreFileLocation());
        }
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
}
