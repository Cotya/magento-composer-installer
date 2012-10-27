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
     * @param \Composer\IO\IOInterface $io
     * @param \Composer\Composer $composer
     * @param string $type
     */
    public function __construct(IOInterface $io, Composer $composer, $type = 'magento-module')
    {
        parent::__construct($io, $composer, $type);
        $this->magentoRootDir = rtrim($composer->getPackage()->getExtra()->get('magento-root-dir'), ' /');
        $this->_isForced = $composer->getPackage()->getExtra()->get('magento-force');
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
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_getModuleDir()),
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $path) {
            if (is_link($path->__toString())) {
                $dest = readlink($path->__toString());
                if ($dest === 0 || !is_readable($dest)) {
                    $denied = @unlink($path->__toString());
                    if ($denied) {
                        throw new \ErrorException("Permission denied");
                    }
                }
            }
        }
    }

    /**
     * Get the destination dir of the magento module
     *
     * @return string
     */
    protected function _getModuleDir()
    {
        return $this->magentoRootDir;
    }

    /**
     * Get the current path of the extension.
     *
     * @return mixed
     */
    protected function _getSourceDir()
    {
        return rtrim($this->composer->getConfig()->get('vendor-dir') . ' /');
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
        if (!is_readable($this->_getSourceDir() . DIRECTORY_SEPARATOR . $source)) {
            throw new \ErrorException("$source does not exists");
        }

        if (is_link($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest)) {
            return true;
        }

        if (is_readable($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest)) {
            if ($this->_isForced()) {
                $success = @unlink($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest);
                if (!$success) {
                    throw new \ErrorException("$dest permission denied!");
                }
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
        if (!is_readable($this->_getSourceDir() . DIRECTORY_SEPARATOR . $source)) {
            throw new \ErrorException("$source does not exists");
        }

        if (is_link($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest)) {
            if ($this->_isForced()) {
                $success = @unlink($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest);
                if (!$success) {
                    throw new \ErrorException("$dest permission denied!");
                }
            } else {
                throw new \ErrorException("$dest already exists");
            }
        }

        copy($this->_getSourceDir() . DIRECTORY_SEPARATOR . $source,
                $this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest);

        if (!is_readable($this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest)) {
            throw new \ErrorException("could not copy file $dest");
        }

    }
}