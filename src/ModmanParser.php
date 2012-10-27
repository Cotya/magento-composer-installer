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

    public function parseModman($file = null)
    {
        if (null === $file) {
            $file = $this->getFile();
        }
        if (! is_readable($file)) {
            throw new
        }
    }
}
