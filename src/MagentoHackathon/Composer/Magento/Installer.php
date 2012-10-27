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

        $this->magentoRootDir = trim($composer->getExtra()->get('magento-root-dir'), '/');
        $this->magentoCodePool =trim($composer->getExtra()->get());
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
        $mapping = $this->getMapping();
        foreach ($mapping AS $source => $dest) {
            $this->_createSymlink($source, $dest);
        }
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
        $this->_cleanSymlinks($this->_getModuleDir());
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

    protected function _isForced()
    {
        // TODO: get forced flag from config
        return false;
    }

    protected function _cleanSymlinks($path)
    {
        $mapping = $this->getMappings();

        foreach (glob($path) AS $file) {
            if (is_dir($file)) {
                $this->_cleanSymlinks($file);
            } elseif (is_link($file)) {
                if (linkinfo($file) == -1) {
                    // Symlink is dead, remove it
                    unlink($file);
                } else {
                    $cleanFile = substr($file, strlen($this->_getModuleDir()));
                    if (!in_array($cleanFile, $path)) {
                        // Remove symlinks which are not mapped
                        unlink($file);
                    }

                }
            }
        }
    }

    protected function _getModuleDir()
    {
        return $this->magentoRootDir; // TODO
    }

    protected function _getSourceDir()
    {
        return "MHH???";
    }

    /**
     * Creates a symlink with lots of error-checking
     *
     * @param $source
     * @param $dest
     * @throws \ErrorException
     * @todo implement file to dir modman target, e.g. Namespace_Module.csv => app/locale/de_DE/
     * @todo implement glob to dir mapping target, e.g. code/* => app/code/local/
     */
    protected function _createSymlink($source, $dest)
    {
        if (!file_exists($this->_getSourceDir() . DIRECTORY_SEPARATOR . $source)) {
            throw new \ErrorException("$source does not exists");
        }

        if (is_link($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest)) {
            return true;
        }

        if (file_exists($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest)) {
            if ($this->_isForced()) {
                unlink($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest);
            } else {
                throw new \ErrorException("$dest already exists and is not a symlink");
            }
        }

        link($this->_getSourceDir() . DIRECTORY_SEPARATOR . $source,
            $this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest);

        if (!is_link($$this->_getModuleDir() . DIRECTORY_SEPARATOR . dest)) {
            throw new \ErrorException("could not create symlink $dest");
        }

    }

    /**
     * Similar to _createSymlink but copy files instead of using symlinks
     * @param $source
     * @param $dest
     */
    protected function _copyOver($source, $dest)
    {
        if (!file_exists($this->_getSourceDir() . DIRECTORY_SEPARATOR . $source)) {
            throw new \ErrorException("$source does not exists");
        }

        if (is_link($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest)) {
            if ($this->_isForced()) {
                unlink($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest);
            } else {
                throw new \ErrorException("$dest already exists");
            }
        }

        copy($this->_getSourceDir() . DIRECTORY_SEPARATOR . $source,
            $this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest);

        if (!file_exists($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest)) {
            throw new \ErrorException("could not copy file $dest");
        }

    }


}
