<?php

namespace MagentoHackathon\Composer\Magento;

use \Composer;

class ModmanParser
{
    /**
     * @var string Path to the modman file
     */
    protected $_file = '';

    /**
     * @var \Composer\Composer
     */
    protected $_composer;

    public function __construct(Composer $composer)
    {
        $this->_composer = $composer;
        $this->setFile($this->getModmanFile());
    }

    public function setFile($file)
    {
        $this->_file = $file;
        return $this;
    }

    public function getFile()
    {
        return $this->_file;
    }

    public function getModmanFile()
    {
        return $this->_composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . 'modman';
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
     * @param $modmanData
     * @return array
     */
    protected function _parseMappings($modmanData)
    {
        $map = array();
        $line = 0;
        foreach (explode("\n", $modmanData) as $row) {
            $line++;
            $row = trim($row);
            if ('' === $row || in_array($row{0}, array('#', '@'))) {
                continue;
            }
            $parts = preg_split('/\s+/', $row, 2, PREG_SPLIT_NO_EMPTY);
            if (count($parts) != 2) {
                throw new \ErrorException(sprintf('Invalid row on line %d has %d parts, expected 2', $line, count($row)));
            }
            $map[$parts[0]] = $parts[1];
        }
        return $map;
    }
}
