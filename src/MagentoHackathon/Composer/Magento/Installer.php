<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;

/**
 * Composer Magento Installer
 */
class Installer extends LibraryInstaller implements InstallerInterface
{
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
    protected $_source_dir;

    /**
     * @var string
     */
    protected $_deployStrategy = "symlink";

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
     * @param \Composer\Composer $composer
     * @param string $type
     * @throws \ErrorException
     */
    public function __construct(IOInterface $io, Composer $composer, $type = 'magento-module')
    {
        parent::__construct($io, $composer, $type);
        $this->initializeVendorDir();

        $this->annoy( $io );

        $extra = $composer->getPackage()->getExtra();

        if (isset($extra['magento-root-dir'])) {

            $dir = rtrim(trim($extra['magento-root-dir']), '/\\');
            if (!is_dir($dir)) {
                $dir = $this->vendorDir . "/$dir";
            }
            $this->magentoRootDir = new \SplFileInfo($dir);
        }

        if (isset($extra['modman-root-dir'])) {

            $dir = rtrim(trim($extra['modman-root-dir']), '/\\');
            if (!is_dir($dir)) {
                $dir = $this->vendorDir . "/$dir";
            }
            if (!is_dir($dir)) {
                throw new \ErrorException("modman root dir \"{$dir}\" is not valid");
            }
            $this->modmanRootDir = new \SplFileInfo($dir);
        }

        if (isset($extra['magento-deploystrategy'])) {
            $this->_deployStrategy = (string)$extra['magento-deploystrategy'];
        }

        if ((is_null($this->magentoRootDir) || false === $this->magentoRootDir->isDir())
            && $this->_deployStrategy != 'none'
        ) {
            $dir = $this->magentoRootDir instanceof \SplFileInfo ? $this->magentoRootDir->getPathname() : '';
            throw new \ErrorException("magento root dir \"{$dir}\" is not valid");
        }

        if (isset($extra['magento-force'])) {
            $this->isForced = (bool)$extra['magento-force'];
        }

        if (isset($extra['magento-deploystrategy'])) {
            $this->setDeployStrategy((string)$extra['magento-deploystrategy']);
        }

        if (!empty($extra['auto-append-gitignore'])) {
            $this->appendGitIgnore = true;
        }

        if (!empty($extra['path-mapping-translations'])) {
            $this->_pathMappingTranslations = (array)$extra['path-mapping-translations'];
        }

    }

    /**
     * @param string $strategy
     */
    public function setDeployStrategy($strategy)
    {
        $this->_deployStrategy = $strategy;
    }

    /**
     * Returns the strategy class used for deployment
     *
     * @param \Composer\Package\PackageInterface $package
     * @param string $strategy
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function getDeployStrategy(PackageInterface $package, $strategy = null)
    {
        if (null === $strategy) {
            $strategy = $this->_deployStrategy;
        }
        $moduleSpecificDeployStrategys = $this->composer->getPackage()->getExtra();
        if( isset($moduleSpecificDeployStrategys['magento-deploystrategy-overwrite']) ){
            $moduleSpecificDeployStrategys = $moduleSpecificDeployStrategys['magento-deploystrategy-overwrite'];
            if( isset($moduleSpecificDeployStrategys[$package->getName()]) ){
                $strategy = $moduleSpecificDeployStrategys[$package->getName()];
            }
        }
        $targetDir = $this->getTargetDir();
        $sourceDir = $this->getSourceDir($package);
        switch ($strategy) {
            case 'copy':
                $impl = new \MagentoHackathon\Composer\Magento\Deploystrategy\Copy($sourceDir, $targetDir);
                break;
            case 'link':
                $impl = new \MagentoHackathon\Composer\Magento\Deploystrategy\Link($sourceDir, $targetDir);
                break;
            case 'none':
                $impl = new \MagentoHackathon\Composer\Magento\Deploystrategy\None($sourceDir, $targetDir);
                break;
            case 'symlink':
            default:
                $impl = new \MagentoHackathon\Composer\Magento\Deploystrategy\Symlink($sourceDir, $targetDir);
        }
        // Inject isForced setting from extra config
        $impl->setIsForced($this->isForced);
        return $impl;
    }

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     * @return bool
     */
    public function supports($packageType)
    {
        return 'magento-module' === $packageType;
    }

    /**
     * Return Source dir of package
     *
     * @param \Composer\Package\PackageInterface $package
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
        $strategy->deploy();

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
     * @param string $ignoreFile
     */
    public function appendGitIgnore(PackageInterface $package, $ignoreFile)
    {
        $contents = array();
        if(file_exists($ignoreFile)) {
            $contents = file($ignoreFile, FILE_IGNORE_NEW_LINES);
        }

        $additions = array();
        foreach($this->getParser($package)->getMappings() as $map) {
            $dest   = $map[1];
            $ignore = sprintf("%s/%s", basename($this->getTargetDir()), $dest);
            if(!in_array($ignore, $contents)) {
                $additions[] = $ignore;
            }
        }

        if(!empty($additions)) {
            array_unshift($additions, '#' . $package->getName());
            $contents = array_merge($contents, $additions);
            file_put_contents($ignoreFile, implode("\n", $contents));
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
        $targetStrategy->deploy();

        if($this->appendGitIgnore) {
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
     * @return Parser
     * @throws \ErrorException
     */
    public function getParser(PackageInterface $package)
    {
        $extra = $package->getExtra();
        $moduleSpecificMap = $this->composer->getPackage()->getExtra();
        if( isset($moduleSpecificMap['magento-map-overwrite']) ){
            $moduleSpecificMap = $moduleSpecificMap['magento-map-overwrite'];
            if( isset($moduleSpecificMap[$package->getName()]) ){
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
            $parser = new PackageXmlParser($this->getSourceDir($package), $extra['package-xml'], $this->_pathMappingTranslations);
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
}
