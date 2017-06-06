<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use SebastianBergmann\Exporter\Exception;

class ProjectConfig
{
    // Config Keys
    const EXTRA_KEY = 'extra';

    const SORT_PRIORITY_KEY = 'magento-deploy-sort-priority';

    const MAGENTO_ROOT_DIR_KEY = 'magento-root-dir';

    const MAGENTO_PROJECT_KEY = 'magento-project';

    const MAGENTO_DEPLOY_STRATEGY_KEY = 'magento-deploystrategy';
    const MAGENTO_DEPLOY_STRATEGY_OVERWRITE_KEY = 'magento-deploystrategy-overwrite';
    const MAGENTO_MAP_OVERWRITE_KEY = 'magento-map-overwrite';
    const MAGENTO_DEPLOY_IGNORE_KEY = 'magento-deploy-ignore';

    const MAGENTO_FORCE_KEY = 'magento-force';

    const AUTO_APPEND_GITIGNORE_KEY = 'auto-append-gitignore';

    const PATH_MAPPINGS_TRANSLATIONS_KEY = 'path-mapping-translations';

    const INCLUDE_ROOT_PACKAGE_KEY = 'include-root-package';

    // Default Values
    const DEFAULT_MAGENTO_ROOT_DIR = 'root';

    const EXTRA_WITH_BOOTSTRAP_PATCH_KEY = 'with-bootstrap-patch';

    const EXTRA_WITH_SKIP_SUGGEST_KEY = 'skip-suggest-repositories';

    const EXTRA_DEV_MODE_APPEND = '-dev';

    protected $libraryPath;
    protected $libraryPackages;
    protected $extra;
    protected $composerConfig;

    /**
     * @var bool
     */
    protected $isDevMode;

    /**
     * @param array $extra
     * @param array $composerConfig
     */
    public function __construct(array $extra, array $composerConfig)
    {
        $this->extra = $extra;
        $this->composerConfig = $composerConfig;

        $this->isDevMode = false;

        if (!is_null($projectConfig = $this->fetchVarFromConfigArray($this->extra, self::MAGENTO_PROJECT_KEY))) {
            $this->applyMagentoConfig($projectConfig);
        }
    }

    /**
     * @param array $array
     * @param string|integer $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function fetchVarFromConfigArray($array, $key, $default = null)
    {
        $array = (array)$array;
        $result = $default;

        if ($this->isDevMode && isset($array[$key . self::EXTRA_DEV_MODE_APPEND])) {
            $result = $array[$key . self::EXTRA_DEV_MODE_APPEND];
        } elseif (isset($array[$key])) {
            $result = $array[$key];
        }

        return $result;
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return null
     */
    protected function fetchVarFromExtraConfig($key, $default = null)
    {
        return $this->fetchVarFromConfigArray($this->extra, $key, $default);
    }

    /**
     * @param $config
     */
    protected function applyMagentoConfig($config)
    {
        $this->libraryPath = $this->fetchVarFromConfigArray($config, 'libraryPath');
        $this->libraryPackages = $this->fetchVarFromConfigArray($config, 'libraries');
    }

    /**
     * @return mixed
     */
    public function getLibraryPath()
    {
        return $this->libraryPath;
    }

    /**
     * @param $packagename
     *
     * @return null
     */
    public function getLibraryConfigByPackagename($packagename)
    {
        return $this->fetchVarFromConfigArray($this->libraryPackages, $packagename);
    }

