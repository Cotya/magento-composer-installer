<?php

namespace MagentoHackathon\Composer\Magento;

class ModmanParser
{
    /**
     * @var string Path to vendor module dir
     */
    protected $_moduleDir = '';

    /**
     * @var \SplFileObject The modman file
     */
    protected $_file = '';

    public function __construct($moduleDir = null)
    {
        $this->_moduleDir = $moduleDir;
        $this->setFile($this->getModmanFile());
    }

    /**
     * @param string $file
     * @return ModmanParser
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
     * @return string
     */
    public function getModmanFile()
    {
        return new \SplFileObject($this->_moduleDir . DIRECTORY_SEPARATOR . 'modman');
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
        if (!$file->isReadable()) {
            throw new \ErrorException(sprintf('modman file "%s" not readable', $file->getPathname()));
        }

        $modmanRows = array();
        while (!$file->eof()) {
            $modmanRows[] = $file->fgets();
        }

        $map = $this->_parseMappings($modmanRows);
        return $map;
    }

    /**
     * @param array $modmanRows
     * @throws \ErrorException
     * @return array
     */
    protected function _parseMappings(array $modmanRows)
    {
        $map = array();
        $line = 0;
        foreach ($modmanRows as $row) {
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
