<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Abstract deploy strategy
 */
abstract class DeploystrategyAbstract
{
    /**
     * The path mappings to map project's directories to magento's directory structure
     *
     * @var array
     */
    protected $mappings = array();

    /**
     * The magento installation's base directory
     *
     * @var string
     */
    protected $destDir;

    /**
     * The module's base directory
     *
     * @var string
     */
    protected $sourceDir;

    /**
     * Constructor
     *
     * @param string $destDir
     * @param string $sourceDir
     */
    public function __construct($destDir, $sourceDir)
    {
        $this->destDir = $destDir;
        $this->sourceDir = $sourceDir;
    }

    /**
     * Executes the deployment strategy for each mapping
     *
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function deploy()
    {
        foreach ($this->getMappings() as $data) {
            list ($source, $dest) = $data;
            $this->create($source, $dest);
        }
        return $this;
    }

    /**
     * Returns the destination dir of the magento module
     *
     * @return string
     */
    protected function getDestDir()
    {
        return $this->destDir;
    }

    /**
     * Returns the current path of the extension
     *
     * @return mixed
     */
    protected function getSourceDir()
    {
        return $this->sourceDir;
    }

    /**
     * If set overrides existing files
     *
     * @todo Implement method body
     * @return bool
     */
    public function isForced()
    {
        return false;
    }

    /**
     * Returns the path mappings to map project's directories to magento's directory structure
     *
     * @return array
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * Sets path mappings to map project's directories to magento's directory structure
     *
     * @param array $mappings
     */
    public function setMappings(array $mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * Add a key value pair to mapping
     */
    public function addMapping($key, $value)
    {
        $this->mappings[] = array($key, $value);
    }

    protected function removeTrailingSlash($path)
    {
       return rtrim($path, ' \\/');
    }

    /**
     * Normalize mapping parameters using a glob wildcard.
     *
     * Delegate the creation of the module's files in the given destination.
     *
     * @param string $source
     * @param string $dest
     * @return bool
     */
    public function create($source, $dest)
    {
        $sourcePath = $this->getSourceDir() . DIRECTORY_SEPARATOR . $this->removeTrailingSlash($source);
        $destPath = $this->getDestDir() . DIRECTORY_SEPARATOR . $dest;

        // Create target directory if it end with a directory separator
        if (! file_exists($destPath) && in_array(substr($destPath, -1), array('/', '\\')) && ! is_dir($sourcePath)) {
            mkdir($destPath, 0777, true);
            $destPath = $this->removeTrailingSlash($destPath);
        }

        // If source doesn't exist, check if it's a glob expression, otherwise we have nothing we can do
        if (!file_exists($sourcePath)) {
            // Handle globing
            $matches = glob($sourcePath);
            if ($matches) {
                foreach ($matches as $match) {
                    $newDest = substr($destPath . DIRECTORY_SEPARATOR . basename($match), strlen($this->getDestDir()));
                    $newDest = ltrim($newDest, ' \\/');
                    $this->create(substr($match, strlen($this->getSourceDir())+1), $newDest);
                }
                return true;
            }

            // Source file isn't a valid file or glob
            throw new \ErrorException("Source $sourcePath does not exists");
        }

        $this->addMapping($source,$dest);
        return $this->createDelegate($source, $dest);
    }

    /**
     * Create the module's files in the given destination.
     *
     * NOTE: source and dest have to be passed as relative directories, like they are listed in the mapping
     *
     * @param string $source
     * @param string $dest
     * @return bool
     */
    abstract protected function createDelegate($source, $dest);

    /**
     * Removes the module's files in the given path
     *
     * @param string $path
     * @return void
     */
    abstract public function clean($path);
}
