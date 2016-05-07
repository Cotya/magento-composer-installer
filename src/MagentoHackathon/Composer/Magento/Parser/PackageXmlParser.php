<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Parser;

/**
 * Parses Magento Connect 2.0 package.xml files
 *
 * Class PackageXmlParser
 * @package MagentoHackathon\Composer\Magento\Parser
 */
class PackageXmlParser implements Parser
{

    /**
     * @var \SplFileObject The package.xml file
     */
    protected $file = null;

    /**
     * @var array Map of package content types to path prefixes
     */
    protected $targets = array();

    /**
     * @param string $packageXmlFile
     */
    public function __construct($packageXmlFile)
    {
        $this->file = new \SplFileObject($packageXmlFile);
    }

    /**
     * @return array
     * @throws \ErrorException
     */
    public function getMappings()
    {
        if (!$this->file->isReadable()) {
            throw new \ErrorException(sprintf('Package file "%s" not readable', $this->file->getPathname()));
        }

        $map = $this->parseMappings();
        return $map;
    }

    /**
     * @throws \RuntimeException
     * @return array
     */
    protected function parseMappings()
    {
        $map = array();

        /** @var $package \SimpleXMLElement */
        $package = simplexml_load_file($this->file->getPathname());
        if (isset($package)) {
            foreach ($package->xpath('//contents/target') as $target) {
                try {
                    $basePath = $this->getTargetPath($target);

                    foreach ($target->children() as $child) {
                        foreach ($this->getElementPaths($child) as $elementPath) {
                            if (pathinfo($elementPath, PATHINFO_EXTENSION) == 'txt') {
                                continue;
                            }
                            $relativePath = str_replace('//', '/', $basePath . '/' . $elementPath);
                            //remove the any trailing './' or '.' from the targets base-path.
                            if (strpos($relativePath, './') === 0) {
                                $relativePath = substr($relativePath, 2);
                            }
                            $map[] = array($relativePath, $relativePath);
                        }
                    }
                } catch (\RuntimeException $e) {
                    // Skip invalid targets
                    continue;
                }
            }
        }
        return $map;
    }

    /**
     * @param \SimpleXMLElement $target
     * @return string
     * @throws \RuntimeException
     */
    protected function getTargetPath(\SimpleXMLElement $target)
    {
        $name = (string) $target->attributes()->name;
        $targets = $this->getTargetsDefinitions();
        if (! isset($targets[$name])) {
            throw new \RuntimeException('Invalid target type ' . $name);
        }
        return $targets[$name];
    }

    /**
     * @return array
     */
    protected function getTargetsDefinitions()
    {
        if (empty($this->targets)) {
            /** @var $targets \SimpleXMLElement */
            $targets = simplexml_load_file(__DIR__ . '/../../../../../res/target.xml');
            foreach ($targets as $target) {
                /** @var $target \SimpleXMLElement */
                $attributes = $target->attributes();
                $this->targets["{$attributes->name}"] = "{$attributes->uri}";
            }
        }
        return $this->targets;
    }

    /**
     * @param \SimpleXMLElement $element
     * @return array
     * @throws \RuntimeException
     */
    protected function getElementPaths(\SimpleXMLElement $element)
    {
        $type = $element->getName();
        $name = $element->attributes()->name;
        $elementPaths = array();

        switch ($type) {
            case 'dir':
                if ($element->children()) {
                    foreach ($element->children() as $child) {
                        foreach ($this->getElementPaths($child) as $elementPath) {
                            $elementPaths[] = $name == '.' ? $elementPath : $name . '/' . $elementPath;
                        }
                    }
                } else {
                    $elementPaths[] = $name;
                }
                break;

            case 'file':
                $elementPaths[] = $name;
                break;

            default:
                throw new \RuntimeException('Unknown path type: ' . $type);
        }

        return $elementPaths;
    }
}
