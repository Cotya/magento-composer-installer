<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Symlink deploy strategy
 */
class Symlink extends DeploystrategyAbstract
{
    /**
     * Creates a symlink with lots of error-checking
     *
     * @param string $source
     * @param string $dest
     * @return bool
     * @throws \ErrorException
     */
    public function create($source, $dest)
    {
        $sourcePath = $this->getSourceDir() . DIRECTORY_SEPARATOR . $this->removeTrailingSlash($source);
        $destPath = $this->getDestDir() . DIRECTORY_SEPARATOR . $this->removeTrailingSlash($dest);

        $this->addMapping($source,$dest);

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
            throw new \ErrorException("Source $sourcePath does not exists");
        }

        // Symlink already exists
        if (is_link($destPath)) {
            if ( readlink($destPath) == realpath($sourcePath) ) {
                // .. and is equal to current source-link
                return;
            }
            unlink( $destPath );
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
        symlink($sourcePath, $destPath);

        // Check we where able to create the symlink
        if (!is_link($destPath)) {
            throw new \ErrorException("Could not create symlink $destPath");
        }

        return $this;
    }

    protected function removeTrailingSlash($path)
    {
       return rtrim($path, ' \\/');
    }

    /**
     * Removes the links in the given path
     *
     * @param string $path
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     * @throws \ErrorException
     */
    public function clean($path)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getDestDir()),
            \RecursiveIteratorIterator::CHILD_FIRST);
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
