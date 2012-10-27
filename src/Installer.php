<?php


namespace MagentoHackathon\Composer\Magento;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;

class Installer extends \Composer\Installer\LibraryInstaller implements \Composer\Installer\InstallerInterface{


    protected $magentoRootDir;
    protected $magentoBaseDir;
    protected $magentoAppDir;
    protected $magentoCodeDir;
    protected $magentoDesignDir;
    protected $magentoLocaleDir;
    protected $magentoEtcDir;
    protected $magentoMediaDir;
    protected $magentoSkinDir;
    protected $magentoVarDir;


    /**
     * Initializes Magento Module installer.
     *
     * @param IOInterface $io
     * @param Composer    $composer
     * @param string      $type
     */
    public function __construct(IOInterface $io, Composer $composer, $type = 'magento-module')
    {
        parent::__construct($io, $composer, $type);

        $this->magentoRootDir = rtrim($composer->getConfig()->get('magento-root-dir'), '/');
        $this->magentoBaseDir = rtrim($composer->getConfig()->get('magento-base-dir'), '/');
        $this->magentoAppDir = rtrim($composer->getConfig()->get('magento-app-dir'), '/');
        $this->magentoCodeDir = rtrim($composer->getConfig()->get('magento-code-dir'), '/');
        $this->magentoDesignDir = rtrim($composer->getConfig()->get('magento-design-dir'), '/');
        $this->magentoLocaleDir = rtrim($composer->getConfig()->get('magento-locale-dir'), '/');
        $this->magentoEtcDir = rtrim($composer->getConfig()->get('magento-etc-dir'), '/');
        $this->magentoMediaDir = rtrim($composer->getConfig()->get('magento-media-dir'), '/');
        $this->magentoSkinDir = rtrim($composer->getConfig()->get('magento-skin-dir'), '/');
        $this->magentoVarDir = rtrim($composer->getConfig()->get('magento-var-dir'), '/');

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
        parent::install($repo, $package);
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
        parent::update($repo, $initial, $target);
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
}
