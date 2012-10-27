<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Symlink deploy strategy
 */
class Copy extends DeploystrategyAbstract
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
        $sourcePath = $this->_getSourceDir() . DIRECTORY_SEPARATOR . $source;
        $destPath = $this->_getDestDir() . DIRECTORY_SEPARATOR . $dest;

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

        // Remove trailing slash, otherwise symlink will fail for target directories
        rtrim($sourcePath, ' /');
        rtrim($destPath, ' /');

        // Create symlink
        copy($sourcePath, $destPath);

        // Check we where able to create the symlink
        if (!is_readable($destPath)) {
            throw new \ErrorException("Could not create symlink $destPath");
        }

        return $this;
    }

    /**
     * Removes all copied files in $dest
     *
     * @param string $path
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     * @throws \ErrorException
     */
    public function clean($path)
    {
        foreach ($this->getMappings() as $source => $dest) {
            @unlink($this->_getDestDir() . DIRECTORY_SEPARATOR . $dest);
        }
        return $this;
    }
}
