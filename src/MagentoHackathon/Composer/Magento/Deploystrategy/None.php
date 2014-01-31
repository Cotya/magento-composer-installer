<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * None deploy strategy
 */
class None extends DeploystrategyAbstract
{
    /**
     * Deploy nothing
     *
     * @param string $source
     * @param string $dest
     * @return bool
     */
    public function createDelegate($source, $dest)
    {
        return true;
    }
}
