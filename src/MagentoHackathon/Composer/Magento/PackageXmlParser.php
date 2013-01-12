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
     * @var array Map of package content types to path prefixes
     */
    protected $_targets = array();

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
                try {
                    $basePath = $this->getTargetPath($target);

                    $elementPath = $this->getElementPath($this->getFirstChild($target)); // first child
                    $relPath = $basePath . DIRECTORY_SEPARATOR . $elementPath;

                    $map[] = array($relPath, $relPath);
                }
                catch (RuntimeException $e) {
                    // Skip invalid targets
                    //throw $e;
                    continue;
                }
            }
        }

        return $map;
    }

    /**
     * @param \SimpleXMLElement $target
     * @return string
     * @throws RuntimeException
     */
    protected function getTargetPath(\SimpleXMLElement $target)
    {
        $name = (string) $target->attributes()->name;
        $targets = $this->getTargetsDefinitions();
        if (! isset($targets[$name])) {
            throw new RuntimeException('Invalid target type ' . $name);
        }
        return $targets[$name];
    }

    /**
     * @return array
     */
    protected function getTargetsDefinitions()
    {
        if (! $this->_targets) {

            $targets = simplexml_load_file(__DIR__ . '/../../../../res/target.xml');
            foreach ($targets as $target) {
                $attributes = $target->attributes();
                $this->_targets["{$attributes->name}"] = "{$attributes->uri}";
            }
        }
        return $this->_targets;
    }

    /**
     * @param \SimpleXMLElement $element
     * @return string
     * @throws RuntimeException
     */
    protected function getElementPath(\SimpleXMLElement$element) {
        $type = $element->getName();
        $name = $element->attributes()->name;

        switch ($type) {
            case 'dir':
                if ($element->children()) {
                    $name .= DIRECTORY_SEPARATOR . $this->getElementPath($this->getFirstChild($element));
                }
                return $name;
            case 'file':
                return $name;
            default:
                throw new RuntimeException('Unknown path type: ' . $type);
        }
    }

    /**
     * @param \SimpleXMLElement $element
     * @return SimpleXMLElement
     */
    protected function getFirstChild(\SimpleXMLElement$element)
    {
        foreach ($element->children() as $child) return $child;
    }
}
