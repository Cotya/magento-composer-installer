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
     * copy files
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
            return $this->create($source, $newDest);
        }

        //file to file
        if (!is_dir($sourcePath) && !is_dir($destPath)) {
            $this->addMapping($sourcePath, $destPath);
            copy($sourcePath, $destPath);
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
                    copy($item, $subDestPath);
                }
                if (!is_readable($subDestPath)) {
                    throw new \ErrorException("Could not create $subDestPath");
                }
            }
        }

        return $this;
    }

    /**
     * Removes all copied files in $dest - not implemented yet
     *
     * @param string $path
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function clean($path)
    {
        return $this;
    }
}
