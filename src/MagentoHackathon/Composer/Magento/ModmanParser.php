<?php

namespace MagentoHackathon\Composer\Magento;

class ModmanParser
{
    /**
     * @var string Path to vendor module dir
     */
    protected $_moduleDir = '';

    /**
     * @var string Path to the modman file
     */
    protected $_file = '';

    public function __construct($moduleDir = null)
    {
        $this->setModuleDir($moduleDir);
        $this->setFile($this->getModmanFile());
    }

    public function setModuleDir($moduleDir)
    {
        // Remove trailing slash
        if (in_array(substr($moduleDir, -1), array('/', '\\'))) {
            $moduleDir = substr($moduleDir, 0, -1);
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
     * @param string $file
     * @return ModmanParser
     */
    public function setFile($file)
    {
        $this->_file = $file;
        return $this;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * @return string
     */
    public function getModmanFile()
    {
        return $this->_moduleDir . DIRECTORY_SEPARATOR . 'modman';
    }

    /**
     * @param string $file
     * @return array
     * @throws \ErrorException
     */
    public function getMappings($file = null)
    {
        if (null === $file) {
            $file = $this->getFile();
        }
        if (! is_readable($file)) {
            throw new \ErrorException(sprintf('modman file "%s" not readable', $file));
        }
        $map = $this->_parseMappings(file_get_contents($file));
        return $map;
    }

    /**
     * @param string $modmanData
     * @return array
     */
    protected function _parseMappings($modmanData)
    {
        $map = array();
        $line = 0;
        foreach (explode("\n", $modmanData) as $row) {
            $line++;
            $row = trim($row);
            if ('' === $row || in_array($row[0], array('#', '@'))) {
                continue;
            }
            $parts = preg_split('/\s+/', $row, 2, PREG_SPLIT_NO_EMPTY);
            if (count($parts) != 2) {
                throw new \ErrorException(sprintf('Invalid row on line %d has %d parts, expected 2', $line, count($row)));
            }
            list ($source, $target) = $parts;

            $map[$source] = $target;
        }
        return $map;
    }
}
