<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

class FullStackTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        
    }
    
    protected function tearDown()
    {
        
    }

    public static function setUpBeforeClass()
    {
        $process = new Process(
            'sed -i \'s/"test_version"/"version"/g\' ./composer.json',
            self::getProjectRoot() 
        );
        $process->run();
        $process = new Process(
            self::getComposerCommand().' archive --format=zip --dir="tests/FullStackTest/artifact" -vvv',
            self::getProjectRoot()
        );
        $process->run();
        if( $process->getExitCode() !== 0){
            $message = 'process for <code>'.$process->getCommandLine().'</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText();
            $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
            $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
            echo $message;
        }
        $packagesPath    = self::getProjectRoot() .'/tests/res/packages';
        $directory = new \DirectoryIterator($packagesPath);
        /** @var \DirectoryIterator $fileinfo */
        foreach($directory as $file){
            if (!$file->isDot() && $file->isDir()) {
                $process = new Process(
                    self::getComposerCommand().' archive --format=zip --dir="../../../../tests/FullStackTest/artifact" -vvv',
                    $file->getPathname()
                );
                $process->run();
                if( $process->getExitCode() !== 0){
                    $message = 'process for <code>'.$process->getCommandLine().'</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText();
                    $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
                    $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
                    echo $message;
                }
            }
        }
    }
    
    public static function tearDownAfterClass()
    {
        $process = new Process(
            'sed -i \'s/"version"/"test_version"/g\' ./composer.json',
            self::getProjectRoot()
        );
        $process->run();
    }
    
    protected static function getBasePath(){
        return realpath(__DIR__.'/../../../FullStackTest');
    }
    
    protected static function getProjectRoot(){
        return realpath(__DIR__.'/../../../..');
    }
    
    protected static function getComposerCommand(){
        $command = 'composer.phar';
        if( getenv('TRAVIS') == "true" ){
            $command = self::getProjectRoot().'/composer.phar';
        }
        return $command;
    }
    
    protected static function getComposerArgs(){
        return '--prefer-dist --no-dev --no-progress --no-interaction --profile';
    }
    
    public function assertProcess(Process $process)
    {
        $message = 'process for <code>'.$process->getCommandLine().'</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText();
        $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
        $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
        $this->assertEquals(0, $process->getExitCode(), $message);
    }
    
    protected function prepareCleanDirectories()
    {
        $fs = new Filesystem();
        $fs->removeDirectory( self::getBasePath().'/htdocs' );
        $fs->ensureDirectoryExists( self::getBasePath().'/htdocs' );

        $fs->removeDirectory( self::getBasePath().'/magento/vendor' );
        $fs->remove( self::getBasePath().'/magento/composer.lock' );
        $fs->removeDirectory( self::getBasePath().'/magento-modules/vendor' );
        $fs->remove( self::getBasePath().'/magento-modules/composer.lock' );
    }
    
    protected function installBaseMagento()
    {
        $process = new Process(
            self::getComposerCommand().' install '.self::getComposerArgs().' --working-dir="./"',
            self::getBasePath().'/magento'
        );
        $process->setTimeout(300);
        $process->run();
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
    public function testEverything( $method )
    {

        $this->assertFileExists( self::getBasePath().'/artifact/magento-hackathon-magento-composer-installer-999.0.0.zip' );

        $methods = $this->getMethodRunConfigs();
        
        $runs = $methods[$method];
            
            $this->prepareCleanDirectories();

            $this->installBaseMagento();

            foreach( $runs as $run => $value){
                $this->changeModuleComposerFileAndUpdate(
                    $value['module_composer_json'],
                    ($run===1) ? 'install' : 'update'
                );

                switch($run){
                    case 1:
                    case 3:
                        foreach( 
                            $this->getFirstOnlyFileTestSet()
                            + $this->getFirstExistTestSet()
                            as $file){
                            $this->assertFileExists( self::getBasePath().'/htdocs/'.$file );
                        }
                        foreach($this->getFirstNotExistTestSet() as $file){
                            $this->assertFileNotExists( self::getBasePath().'/htdocs/'.$file );
                        }
                        break;
                    case 2:
                        if($method==="symlink"){
                            foreach($this->getFirstOnlyFileTestSet() as $file){
                                $this->assertFileNotExists( self::getBasePath().'/htdocs/'.$file );
                            }
                        }
                        foreach($this->getSecondExistTestSet() as $file){
                            $this->assertFileExists( self::getBasePath().'/htdocs/'.$file );
                        }
                        break;
                }

            }
            

        
    }
    
    protected function changeModuleComposerFileAndUpdate($file, $command = "update")
    {
        $magentoModuleComposerFile = self::getBasePath().'/magento-modules/composer.json';
        if(file_exists($magentoModuleComposerFile)){
            unlink($magentoModuleComposerFile);
        }
        copy(
            self::getBasePath().'/magento-modules/'.$file,
            $magentoModuleComposerFile
        );

        $process = new Process(
            self::getComposerCommand().' '.$command.' '.self::getComposerArgs().' --optimize-autoloader --working-dir="./"',
            self::getBasePath().'/magento-modules'
        );
        $process->setTimeout(300);
        $process->run();
        $this->assertProcess($process);
    }
    
    protected function getFirstOnlyFileTestSet()
    {
        return array(
            'app/etc/modules/Aoe_Profiler.xml',
            'app/design/frontend/test/default/issue76/subdir/subdir/issue76.phtml',
//            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
        );
    }

    protected function getFirstNotExistTestSet()
    {
        return array(
            'app/design/frontend/test/default/issue76/subdir/issue76.phtml',
            'app/design/frontend/test/default/issue76/design/subdir/subdir/issue76.phtml',
//            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
//            'app/design/frontend/test/default/updateFileRemove/test2.phtml',
        );
    }

    protected function getFirstExistTestSet()
    {
        return array(
//            'app/design/frontend/test/default/updateFileRemove/design/test1.phtml',
//            'app/design/frontend/test/default/updateFileRemove/design/test2.phtml',
        );
    }

    protected function getSecondExistTestSet()
    {
        return array(
//            'app/design/frontend/test/default/updateFileRemove/design/test1.phtml',
        );
    }




}