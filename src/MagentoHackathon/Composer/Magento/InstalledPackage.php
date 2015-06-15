<?php

namespace MagentoHackathon\Composer\Magento;

/**
 * Class InstalledPackage
 * @package MagentoHackathon\Composer\Magento
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class InstalledPackage
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var array
     */
    protected $installedFiles;

    /**
     * @param string $name
     * @param string $version
     * @param array $files
     */
    public function __construct($name, $version, array $files)
    {
        $this->name = $name;
        $this->installedFiles = $files;
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getUniqueName()
    {
        return sprintf('%s-%s', $this->getName(), $this->getVersion());
    }

    /**
     * @return array
     */
    public function getInstalledFiles()
    {
        return $this->installedFiles;
    }
}
