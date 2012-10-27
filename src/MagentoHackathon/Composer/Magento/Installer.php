<?php


namespace MagentoHackathon\Composer\Magento;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;

class Installer extends LibraryInstaller implements InstallerInterface
{

    protected $_magentoRootDir = null;
    protected $_isForced = false;
    protected $_target_dir;

    /**
     * Initializes Magento Module installer.
     *
     * @param \Composer\IO\IOInterface $io
     * @param \Composer\Composer $composer
     * @param string $type
     */
    public function __construct(IOInterface $io, Composer $composer, $type = 'magento-module')
    {
        parent::__construct($io, $composer, $type);

        $extra = $composer->getPackage()->getExtra();

        $this->_target_dir = $composer->getPackage()->getTargetDir();

        if (isset($extra['magento-root-dir'])) {
            $this->magentoRootDir = trim($extra['magento-root-dir']);
        }

        if (!is_dir($this->magentoRootDir) || empty($this->magentoRootDir)) {
            throw new \ErrorException("magento root dir is not valid");
        }

        $this->_magentoRootDir = $extra['magento-root-dir'];
        $this->_isForced = $extra['magento-force'];
    }

    /**
     * @return \MagentoHackathon\Composer\Magento\Depolystrategy\DeploystrategyAbstract
     */
    public function getDeployStrategy()
    {
        return new \MagentoHackathon\Composer\Magento\Depolystrategy\Symlink($this->_magentoRootDir, $this->_target_dir);
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
     * Installs specific package.
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
     * Updates specific package.
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $initial already installed package version
     * @param PackageInterface             $target  updated version
     *
     * @throws InvalidArgumentException if $from package is not installed
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
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

    public function getParser()
    {
        $parser = new ModmanParser($this->vendorDir);
        return $parser;
    }


}