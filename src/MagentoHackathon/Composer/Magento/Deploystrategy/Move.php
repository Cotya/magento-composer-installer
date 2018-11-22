<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Symlink deploy strategy
 */
class Move extends Copy
{
    /**
     * transfer by moving files
     *
     * @param string $item
     * @param string $subDestPath
     * @return void
     */
    protected function transfer($item, $subDestPath)
    {
        rename($item, $subDestPath);
    }

    /**
     * afterDeploy
     *
     * @return void
     */
    protected function afterDeploy()
    {
        if(is_dir($this->sourceDir)) {
            $this->removeDir($this->sourceDir);
        }
    }

    private function removeDir($path)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $fileinfo) {
            $filename = $fileinfo->getFilename();
            if($filename != '..' || $filename != '.') {
                $removeAction = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                try {
                    $removeAction($fileinfo->getRealPath());
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'Directory not empty')) {
                        $this->removeDir($fileinfo->getRealPath());
                    }
                }
            }
        }
        rmdir($this->sourceDir);
    }

}
