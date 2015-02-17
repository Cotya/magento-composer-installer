<?php

namespace MagentoHackathon\Composer\Magento\UnInstallStrategy;

/**
 * Interface UnInstallStrategyInterface
 * @package MagentoHackathon\Composer\Magento\UnInstallStrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
interface UnInstallStrategyInterface
{
    /**
     * UnInstall the extension given the list of install files
     *
     * @param array $files
     */
    public function unInstall(array $files);
}
