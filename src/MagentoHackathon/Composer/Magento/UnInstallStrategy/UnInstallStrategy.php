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
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * @param FileSystem $fileSystem
     */
    public function __construct(FileSystem $fileSystem)
    {

        $this->fileSystem = $fileSystem;
    }

    /**
     * UnInstall the extension given the list of install files
     *
     * @param array $files
     */
    public function unInstall(array $files)
    {
        foreach ($files as $file) {
            /*
            because of different reasons the file can be already gone.
            example:
            - file got deployed by multiple modules(should only happen with copy force)
            - user did things
            */
            if (file_exists($file)) {
                $this->fileSystem->unlink($file);

                if ($this->fileSystem->isDirEmpty(dirname($file))) {
                    $this->fileSystem->removeDirectory(dirname($file));
                }
            }
        }
    }
}
