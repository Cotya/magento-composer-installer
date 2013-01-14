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
     * @todo This is not yet implemented
     * @var bool
     */
    protected $isForced = false;

    /**
     * If set the package will not be deployed (with any DeployStrategy)
     * Using a modman-root-dir is not supported yet but the modman-DeployStrategy so you might want to use the normal
     * modman script for this
     *
     * @var bool
     */
    protected $skipPackageDeployment = false;

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

        $extra = $composer->getPackage()->getExtra();

        if (isset($extra['magento-root-dir'])) {

            $dir = rtrim(trim($extra['magento-root-dir']), DIRECTORY_SEPARATOR);
            if (!is_dir($dir)) {
                $dir = $this->vendorDir . DIRECTORY_SEPARATOR . $dir;
            }
            $this->magentoRootDir = new \SplFileInfo($dir);
        }

        if (isset($extra['modman-root-dir'])) {

            $dir = rtrim(trim($extra['modman-root-dir']), DIRECTORY_SEPARATOR);
            if (!is_dir($dir)) {
                $dir = $this->vendorDir . DIRECTORY_SEPARATOR . $dir;
            }
            if (!is_dir($dir)) {
                throw new \ErrorException("modman root dir \"{$dir}\" is not valid");
            }
            $this->modmanRootDir = new \SplFileInfo($dir);
        }

        if (is_null($this->magentoRootDir) || false === $this->magentoRootDir->isDir()) {
            $dir = $this->magentoRootDir instanceof \SplFileInfo ? $this->magentoRootDir->getPathname() : '';
            throw new \ErrorException("magento root dir \"{$dir}\" is not valid");
        }

        // Do not use force unless you have lots of time to fix things. Untested!
        if (isset($extra['magento-force'])) {
            $this->isForced = (bool)$extra['magento-force'];
        }

        if (isset($extra['magento-deploystrategy'])) {
            $this->setDeployStrategy((string)$extra['magento-deploystrategy']);
        }

        if (!empty($extra['skip-package-deployment'])) {
            $this->skipPackageDeployment = true;
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
        switch ($strategy) {
            case 'copy':
                $impl = new \MagentoHackathon\Composer\Magento\Deploystrategy\Copy($this->magentoRootDir->getPathname(), $this->getSourceDir($package));
                break;
            case 'link':
                $impl = new \MagentoHackathon\Composer\Magento\Deploystrategy\Link($this->magentoRootDir->getPathname(), $this->getSourceDir($package));
                break;
            case 'symlink':
            default:
                $impl = new \MagentoHackathon\Composer\Magento\Deploystrategy\Symlink($this->magentoRootDir->getPathname(), $this->getSourceDir($package));
        }
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
     * Installs specific package
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);

        if (!$this->skipPackageDeployment) {
            $strategy = $this->getDeployStrategy($package);
            $strategy->setMappings($this->getParser($package)->getMappings());
            $strategy->deploy();
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

        if (!$this->skipPackageDeployment) {
            $initialStrategy = $this->getDeployStrategy($initial);
            $initialStrategy->setMappings($this->getParser($initial)->getMappings());
            $initialStrategy->clean();
        }

        parent::update($repo, $initial, $target);

        if (!$this->skipPackageDeployment) {
            $targetStrategy = $this->getDeployStrategy($target);
            $targetStrategy->setMappings($this->getParser($target)->getMappings());
            $targetStrategy->deploy();
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
        if (!$this->skipPackageDeployment) {
            $strategy = $this->getDeployStrategy($package);
            $strategy->setMappings($this->getParser($package)->getMappings());
            $strategy->clean();
        }

        parent::uninstall($repo, $package);
    }

    /**
     * Returns the modman parser for the vendor dir
     *
     * @param PackageInterface $package
     * @return Parser
     */
    public function getParser(PackageInterface $package)
    {
        $extra = $package->getExtra();

        if (isset($extra['map'])) {
            $parser = new MapParser($extra['map']);
            return $parser;
        } elseif (isset($extra['package-xml'])) {
            $parser = new PackageXmlParser($this->getSourceDir($package), $extra['package-xml']);
            return $parser;
        } elseif (file_exists($this->getSourceDir($package) . DIRECTORY_SEPARATOR . 'modman')) {
            $parser = new ModmanParser($this->getSourceDir($package));
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

        return $installPath;
    }
}
