<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento;

use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;
use MagentoHackathon\Composer\Magento\Deploystrategy\Link;
use MagentoHackathon\Composer\Magento\Deploystrategy\None;
use MagentoHackathon\Composer\Magento\Deploystrategy\Symlink;

class Factory
{

    /**
     * @param $strategyName
     * @param $sourceDir
     * @param $targetDir
     *
     * @return Copy|Link|None|Symlink
     */
    public static function getDeployStrategyObject($strategyName, $sourceDir, $targetDir)
    {

        switch ($strategyName) {
            case 'copy':
                $impl = new Copy($sourceDir, $targetDir);
                break;
            case 'link':
                $impl = new Link($sourceDir, $targetDir);
                break;
            case 'none':
                $impl = new None($sourceDir, $targetDir);
                break;
            case 'symlink':
            default:
                $impl = new Symlink($sourceDir, $targetDir);
        }
        
        return $impl;
    }
}
