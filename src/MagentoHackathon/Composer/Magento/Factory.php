<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento;

use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;
use MagentoHackathon\Composer\Magento\Deploystrategy\Link;
use MagentoHackathon\Composer\Magento\Deploystrategy\None;
use MagentoHackathon\Composer\Magento\Deploystrategy\Symlink;

class Factory
{

    /**
     * @param $strategyName
     * @param $sourceDir
     * @param $targetDir
     *
     * @return Copy|Link|None|Symlink
     */
    public static function getDeployStrategyObject($strategyName, $sourceDir, $targetDir)
    {

        switch ($strategyName) {
            case 'copy':
                $impl = new Copy($sourceDir, $targetDir);
                break;
            case 'link':
                $impl = new Link($sourceDir, $targetDir);
                break;
            case 'none':
                $impl = new None($sourceDir, $targetDir);
                break;
            case 'symlink':
            default:
                $impl = new Symlink($sourceDir, $targetDir);
        }
        
        return $impl;
    }

    /**
     * @param ProjectConfig $projectConfig
     * @param               $package
     * @param               $packageDir
     * 
     * @return Parser
     *
     * @throws \ErrorException
     */
    public static function getMappingParser(ProjectConfig $projectConfig, $package, $packageDir)
    {
        $packageName = $package['name'];
        $pathMappingTranslations = array();
        if ($projectConfig->hasPathMappingTranslations()) {
            $pathMappingTranslations = $projectConfig->getPathMappingTranslations();
        }

        $extra = $package;
        $moduleSpecificMap = $projectConfig->getMagentoMapOverwrite();
        if ($moduleSpecificMap) {
            if (isset($moduleSpecificMap[$packageName])) {
                $map = $moduleSpecificMap[$packageName];
            }
        }

        if (isset($map)) {
            $parser = new MapParser($map, $pathMappingTranslations);

            return $parser;
        } elseif (isset($extra['map'])) {
            $parser = new MapParser($extra['map'], $pathMappingTranslations);

            return $parser;
        } elseif (isset($extra['package-xml'])) {
            $parser = new PackageXmlParser(
                $packageDir,
                $extra['package-xml'],
                $pathMappingTranslations
            );

            return $parser;
        } elseif (file_exists($packageDir . '/modman')) {
            $parser = new ModmanParser($packageDir, $pathMappingTranslations);

            return $parser;
        } else {
            throw new \ErrorException('Unable to find deploy strategy for module: no known mapping');
        }
    }
}
