<?php
/**
 * ModuleInstaller.php
 */

namespace MagentoHackathon\Composer\Magento\Installer;

/**
 * Class ModuleInstaller
 *
 * @package MagentoHackathon\Composer\Magento\Installer
 */
class ModuleInstaller extends MagentoInstallerAbstract
{
    /**
     * Package Type Definition
     */
    const PACKAGE_TYPE = 'magento-module';

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     *
     * @return bool
     */
    public function supports($packageType)
    {
        return self::PACKAGE_TYPE === $packageType;
    }
}