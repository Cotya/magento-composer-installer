<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer;


use MagentoHackathon\Composer\Magento\ProjectConfig;

class Helper
{
    /**
     * @var \SplFileInfo
     */
    protected $projectRoot;

    /**
     * @var ProjectConfig
     */
    protected $magentoProjectConfig;

    /**
     * @param \SplFileInfo $projectRoot
     */
    public function __construct(\SplFileInfo $projectRoot)
    {
        if (!file_exists($projectRoot->getPathname().'/composer.json')) {
            throw new \InvalidArgumentException('no composer.json found in project root');
        }
        $this->projectRoot = $projectRoot;
        
        $reader = new \Eloquent\Composer\Configuration\ConfigurationReader;
        $composerJsonObject = $reader->read($this->projectRoot.'/composer.json');
        $this->magentoProjectConfig = new ProjectConfig((array)$composerJsonObject->extra());
    }
    
    public function getVendorDirectory()
    {
        /*
        $reader = new \Eloquent\Composer\Configuration\ConfigurationReader;
        $composerJsonObject = $reader->read($this->projectRoot.'/composer.json');
        return $composerJsonObject->vendorName();
        */
        return new \SplFileInfo($this->projectRoot.'/vendor');
    }
    
    public function getInstalledPackages()
    {
        
        $installedJsonObject = json_decode(file_get_contents(
            $this->getVendorDirectory()->getPathname().'/composer/installed.json'
        ), true);
        return $installedJsonObject;
    }

    /**
     * @return ProjectConfig
     */
    public function getMagentoProjectConfig()
    {
        return $this->magentoProjectConfig;
    }
    
    public function getPackageByName($name)
    {
        $result = null;
        foreach ($this->getInstalledPackages() as $package) {
            if ($package['name'] == $name) {
                $result = $package;
                break;
            }
        }
        return $result;
    }
}
