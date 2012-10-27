<?php


namespace MagentoHackathon\Composer\Magento;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;

class Installer extends \Composer\Installer\LibraryInstaller implements \Composer\Installer\InstallerInterface
{


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

        $this->magentoRootDir = rtrim($composer->getExtra()->get('magento-root-dir'), '/');
        $this->magentoBaseDir = rtrim($composer->getExtra()->get('magento-base-dir'), '/');
        $this->magentoAppDir = rtrim($composer->getExtra()->get('magento-app-dir'), '/');
        $this->magentoCodeDir = rtrim($composer->getExtra()->get('magento-code-dir'), '/');
        $this->magentoDesignDir = rtrim($composer->getExtra()->get('magento-design-dir'), '/');
        $this->magentoLocaleDir = rtrim($composer->getExtra()->get('magento-locale-dir'), '/');
        $this->magentoEtcDir = rtrim($composer->getExtra()->get('magento-etc-dir'), '/');
        $this->magentoMediaDir = rtrim($composer->getExtra()->get('magento-media-dir'), '/');
        $this->magentoSkinDir = rtrim($composer->getExtra()->get('magento-skin-dir'), '/');
        $this->magentoVarDir = rtrim($composer->getExtra()->get('magento-var-dir'), '/');

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

    protected function _isForced()
    {
        // TODO: get forced flag
        return false;
    }

    /**
     * Creates a symlink with lots of error-checking
     *
     * @param $source
     * @param $dest
     * @throws \ErrorException
     */
    protected function _createSymlink($source, $dest)
    {
        if (!file_exists($source)) {
            throw new \ErrorException("$source does not exists");
        }

        if (is_link($dest)) {
            return true;
        }

        if (file_exists($dest)) {
            if ($this->_isForced()) {
                unlink($dest);
            } else {
                throw new \ErrorException("$dest already exists and is not a symlink");
            }
        }

        link($source, $dest);
        if (!is_link($dest)) {
            throw new \ErrorException("could not create symlink $dest");
        }

    }

    /**
     * Similar to apply_modman_file but creates hardlinks instead of using symlinks
     * @param $source
     * @param $dest
     */
    protected function _copyOver($source, $dest)
    {
        if (!file_exists($source)) {
            throw new \ErrorException("$source does not exists");
        }

        if (is_link($dest)) {
            if ($this->_isForced()) {
                unlink($dest);
            } else {
                throw new \ErrorException("$dest already exists");
            }
        }

        copy($source, $dest);
        if (!file_exists($dest)) {
            throw new \ErrorException("could not copy file $dest");
        }

    }


}
