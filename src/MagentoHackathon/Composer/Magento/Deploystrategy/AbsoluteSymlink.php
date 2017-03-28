<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Absolute Symlink deploy strategy
 */
class AbsoluteSymlink extends Symlink
{
    /**
     * @inheritdoc
     */
    protected function symlink($relSourcePath, $destPath, $absSourcePath)
    {
        // make symlinks always absolute on windows because of #142
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $absSourcePath = str_replace('/', '\\', $absSourcePath);
        }
        return symlink($absSourcePath, $destPath);
    }
}
