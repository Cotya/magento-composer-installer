<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Hardlink deploy strategy
 */
class Link extends DeploystrategyAbstract
{
    /**
     * Creates a hardlink with lots of error-checking
     *
     * @param string $source
     * @param string $dest
     * @return bool
     * @throws \ErrorException
     */
    public function createDelegate($source, $dest)
    {
        $sourcePath = $this->getSourceDir() . DIRECTORY_SEPARATOR . $this->removeTrailingSlash($source);
        $destPath = $this->getDestDir() . DIRECTORY_SEPARATOR . $this->removeTrailingSlash($dest);

        // Handle file to dir linking,
        // e.g. Namespace_Module.csv => app/locale/de_DE/
        if (file_exists($destPath) && is_dir($destPath) && is_file($sourcePath)) {
            $newDest = $destPath . DIRECTORY_SEPARATOR . basename($source);
            $this->addMapping($source, $newDest);
            return $this->create($source, substr($newDest, strlen($this->getDestDir())+1));
        }

        //file to file
        if (!is_dir($sourcePath) && !is_dir($destPath)) {
            $this->addMapping($sourcePath, $destPath);
            $destDir = dirname($destPath);
            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }
            link($sourcePath, $destPath);
        }

        //copy dir to dir
        if (is_dir($sourcePath)) {
            //first create destination folder
            mkdir($destPath, 0777, true);
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourcePath),
                \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $item) {
                $subDestPath = $destPath . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                if ($item->isDir()) {
                    mkdir($subDestPath, 0777, true);
                } else {
                    $this->addMapping($item->__toString(), $subDestPath);
                    link($item, $subDestPath);
                }
                if (!is_readable($subDestPath)) {
                    throw new \ErrorException("Could not create $subDestPath");
                }
            }
        }

        return true;
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
