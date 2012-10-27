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
     * @var string
     */
    protected $magentoRootDir = null;

    /**
     * @var bool
     */
    protected $_isForced = false;

    /**
     * The module's base directory
     *
     * @var string
     */
    protected $_source_dir;

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

        $this->_source_dir = $this->vendorDir.DIRECTORY_SEPARATOR.$composer->getPackage()->getPrettyName();

        if (isset($extra['magento-root-dir'])) {
            $this->magentoRootDir = trim($extra['magento-root-dir']);
        }

        if (!is_dir($this->_source_dir) || empty($this->magentoRootDir)) {
            throw new \ErrorException("magento root dir is not valid");
        };

        if ( isset( $extra['magento-force'] ) ) {
            $this->_isForced = $extra['magento-force'];
        }

    }

    /**
     * Returns the strategy class used for deployment
     *
     * @return \MagentoHackathon\Composer\Magento\Depolystrategy\DeploystrategyAbstract
     */
    public function getDeployStrategy()
    {
        return new \MagentoHackathon\Composer\Magento\Depolystrategy\Symlink($this->magentoRootDir, $this->_target_dir);
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
     * Installs specific package
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $strategy = $this->getDeployStrategy();
        $strategy->setMappings($this->getParser()->getMappings());
        $strategy->deploy();
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
        die('OK');
        $this->install($repo, $initial, $target);
    }

    /**
     * Uninstalls specific package.
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::uninstall($repo, $package);
    }

    /**
     * Returns the modman parser for the vendor dir
     *
     * @return ModmanParser
     */
    public function getParser()
    {
        $parser = new ModmanParser($this->_source_dir);
        return $parser;
    }
}
