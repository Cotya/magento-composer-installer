<?php

namespace MagentoHackathon\Composer\Magento\UnInstallStrategy;

use Composer\Util\Filesystem;

/**
 * Class UnInstallStrategy
 * @package MagentoHackathon\Composer\Magento\UnInstallStrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class UnInstallStrategy implements UnInstallStrategyInterface
{

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * The root dir for uninstalling from. Should be project root.
     *
     * @var string
     */
    protected $rootDir;

    /**
     * @param Filesystem $fileSystem
     * @param string     $rootDir
     */
    public function __construct(Filesystem $fileSystem, $rootDir)
    {
        $this->fileSystem   = $fileSystem;
        $this->rootDir      = $rootDir;
    }

    /**
     * UnInstall the extension given the list of install files
     *
     * @param array $files
     */
    public function unInstall(array $files)
    {
        foreach ($files as $file) {
            $file = $this->rootDir . $file;

            /*
            because of different reasons the file can be already gone.
            example:
            - file got deployed by multiple modules(should only happen with copy force)
            - user did things

            when the file is a symlink, but the target is already gone, file_exists returns false
            */

            if (is_link($file)) {
                $this->fileSystem->unlink($file);
            }

            if (file_exists($file)) {
                $this->fileSystem->remove($file);
            }

            $parentDir = dirname($file);
            while (is_dir($parentDir)
                && $this->fileSystem->isDirEmpty($parentDir)
                && $parentDir !== $this->rootDir
            ) {
                $this->fileSystem->removeDirectory($parentDir);
                $parentDir = dirname($parentDir);
            }
        }
    }
}
