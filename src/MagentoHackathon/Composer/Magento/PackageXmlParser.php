<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento;

/**
 * Parses Magento Connect 2.0 package.xml files
 */
class PackageXmlParser implements Parser
{
    /**
     * @var string Path to vendor module dir
     */
    protected $_moduleDir = null;

    /**
     * @var \SplFileObject The package.xml file
     */
    protected $_file = null;

    /**
     * Constructor
     *
     * @param string $moduleDir
     * @param string $packageXmlFile
     */
    public function __construct($moduleDir, $packageXmlFile)
    {
        $this->setModuleDir($moduleDir);
        $this->setFile($this->getModuleDir() . DIRECTORY_SEPARATOR .  $packageXmlFile);
    }

    /**
     * Sets the module directory where to search for the package.xml file
     *
     * @param string $moduleDir
     * @return PackageXmlParser
     */
    public function setModuleDir($moduleDir)
    {
        // Remove trailing slash
        if (!is_null($moduleDir)) {
            $moduleDir = rtrim($moduleDir, '\\/');
        }

        $this->_moduleDir = $moduleDir;
        return $this;
    }

    /**
     * @return string
     */
    public function getModuleDir()
    {
        return $this->_moduleDir;
    }

    /**
     * @param string|SplFileObject $file
     * @return PackageXmlParser
     */
    public function setFile($file)
    {
        if (is_string($file)) {
            $file = new \SplFileObject($file);
        }
        $this->_file = $file;
        return $this;
    }

    /**
     * @return \SplFileObject
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * @return array
     * @throws \ErrorException
     */
    public function getMappings()
    {
        $file = $this->getFile();

        if (!$file->isReadable()) {
            throw new \ErrorException(sprintf('Package file "%s" not readable', $file->getPathname()));
        }

        $map = $this->_parseMappings();
        return $map;
    }

    /**
     * @throws \ErrorException
     * @return array
     */
    protected function _parseMappings()
    {
        $map = array();

        /** @var $package SimpleXMLElement */
        $package = simplexml_load_file($this->getFile());

        if (isset($package)) {
            foreach ($package->xpath('//contents/target') as $target) {
                var_dump($target);
            }
        }

        return $map;
    }
}
