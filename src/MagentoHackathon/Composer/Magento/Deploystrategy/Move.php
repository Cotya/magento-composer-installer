<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Move deploy strategy
 * Ref: https://github.com/Cotya/magento-composer-installer/issues/176
 */
class Move extends Copy
{
    /**
     * transfer by moving files
     *
     * @param string $item
     * @param string $subDestPath
     * @return bool
     */
    protected function transfer($item, $subDestPath)
    {
        return rename($item, $subDestPath);
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

    /**
     * Recursively remove files and folders from given path
     *
     * @param $path
     * @return void
     * @throws \Exception
     */
    private function removeDir($path)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if($filename != '..' || $filename != '.') {
                $removeAction = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
                try {
                    $removeAction($fileInfo->getRealPath());
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'Directory not empty')) {
                        $this->removeDir($fileInfo->getRealPath());
                    } else {
                        throw new Exception(sprintf('%s could not be removed.', $fileInfo->getRealPath()));
                    }

                }
            }
        }
        rmdir($path);
    }

}
