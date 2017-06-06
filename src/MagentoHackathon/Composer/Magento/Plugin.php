<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\Config;
use Composer\DependencyResolver\Rule;
use Composer\Installer;
use Composer\Package\AliasPackage;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use MagentoHackathon\Composer\Helper;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Factory\DeploystrategyFactory;
use MagentoHackathon\Composer\Magento\Factory\EntryFactory;
use MagentoHackathon\Composer\Magento\Factory\ParserFactory;
use MagentoHackathon\Composer\Magento\Factory\PathTranslationParserFactory;
use MagentoHackathon\Composer\Magento\Patcher\Bootstrap;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageFileSystemRepository;
use MagentoHackathon\Composer\Magento\UnInstallStrategy\UnInstallStrategy;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * The type of packages this plugin supports
     */
    const PACKAGE_TYPE = 'magento-module';

    const VENDOR_DIR_KEY = 'vendor-dir';

    const BIN_DIR_KEY = 'bin-dir';

    const THESEER_AUTOLOAD_EXEC_BIN_PATH = '/phpab';

    const THESEER_AUTOLOAD_EXEC_REL_PATH = '/theseer/autoload/composer/bin/phpab';

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var DeployManager
     */
    protected $deployManager;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var EntryFactory
     */
    protected $entryFactory;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * init the DeployManager
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    protected function initDeployManager(Composer $composer, IOInterface $io, EventManager $eventManager)
    {
        $this->deployManager = new DeployManager($eventManager);
        $this->deployManager->setSortPriority($this->getSortPriority($composer));

        $this->applyEvents($eventManager);
    }

    protected function applyEvents(EventManager $eventManager)
    {

        if ($this->config->hasAutoAppendGitignore()) {
            $gitIgnoreLocation = sprintf('%s/.gitignore', $this->config->getMagentoRootDir());
            $gitIgnore = new GitIgnoreListener(new GitIgnore($gitIgnoreLocation));

            $eventManager->listen('post-package-deploy', [$gitIgnore, 'addNewInstalledFiles']);
            $eventManager->listen('post-package-uninstall', [$gitIgnore, 'removeUnInstalledFiles']);
        }

        $io = $this->io;
        if ($this->io->isDebug()) {
            $eventManager->listen('pre-package-deploy', function (PackageDeployEvent $event) use ($io) {
                $io->write('Start magento deploy for ' . $event->getDeployEntry()->getPackageName());
            });
        }
    }

    /**
     * get Sort Priority from extra Config
     *
     * @param \Composer\Composer $composer
     *
     * @return array
     */
    private function getSortPriority(Composer $composer)
    {
        $extra = $composer->getPackage()->getExtra();

        return isset($extra[ProjectConfig::SORT_PRIORITY_KEY])
            ? $extra[ProjectConfig::SORT_PRIORITY_KEY]
            : array();
    }

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->composer = $composer;

        $this->filesystem = new Filesystem();
        $this->config = new ProjectConfig($composer->getPackage()->getExtra(), $composer->getConfig()->all());

        if (!$this->config->skipSuggestComposerRepositories()) {
            $this->suggestComposerRepositories();
        }

        $this->entryFactory = new EntryFactory(
            $this->config,
            new DeploystrategyFactory($this->config),
            new PathTranslationParserFactory(new ParserFactory($this->config), $this->config)
        );

        $this->initDeployManager($composer, $io, $this->getEventManager());
        $this->writeDebug('activate magento plugin');
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            Installer\PackageEvents::PRE_PACKAGE_UPDATE => array(
                array('onPackageUpdate', 0),
            ),
            ScriptEvents::POST_INSTALL_CMD => array(
                array('onNewCodeEvent', 0),
            ),
            ScriptEvents::POST_UPDATE_CMD  => array(
                array('onNewCodeEvent', 0),
            ),
        );
    }

    /**
     * event listener is named this way, as it listens for events leading to changed code files
     *
     * @param Event $event
     */
    public function onNewCodeEvent(Event $event)
    {

        $packageTypeToMatch = static::PACKAGE_TYPE;
        $magentoModules = array_filter(
            $this->composer->getRepositoryManager()->getLocalRepository()->getPackages(),
            function (PackageInterface $package) use ($packageTypeToMatch) {
                if ($package instanceof AliasPackage) {
                    return false;
                }
                return $package->getType() === $packageTypeToMatch;
            }
        );

        if ($this->composer->getPackage()->getType() === static::PACKAGE_TYPE
            && $this->config->getIncludeRootPackage() === true
        ) {
            $magentoModules[] = $this->composer->getPackage();
        }

        $vendorDir = rtrim($this->composer->getConfig()->get(self::VENDOR_DIR_KEY), '/');

        Helper::initMagentoRootDir(
            $this->config,
            $this->io,
            $this->filesystem,
            $vendorDir
        );

        if ($event->isDevMode()) {
            $this->config->setDevMode();
        }

        $this->applyEvents($this->getEventManager());

        if (in_array('--redeploy', $event->getArguments())) {
            $this->writeDebug('remove all deployed modules');
            $this->getModuleManager()->updateInstalledPackages(array());
        }
        $this->writeDebug('start magento module deploy via moduleManager');
        $this->getModuleManager()->updateInstalledPackages($magentoModules);
        $this->deployLibraries();

        $patcher = Bootstrap::fromConfig($this->config);
        $patcher->setIo($this->io);
        try {
            $patcher->patch();
        } catch (\DomainException $e) {
            $this->io->write('<comment>'.$e->getMessage().'</comment>');
        }
    }

    public function onPackageUpdate(PackageEvent $event)
    {
        /** @var Rule $rule */
        $rule = $event->getOperation()->getReason();
        if ($rule instanceof Rule) {
            if ($event->getOperation()->getJobType() === 'update') {
                if ($rule->getJob()['packageName'] === 'magento-hackathon/magento-composer-installer') {
                    throw new \Exception(
                        'Dont update the "magento-hackathon/magento-composer-installer" with active plugins.' . PHP_EOL .
                        'Consult the documentation on how to update the Installer' . PHP_EOL .
                        'https://github.com/Cotya/magento-composer-installer#update-the-installer' . PHP_EOL
                    );
                }
            }
        } else {
            
        }
        
    }
    
    /**
     * test configured repositories and give message about adding recommended ones
     */
    protected function suggestComposerRepositories()
    {
        $foundFiregento = false;
        $foundMagento   = false;

        foreach ($this->config->getComposerRepositories() as $repository) {
            if (!isset($repository["type"]) || $repository["type"] !== "composer") {
                continue;
            }
            if (strpos($repository["url"], "packages.firegento.com") !== false) {
                $foundFiregento = true;
            }
        };
        $message1 = "<comment>you may want to add the %s repository to composer.</comment>";
        $message2 = "<comment>add it with:</comment> composer.phar config -g repositories.%s composer %s";
        if (!$foundFiregento) {
            $this->io->write(sprintf($message1, 'packages.firegento.com'));
            $this->io->write(sprintf($message2, 'firegento', 'https://packages.firegento.com'));
        }
    }

    /**
     * deploy Libraries
     */
    protected function deployLibraries()
    {
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $autoloadDirectories = array();

        $libraryPath = $this->config->getLibraryPath();

        if ($libraryPath === null) {
            $this->writeDebug('jump over deployLibraries as no Magento libraryPath is set');

            return;
        }

        $vendorDir = rtrim($this->composer->getConfig()->get(self::VENDOR_DIR_KEY), '/');

        $this->filesystem->removeDirectory($libraryPath);
        $this->filesystem->ensureDirectoryExists($libraryPath);

        foreach ($packages as $package) {
            /** @var PackageInterface $package */
            $packageConfig = $this->config->getLibraryConfigByPackagename($package->getName());
            if ($packageConfig === null) {
                continue;
            }
            if (!isset($packageConfig['autoload'])) {
                $packageConfig['autoload'] = array('/');
            }
            foreach ($packageConfig['autoload'] as $path) {
                $autoloadDirectories[] = $libraryPath . '/' . $package->getName() . "/" . $path;
            }
            $this->writeDebug(sprintf('Magento deployLibraries executed for %s', $package->getName()));

            $libraryTargetPath = $libraryPath . '/' . $package->getName();
            $this->filesystem->removeDirectory($libraryTargetPath);
            $this->filesystem->ensureDirectoryExists($libraryTargetPath);
            $this->copyRecursive($vendorDir . '/' . $package->getPrettyName(), $libraryTargetPath);
        }

        if (false !== ($executable = $this->getTheseerAutoloadExecutable())) {
            $this->writeDebug('Magento deployLibraries executes autoload generator');

            $params = $this->getTheseerAutoloadParams($libraryPath, $autoloadDirectories);

            $process = new Process($executable . $params);
            $process->run();
        }
    }

    /**
     * return the autoload generator binary path or false if not found
     *
     * @return bool|string
     */
    protected function getTheseerAutoloadExecutable()
    {
        $executable = $this->composer->getConfig()->get(self::BIN_DIR_KEY)
            . self::THESEER_AUTOLOAD_EXEC_BIN_PATH;

        if (!file_exists($executable)) {
            $executable = $this->composer->getConfig()->get(self::VENDOR_DIR_KEY)
                . self::THESEER_AUTOLOAD_EXEC_REL_PATH;
        }

        if (!file_exists($executable)) {
            $this->writeDebug(
                'Magento deployLibraries autoload generator not available, you should require "theseer/autoload"',
                $executable
            );

            return false;
        }

        return $executable;
    }

    /**
     * get Theseer Autoload Generator Params
     *
     * @param string $libraryPath
     * @param array  $autoloadDirectories
     *
     * @return string
     */
    protected function getTheseerAutoloadParams($libraryPath, $autoloadDirectories)
    {
        // @todo  --blacklist 'test\\\\*'
        return " -b {$libraryPath} -o {$libraryPath}/autoload.php  " . implode(' ', $autoloadDirectories);
    }

    /**
     * Copy then delete is a non-atomic version of {@link rename}.
     *
     * Some systems can't rename and also don't have proc_open,
     * which requires this solution.
     *
     * copied from \Composer\Util\Filesystem::copyThenRemove and removed the remove part
     *
     * @param string $source
     * @param string $target
     */
    protected function copyRecursive($source, $target)
    {
        $it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);
        $this->filesystem->ensureDirectoryExists($target);

        foreach ($ri as $file) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathName();
            if ($file->isDir()) {
                $this->filesystem->ensureDirectoryExists($targetPath);
            } else {
                copy($file->getPathname(), $targetPath);
            }
        }
    }

    /**
     * print Debug Message
     *
     * @param $message
     */
    private function writeDebug($message, $varDump = null)
    {
        if ($this->io->isDebug()) {
            $this->io->write($message);

            if (!is_null($varDump)) {
                var_dump($varDump);
            }
        }
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    public function getPackageInstallPath(PackageInterface $package)
    {
        $vendorDir = realpath(rtrim($this->composer->getConfig()->get('vendor-dir'), '/'));
        return sprintf('%s/%s', $vendorDir, $package->getPrettyName());
    }

    /**
     * @return EventManager
     */
    protected function getEventManager()
    {
        if (null === $this->eventManager) {
            $this->eventManager = new EventManager;
        }

        return $this->eventManager;
    }

    /**
     * @return ModuleManager
     */
    protected function getModuleManager()
    {
        if (null === $this->moduleManager) {
            $this->moduleManager = new ModuleManager(
                new InstalledPackageFileSystemRepository(
                    rtrim($this->composer->getConfig()->get(self::VENDOR_DIR_KEY), '/') . '/installed.json',
                    new InstalledPackageDumper()
                ),
                $this->getEventManager(),
                $this->config,
                new UnInstallStrategy($this->filesystem, $this->config->getMagentoRootDir()),
                new InstallStrategyFactory($this->config, new ParserFactory($this->config))
            );
        }

        return $this->moduleManager;
    }
}