    /**
     * @return string
     */
    public function getMagentoRootDir()
    {
        return rtrim(
            trim(
                $this->fetchVarFromExtraConfig(
                    self::MAGENTO_ROOT_DIR_KEY,
                    self::DEFAULT_MAGENTO_ROOT_DIR
                )
            ),
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * @param $rootDir
     */
    public function setMagentoRootDir($rootDir)
    {
        $this->updateExtraConfig(self::MAGENTO_ROOT_DIR_KEY, rtrim(trim($rootDir), DIRECTORY_SEPARATOR));
    }

    /**
     * @return bool
     */
    public function hasMagentoRootDir()
    {
        return $this->hasExtraField(self::MAGENTO_ROOT_DIR_KEY);
    }

    public function getMagentoVarDir()
    {
        return $this->getMagentoRootDir().'var'.DIRECTORY_SEPARATOR;
    }

    /**
     * @param $deployStrategy
     */
    public function setDeployStrategy($deployStrategy)
    {
        $this->updateExtraConfig(self::MAGENTO_DEPLOY_STRATEGY_KEY, trim($deployStrategy));
    }

    /**
     * @return string
     */
    public function getDeployStrategy()
    {
        return trim((string)$this->fetchVarFromExtraConfig(self::MAGENTO_DEPLOY_STRATEGY_KEY));
    }

    /**
     * @return bool
     */
    public function hasDeployStrategy()
    {
        return $this->hasExtraField(self::MAGENTO_DEPLOY_STRATEGY_KEY);
    }

    /**
     * @return array
     */
    public function getDeployStrategyOverwrite()
    {
        return (array)$this->transformArrayKeysToLowerCase(
            $this->fetchVarFromExtraConfig(self::MAGENTO_DEPLOY_STRATEGY_OVERWRITE_KEY, array())
        );
    }

    /**
     * @return bool
     */
    public function hasDeployStrategyOverwrite()
    {
        return $this->hasExtraField(self::MAGENTO_DEPLOY_STRATEGY_OVERWRITE_KEY);
    }

    /**
     * @param $packagename
     *
     * @return integer
     */
    public function getModuleSpecificDeployStrategy($packagename)
    {
        $moduleSpecificDeployStrategies = $this->getDeployStrategyOverwrite();

        $strategyName = $this->getDeployStrategy();
        if (isset($moduleSpecificDeployStrategies[$packagename])) {
            $strategyName = $moduleSpecificDeployStrategies[$packagename];
        }
        return $strategyName;
    }

    /**
     * @param $packagename
     *
     * @return integer
     */
    public function getModuleSpecificSortValue($packagename)
    {
        $sortPriorityArray = $this->fetchVarFromExtraConfig(self::SORT_PRIORITY_KEY, array());
        if (isset($sortPriorityArray[$packagename])) {
            $sortValue = $sortPriorityArray[$packagename];
        } else {
            $sortValue = 100;
            if ($this->getModuleSpecificDeployStrategy($packagename) === 'copy') {
                $sortValue++;
            }
        }
        return $sortValue;
    }

    /**
     * @return array
     */
    public function getMagentoDeployIgnore()
    {
        return (array)$this->transformArrayKeysToLowerCase(
            $this->fetchVarFromExtraConfig(self::MAGENTO_DEPLOY_IGNORE_KEY)
        );
    }

    /**
     * @param $packagename
     *
     * @return array
     */
    public function getModuleSpecificDeployIgnores($packagename)
    {
        $moduleSpecificDeployIgnores = array();
        if ($this->hasMagentoDeployIgnore()) {
            $magentoDeployIgnore = $this->getMagentoDeployIgnore();
            if (isset($magentoDeployIgnore['*'])) {
                $moduleSpecificDeployIgnores = $magentoDeployIgnore['*'];
            }
            if (isset($magentoDeployIgnore[$packagename])) {
                $moduleSpecificDeployIgnores = array_merge(
                    $moduleSpecificDeployIgnores,
                    $magentoDeployIgnore[$packagename]
                );
            }
        }
        return $moduleSpecificDeployIgnores;
    }

    /**
     * @return bool
     */
    public function hasMagentoDeployIgnore()
    {
        return $this->hasExtraField(self::MAGENTO_DEPLOY_IGNORE_KEY);
    }

    /**
     * @param $magentoForce
     */
    public function setMagentoForce($magentoForce)
    {
        $this->updateExtraConfig(self::MAGENTO_FORCE_KEY, trim($magentoForce));
    }

    /**
     * @return string
     */
    public function getMagentoForce()
    {
        return (bool)$this->fetchVarFromExtraConfig(self::MAGENTO_FORCE_KEY);
    }

    /**
     * @return bool
     */
    public function hasMagentoForce()
    {
        return $this->hasExtraField(self::MAGENTO_FORCE_KEY);
    }

    public function getMagentoForceByPackageName($packagename)
    {
        return $this->getMagentoForce();
    }

    /**
     * @return bool
     */
    public function hasAutoAppendGitignore()
    {
        return $this->hasExtraField(self::AUTO_APPEND_GITIGNORE_KEY);
    }

    /**
     * @return array
     */
    public function getPathMappingTranslations()
    {
        return (array)$this->fetchVarFromExtraConfig(self::PATH_MAPPINGS_TRANSLATIONS_KEY);
    }

    /**
     * @return bool
     */
    public function hasPathMappingTranslations()
    {
        return $this->hasExtraField(self::PATH_MAPPINGS_TRANSLATIONS_KEY);
    }

    /**
     * @return array
     */
    public function getMagentoDeployOverwrite()
    {
        return (array)$this->transformArrayKeysToLowerCase(
            $this->fetchVarFromExtraConfig(self::MAGENTO_DEPLOY_STRATEGY_OVERWRITE_KEY)
        );
    }

    public function getMagentoMapOverwrite()
    {
        return $this->transformArrayKeysToLowerCase(
            (array)$this->fetchVarFromExtraConfig(self::MAGENTO_MAP_OVERWRITE_KEY)
        );
    }
    protected function hasExtraField($key)
    {
        return (bool)!is_null($this->fetchVarFromExtraConfig($key));
    }

    /**
     * @param $key
     * @param $value
     */
    protected function updateExtraConfig($key, $value)
    {
        $this->extra[$key] = $value;
        $this->updateExtraJson();
    }

    /**
     * @throws \Exception
     */
    protected function updateExtraJson()
    {
        $composerFile = Factory::getComposerFile();

        if (!file_exists($composerFile) && !file_put_contents($composerFile, "{\n}\n")) {
            throw new Exception(sprintf('%s could not be created', $composerFile));
        }

        if (!is_readable($composerFile)) {
            throw new Exception(sprintf('%s is not readable', $composerFile));
        }

        if (!is_writable($composerFile)) {
            throw new Exception(sprintf('%s is not writable', $composerFile));
        }

        $json = new JsonFile($composerFile);
        $composer = $json->read();

        $baseExtra = array_key_exists(self::EXTRA_KEY, $composer)
            ? $composer[self::EXTRA_KEY]
            : array();

        if (!$this->updateFileCleanly($json, $baseExtra, $this->extra, self::EXTRA_KEY)) {
            foreach ($this->extra as $key => $value) {
                $baseExtra[$key] = $value;
            }

            $composer[self::EXTRA_KEY] = $baseExtra;
            $json->write($composer);
        }
    }

    /**
     * @param JsonFile $json
     * @param array    $base
     * @param array    $new
     * @param          $rootKey
     *
     * @return bool
     */
    private function updateFileCleanly(JsonFile $json, array $base, array $new, $rootKey)
    {
        $contents = file_get_contents($json->getPath());

        $manipulator = new JsonManipulator($contents);

        foreach ($new as $childKey => $childValue) {
            if (!$manipulator->addLink($rootKey, $childKey, $childValue)) {
                return false;
            }
        }

        file_put_contents($json->getPath(), $manipulator->getContents());

        return true;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function transformArrayKeysToLowerCase(array $array)
    {
        return array_change_key_case($array, CASE_LOWER);
    }

    public function getComposerRepositories()
    {
        return $this->fetchVarFromConfigArray($this->composerConfig, 'repositories', array());
    }

    /**
     * Get Composer vendor directory
     *
     * @return string
     */
    public function getVendorDir()
    {
        return $this->fetchVarFromConfigArray(
            isset($this->composerConfig['config']) ? $this->composerConfig['config'] : array(),
            'vendor-dir',
            getcwd() . '/vendor'
        );
    }

    /**
     * @return boolean
     */
    public function mustApplyBootstrapPatch()
    {
        return (bool) $this->fetchVarFromExtraConfig(self::EXTRA_WITH_BOOTSTRAP_PATCH_KEY, true);
    }

    /**
     * @return boolean
     */
    public function skipSuggestComposerRepositories()
    {
        return (bool) $this->fetchVarFromExtraConfig(self::EXTRA_WITH_SKIP_SUGGEST_KEY, false);
    }

    /**
     * @param $includeRootPackage
     */
    public function setIncludeRootPackage($includeRootPackage)
    {
        $this->updateExtraConfig(self::INCLUDE_ROOT_PACKAGE_KEY, trim($includeRootPackage));
    }

    /**
     * @return bool
     */
    public function getIncludeRootPackage()
    {
        return (bool)$this->fetchVarFromExtraConfig(self::INCLUDE_ROOT_PACKAGE_KEY);
    }

    /**
     * Get dev mode
     *
     * @return bool
     */
    public function isDevMode()
    {
        return $this->isDevMode;
    }

    /**
     * Dev mode
     */
    public function setDevMode()
    {
        $this->isDevMode = true;
    }

    /**
     * No dev mode
     */
    public function setNoDevMode()
    {
        $this->isDevMode = false;
    }

}
