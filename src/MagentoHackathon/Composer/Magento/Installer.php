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
     * If set overrides existing files
     *
     * @todo This is not yet implemented
     * @var bool
     */
    protected $isForced = false;

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

        if (is_null($this->magentoRootDir) || false === $this->magentoRootDir->isDir()) {
            $dir = $this->magentoRootDir instanceof \SplFileInfo ? $this->magentoRootDir->getPathname() : '';
            throw new \ErrorException("magento root dir \"{$dir}\" is not valid");
        }
        ;

        if (isset($extra['magento-force'])) {
            $this->isForced = (bool)$extra['magento-force'];
        }
    }

    /**
     * Returns the strategy class used for deployment
     *
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function getDeployStrategy(PackageInterface $package)
    {
        return new \MagentoHackathon\Composer\Magento\Deploystrategy\Symlink($this->magentoRootDir->getPathname(), $this->getSourceDir($package));
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

        $strategy = $this->getDeployStrategy($package);
        $strategy->setMappings($this->getParser($package)->getMappings());
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
        $this->getDeployStrategy()->clean($this->magentoRootDir);
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
     * @param PackageInterface $package
     * @return ModmanParser
     */
    public function getParser(PackageInterface $package)
    {
        $parser = new ModmanParser($this->getSourceDir($package));
        return $parser;
    }
}
