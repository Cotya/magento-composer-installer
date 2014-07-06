<?php
/**
 * Core.php
 *
 * PHP Version 5
 *
 * @category  Brandung
 * @package   Brandung_magento-composer-installer
 * @author    David Verholen <david.verholen@brandung.de>
 * @copyright 2014 Brandung GmbH & Co Kg
 * @license   http://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link      http://www.brandung.de
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

use Composer\Util\Filesystem;


/**
 * Class Core
 *
 * @category  Brandung
 * @package   Brandung_magento-composer-installer
 * @author    David Verholen <david.verholen@brandung.de>
 * @copyright 2014 Brandung GmbH & Co Kg
 * @license   http://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link      http://www.brandung.de
 */
class Core extends Copy
{
    const DS = DIRECTORY_SEPARATOR;

    const BACKUP_DIR = 'persistent';

    const DEFAULT_MAGENTO_ROOT = 'magento';

    /**
     * Directories that need write Permissions for the Web Server
     *
     * @var array
     */
    protected $magentoWritableDirs
        = array(
            'app/etc',
            'media',
            'var'
        );

    /**
     * Directories that persist between Updates
     *
     * @var array
     */
    protected $persistentDirs
        = array(
            'media',
            'var'
        );

    /**
     * fs
     *
     * @var Filesystem
     */
    protected $fs;

    public function __construct($sourceDir, $destDir)
    {
        parent::__construct($sourceDir, $destDir);
        $this->fs = new Filesystem();
    }

    /**
     * beforeDeploy
     *
     * @return void
     */
    protected function beforeClean()
    {
        parent::beforeDeploy();
        if (!file_exists($this->destDir) || !is_dir($this->destDir)) {
            return;
        }

        $this->fs->ensureDirectoryExists(self::BACKUP_DIR);
        $this->movePersistentFiles($this->destDir, self::BACKUP_DIR);
    }

    /**
     * afterClean
     *
     * @return void
     */
    protected function afterClean()
    {
        parent::afterClean();
        $this->emptyDir($this->destDir);
    }


    /**
     * afterDeploy
     *
     * @return void
     */
    protected function afterDeploy()
    {
        parent::afterDeploy();
        if (!file_exists(self::BACKUP_DIR) || !is_dir(self::BACKUP_DIR)) {
            return;
        }
        $this->movePersistentFiles(self::BACKUP_DIR, $this->destDir);
        $this->rrmdir(self::BACKUP_DIR);
    }

    /**
     * getLocalXmlPath
     *
     * @return string
     */
    protected function getLocalXmlPath()
    {
        return 'app' . self::DS . 'etc' . self::DS . 'local.xml';
    }

    protected function movePersistentFiles($sourceDir, $targetDir)
    {
        $this->movePersistentDirectories($sourceDir, $targetDir);
        $this->moveLocalXml($sourceDir, $targetDir);
    }

    protected function moveLocalXml($sourceDir, $targetDir)
    {
        $source = $sourceDir . self::DS . $this->getLocalXmlPath();
        $target = $targetDir . self::DS . $this->getLocalXmlPath();
        if (file_exists($source)) {
            $this->fs->ensureDirectoryExists(dirname($target));
            rename($source, $target);
        }
    }

    protected function movePersistentDirectories($sourceDir, $targetDir)
    {
        foreach ($this->persistentDirs as $dir) {
            $source = $sourceDir . self::DS . $dir;
            $target = $targetDir . self::DS . $dir;
            if (file_exists($source)) {
                $this->emptyDir($target);
                $this->fs->copyThenRemove($source, $target);
            }
        }
    }

    /**
     * rrmdir
     *
     * @param string $dirPath path of the dir to recursively remove
     *
     * @return bool
     */
    protected function rrmdir($dirPath)
    {
        if (file_exists($dirPath)) {
            foreach (
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $dirPath,
                        \FilesystemIterator::SKIP_DOTS
                    ),
                    \RecursiveIteratorIterator::CHILD_FIRST
                ) as $path
            ) {
                /* @var \SplFileInfo $path */
                $path->isDir()
                    ? rmdir($path->getPathname())
                    : unlink($path->getPathname());
            }
            return rmdir($dirPath);
        }
        return false;
    }

    /**
     * emptyDir
     *
     * @param $dir
     *
     * @return void
     */
    protected function emptyDir($dir)
    {
        $this->rrmdir($dir);
        $this->fs->ensureDirectoryExists($dir);
    }
} 
