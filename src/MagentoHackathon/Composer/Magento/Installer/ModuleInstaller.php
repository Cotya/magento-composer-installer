<?php
/**
 * ModuleInstaller.php
 */

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;

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
     * @param IOInterface $io
     * @param Composer    $composer
     * @param string      $type
     *
     * @throws \ErrorException
     */
    public function __construct(IOInterface $io, Composer $composer, $type = self::PACKAGE_TYPE)
    {
        parent::__construct($io, $composer, $type);
    }

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