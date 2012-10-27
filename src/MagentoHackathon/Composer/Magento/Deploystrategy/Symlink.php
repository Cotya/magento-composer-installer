<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Depolystrategy;

/**
 * Symlink deploy strategy
 */
class Symlink extends DeploystrategyAbstract
{
    /**
     * Creates a symlink with lots of error-checking
     *
     * @param $source
     * @param $dest
     * @return bool
     * @throws \ErrorException
     * @todo implement file to dir modman target, e.g. Namespace_Module.csv => app/locale/de_DE/
     * @todo implement glob to dir mapping target, e.g. code/* => app/code/local/
     */
    public function create($source, $dest)
    {
        $sourcePath = $this->_getSourceDir() . DIRECTORY_SEPARATOR . $source;
        $destPath = $this->_getDestDir() . DIRECTORY_SEPARATOR . $dest;

        // If source doesn't exist, check if it's a glob expression, otherwise we have nothing we can do
        if (!file_exists($sourcePath)) {
            // Handle globing
            $matches = glob($sourcePath);
            if ($matches) {
                foreach ($matches as $match) {
                    $newDest = $destPath . DIRECTORY_SEPARATOR . basename($match);
                    $this->create($match, $newDest);
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
        if (!file_exists($destDir)) {
            mkdir($destDir, 0777, true);
        }

        // Handle file to dir linking,
        // e.g. Namespace_Module.csv => app/locale/de_DE/
        if (file_exists($destPath) && is_dir($destPath) && is_file($sourcePath)) {
            $newDest = $destPath . DIRECTORY_SEPARATOR . basename($source);
            return $this->create($source, $newDest);
        }

        // From now on $destPath can't be a directory, that case is already handled

        // If file exists and is not a symlink, throw exception unless FORCE is set
        if (file_exists($destPath)) {
            if ($this->isForced()) {
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
     * Removes the links in the given path
     *
     * @param string $path
     * @return \MagentoHackathon\Composer\Magento\Depolystrategy\DeploystrategyAbstract
     * @throws \ErrorException
     */
    public function clean($path)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->_getDestDir()),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if (is_link($path->__toString())) {
                $dest = readlink($path->__toString());
                if ($dest === 0 || !is_readable($dest)) {
                    $denied = @unlink($path->__toString());
                    if ($denied) {
                        throw new \ErrorException('Permission denied on ' . $path->__toString());
                    }
                }
            }
        }

        return $this;
    }
}
