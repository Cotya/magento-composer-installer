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
        $fs = new Filesystem();
        
        $fs->removeDirectory( self::getBasePath().'/htdocs' );
        $fs->ensureDirectoryExists( self::getBasePath().'/htdocs' );

        $fs->removeDirectory( self::getBasePath().'/magento/vendor' );
        $fs->remove( self::getBasePath().'/magento/composer.lock' );
        $fs->removeDirectory( self::getBasePath().'/magento-modules/vendor' );
        $fs->remove( self::getBasePath().'/magento-modules/composer.lock' );


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
            $command = 'composer';
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

    public function testFirstInstall()
    {
        $process = new Process(
            self::getComposerCommand().' install '.self::getComposerArgs().' --working-dir="./magento"',
            self::getBasePath()
        );
        $process->setTimeout(300);
        $process->run();
        $this->assertProcess($process);
        
        $magentoModuleComposerFile = self::getBasePath().'/magento-modules/composer.json';
        if(file_exists($magentoModuleComposerFile)){
            unlink($magentoModuleComposerFile);
        }
        copy(
            self::getBasePath().'/magento-modules/composer_1.json',
            $magentoModuleComposerFile
        );
        
        $process = new Process(
            self::getComposerCommand().' install '.self::getComposerArgs().' --optimize-autoloader --working-dir="./magento-modules"',
            self::getBasePath()
        );
        $process->setTimeout(300);
        $process->run();
        $this->assertProcess($process);
    }
    
    protected function getFirstFileTestSet()
    {
        return array(
            'app/etc/modules/Aoe_Profiler.xml'
        );
    }

    /**
     * @depends testFirstInstall
     */
    public function testAfterFirstInstall()
    {
        foreach($this->getFirstFileTestSet() as $file){
            $this->assertFileExists( self::getBasePath().'/htdocs/'.$file );
        }
    }

    /**
     * @depends testAfterFirstInstall
     */
    public function testFirstUpdate()
    {
        $magentoModuleComposerFile = self::getBasePath().'/magento-modules/composer.json';
        if(file_exists($magentoModuleComposerFile)){
            unlink($magentoModuleComposerFile);
        }
        copy(
            self::getBasePath().'/magento-modules/composer_2.json',
            $magentoModuleComposerFile
        );

        $process = new Process(
            self::getComposerCommand().' update '.self::getComposerArgs().' --optimize-autoloader --working-dir="./magento-modules"',
            self::getBasePath()
        );
        $process->setTimeout(300);
        $process->run();
        $this->assertProcess($process);
    }

    /**
     * @depends testFirstUpdate
     */
    public function testAfterFirstUpdate()
    {
        foreach($this->getFirstFileTestSet() as $file){
            $this->assertFileNotExists( self::getBasePath().'/htdocs/'.$file );
        }
    }

    /**
     * @depends testAfterFirstUpdate
     */
    public function testSecondUpdate()
    {
        $magentoModuleComposerFile = self::getBasePath().'/magento-modules/composer.json';
        if(file_exists($magentoModuleComposerFile)){
            unlink($magentoModuleComposerFile);
        }
        copy(
            self::getBasePath().'/magento-modules/composer_1.json',
            $magentoModuleComposerFile
        );

        $process = new Process(
            self::getComposerCommand().' update '.self::getComposerArgs().' --optimize-autoloader --working-dir="./magento-modules"',
            self::getBasePath()
        );
        $process->setTimeout(300);
        $process->run();
        $this->assertProcess($process);
    }

    /**
     * @depends testSecondUpdate
     */
    public function testAfterSecondUpdate()
    {
        foreach($this->getFirstFileTestSet() as $file){
            $this->assertFileExists( self::getBasePath().'/htdocs/'.$file );
        }
    }


}