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
    public function create($source, $dest)
    {
        $sourcePath = $this->getSourceDir() . DIRECTORY_SEPARATOR . $source;
        $destPath = $this->getDestDir() . DIRECTORY_SEPARATOR . $dest;

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

        // Handle file to dir linking,
        // e.g. Namespace_Module.csv => app/locale/de_DE/
        if (file_exists($destPath) && is_dir($destPath) && is_file($sourcePath)) {
            $newDest = $destPath . DIRECTORY_SEPARATOR . basename($source);
            $this->addMapping($source, $newDest);
            return $this->create($source, $newDest);
        }

        //file to file
        if (!is_dir($sourcePath) && !is_dir($destPath)) {
            $this->addMapping($sourcePath, $destPath);
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

        return $this;
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
