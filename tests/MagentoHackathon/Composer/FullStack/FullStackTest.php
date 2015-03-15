<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

class FullStackTest extends FullStack\AbstractTest
{
    

    protected function setUp()
    {
        
    }
    
    protected function tearDown()
    {
        
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $packagesPath    = self::getProjectRoot() .'/tests/res/packages';
        $directory = new \DirectoryIterator($packagesPath);
        /** @var \DirectoryIterator $fileinfo */
        foreach ($directory as $file) {
            if (!$file->isDot() && $file->isDir()) {
                $args = ' archive --format=zip --dir="../../../../tests/FullStackTest/artifact" -vvv';
                $process = new Process(
                    self::getComposerCommand() . $args,
                    $file->getPathname()
                );
                $process->run();
                if ($process->getExitCode() !== 0) {
                    $message = sprintf(
                        "process for <code>%s</code> exited with %s: %s%sError Message:%s%s%sOutput:%s%s",
                        $process->getCommandLine(),
                        $process->getExitCode(),
                        $process->getExitCodeText(),
                        PHP_EOL,
                        PHP_EOL,
                        $process->getErrorOutput(),
                        PHP_EOL,
                        PHP_EOL,
                        $process->getOutput()
                    );
                    echo $message;
                }
            }
        }
    }
    
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }
    
    protected function prepareCleanDirectories()
    {
        $fs = new Filesystem();
        $fs->removeDirectory(self::getBasePath().'/htdocs');
        $fs->ensureDirectoryExists(self::getBasePath().'/htdocs');

        $fs->removeDirectory(self::getBasePath().'/magento/vendor');
        $fs->remove(self::getBasePath().'/magento/composer.lock');
        $fs->removeDirectory(self::getBasePath().'/magento-modules/vendor');
        $fs->remove(self::getBasePath().'/magento-modules/composer.lock');
    }
    
    protected function installBaseMagento()
    {
        $process = new Process(
            self::getComposerCommand().' install '.self::getComposerArgs().' --working-dir="./"',
            self::getBasePath().'/magento'
        );
        $process->setTimeout(300);
        $process->run();
        self::logProcessOutput($process, 'installBaseMagento');
        $this->assertProcess($process);
    }
    
    protected function getMethodRunConfigs()
    {
        $array = array(
            'symlink' => array(
                1 => array(
                    'module_composer_json' => "composer_1.json",
                ),
                2 => array(
                    'module_composer_json' => "composer_2.json",
                ),
                3 => array(
                    'module_composer_json' => "composer_1.json",
                ),
            ),
            'copy' => array(
                1 => array(
                    'module_composer_json' => "composer_1_copy.json",
                ),
                2 => array(
                    'module_composer_json' => "composer_2_copy.json",
                ),
                3 => array(
                    'module_composer_json' => "composer_1_copy.json",
                ),
            ),
            'copy_force' => array(
                1 => array(
                    'module_composer_json' => "composer_1_copy_force.json",
                ),
                2 => array(
                    'module_composer_json' => "composer_2_copy_force.json",
                ),
                3 => array(
                    'module_composer_json' => "composer_1_copy_force.json",
                ),
            ),
            
        );
        
        return $array;
    }
    
    public function methodProvider()
    {
        return array(
            array('symlink'),
            array('copy'),
            array('copy_force'),
        );
    }

    /**
     * @dataProvider methodProvider
     */
    public function testEverything($method)
    {

        $this->assertFileExists(
            self::getBasePath() . '/artifact/magento-hackathon-magento-composer-installer-999.0.0.zip'
        );

        $methods = $this->getMethodRunConfigs();
        
        $runs = $methods[$method];
            
            $this->prepareCleanDirectories();

            $this->installBaseMagento();

        foreach ($runs as $run => $value) {
            $this->changeModuleComposerFileAndUpdate(
                $value['module_composer_json'],
                ($run===1) ? 'install' : 'update'
            );

            switch($run){
                case 1:
                case 3:
                    foreach ($this->getFirstOnlyFileTestSet() + $this->getFirstExistTestSet() as $file) {
                        $this->assertFileExists(self::getBasePath().'/htdocs/'.$file);
                    }
                    foreach ($this->getFirstNotExistTestSet() as $file) {
                        $this->assertFileNotExists(self::getBasePath().'/htdocs/'.$file);
                    }
                    if ($method==="copy_force") {
                        $this->assertStringEqualsFile(
                            self::getBasePath().'/htdocs/'.'app/design/frontend/test/default/installSort/test1.phtml',
                            'testcontent2'
                        );
                        $this->assertStringEqualsFile(
                            self::getBasePath().'/htdocs/'.'app/design/frontend/test/default/installSort/test2.phtml',
                            'testcontent3'
                        );
                    }
                    break;
                case 2:
                    if ($method==="symlink") {
                        foreach ($this->getFirstOnlyFileTestSet() as $file) {
                            $this->assertFileNotExists(self::getBasePath().'/htdocs/'.$file);
                        }
                    }
                    foreach ($this->getSecondExistTestSet() as $file) {
                        $this->assertFileExists(self::getBasePath().'/htdocs/'.$file);
                    }
                    break;
            }
        }
    }
    
    protected function changeModuleComposerFileAndUpdate($file, $command = "update")
    {
        $magentoModuleComposerFile = self::getBasePath().'/magento-modules/composer.json';
        if (file_exists($magentoModuleComposerFile)) {
            unlink($magentoModuleComposerFile);
        }
        copy(
            self::getBasePath().'/magento-modules/'.$file,
            $magentoModuleComposerFile
        );

        $process = new Process(
            sprintf(
                '%s %s %s  --optimize-autoloader --working-dir="./"',
                self::getComposerCommand(),
                $command,
                self::getComposerArgs()
            ),
            self::getBasePath().'/magento-modules'
        );
        $process->setTimeout(300);
        $process->run();
        self::logProcessOutput($process);
        $this->assertProcess($process);
    }
    
    protected function getFirstOnlyFileTestSet()
    {
        return array(
            'app/etc/modules/Aoe_Profiler.xml',
            'app/design/frontend/test/default/issue76/Foobar/issue76.phtml',
            'app/design/frontend/wildcard/wildcard.phtml',
            'composer_lib/autoload.php',
            'composer_lib/magento-hackathon/magento-composer-installer-test-library/composer.json',
            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
        );
    }

    protected function getFirstNotExistTestSet()
    {
        return array(
            'app/design/frontend/test/default/issue76/Foobar/Foobar/issue76.phtml',
            'app/design/frontend/frontend/wildcard/wildcard.phtml',
            'app/app/code/test.php',
            'index.php',
            'shell/compiler.php',
//            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
            'app/design/frontend/test/default/updateFileRemove/test2.phtml',
        );
    }

    protected function getFirstExistTestSet()
    {
        return array(
            'app/design/frontend/test/default/updateFileRemove/design/test1.phtml',
            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
              'shell/log.php',
        );
    }

    protected function getSecondExistTestSet()
    {
        return array(
            'app/design/frontend/test/default/updateFileRemove/design/test1.phtml',
        );
    }
}
