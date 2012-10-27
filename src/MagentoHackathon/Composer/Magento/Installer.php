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
        return $this->magentoRootDir;
    }

    protected function _getSourceDir()
    {
        return $this->vendorDir;
    }

    /**
     * Creates a symlink with lots of error-checking
     *
     * @param $source
     * @param $dest
     * @return void
     * @throws \ErrorException
     */
    protected function _createSymlink($source, $dest)
    {
        $sourcePath = $this->_getSourceDir() . DIRECTORY_SEPARATOR . $source;
        $destPath = $this->_getModuleDir() . DIRECTORY_SEPARATOR . $dest;

        // If source doesn't exist, check if it's a glob expression, otherwise we have nothing we can do
        if (!file_exists($sourcePath)) {
            // Handle globing
            $matches = glob($sourcePath);
            if ($matches) {
                foreach ($matches as $match) {
                    $newDest = $destPath . DIRECTORY_SEPARATOR . basename($match);
                    $this->_createSymlink($match, $newDest);
                }
                return;
            }

            // Source file isn't a valid file or glob
            throw new \ErrorException("Source $source does not exists");
        }

        // Symlink already exists, nothing to do
        if (is_link($destPath)) {
            // TODO Check if symlink still is valid!
            return;
        }

        // Create all directories up to one below the target if they don't exist
        $destDir = dirname($destPath);
        if (! file_exists($destDir)) {
            mkdir($destDir, 0777, true);
        }

        // Handle file to dir linking,
        // e.g. Namespace_Module.csv => app/locale/de_DE/
        if (file_exists($destPath) && is_dir($destPath) && is_file($sourcePath)) {
            $newDest = $destPath . DIRECTORY_SEPARATOR . basename($source);
            return $this->_createSymlink($source, $newDest);
        }

        // From now on $destPath can't be a directory, that case is already handled

        // If file exists and is not a symlink, throw exception unless FORCE is set
        if (file_exists($destPath)) {
            if ($this->_isForced()) {
                unlink($destPath);
            } else {
                throw new \ErrorException("Target $dest already exists and is not a symlink");
            }
        }

        // Create symlink
        link($sourcePath, $destPath);

        // Check we where able to create the symlink
        if (!is_link($destPath)) {
            throw new \ErrorException("Could not create symlink $dest");
        }

        return;
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
