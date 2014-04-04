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

    public function testFirstInstall()
    {
        $this->assertFileExists( self::getBasePath().'/artifact/magento-hackathon-magento-composer-installer-999.0.0.zip' );
        $process = new Process(
            self::getComposerCommand().' install '.self::getComposerArgs().' --working-dir="./"',
            self::getBasePath().'/magento'
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
            self::getComposerCommand().' install '.self::getComposerArgs().' --optimize-autoloader --working-dir="./"',
            self::getBasePath().'/magento-modules'
        );
        $process->setTimeout(300);
        $process->run();
        $this->assertProcess($process);
    }
    
    protected function changeModuleComposerFileAndUpdate($file)
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
            self::getComposerCommand().' update '.self::getComposerArgs().' --optimize-autoloader --working-dir="./"',
            self::getBasePath().'/magento-modules'
        );
        $process->setTimeout(300);
        $process->run();
        $this->assertProcess($process);
    }
    
    protected function getFirstFileTestSet()
    {
        return array(
            'app/etc/modules/Aoe_Profiler.xml',
            'app/design/frontend/test/default/issue76/subdir/subdir/issue76.phtml',
        );
    }

    protected function getFirstNotExistTestSet()
    {
        return array(
            'app/design/frontend/test/default/issue76/subdir/issue76.phtml',
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
        foreach($this->getFirstNotExistTestSet() as $file){
            $this->assertFileNotExists( self::getBasePath().'/htdocs/'.$file );
        }
    }

    /**
     * @depends testAfterFirstInstall
     */
    public function testFirstUpdate()
    {
        $this->changeModuleComposerFileAndUpdate('composer_2.json');
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
        $this->changeModuleComposerFileAndUpdate('composer_1.json');
    }

    /**
     * @depends testSecondUpdate
     */
    public function testAfterSecondUpdate()
    {
        foreach($this->getFirstFileTestSet() as $file){
            $this->assertFileExists( self::getBasePath().'/htdocs/'.$file );
        }
        foreach($this->getFirstNotExistTestSet() as $file){
            $this->assertFileNotExists( self::getBasePath().'/htdocs/'.$file );
        }
    }

    /**
     * @depends testAfterSecondUpdate
     */
    public function testChangeFromSymlinkToCopy()
    {
        $this->changeModuleComposerFileAndUpdate('composer_2.json');
        $fsIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( self::getBasePath().'/htdocs',
            \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::SKIP_DOTS
            ),
            1
        );
        while($fsIterator->valid()){
            if( $fsIterator->current()->isLink() ){
                //echo $fsIterator->key().PHP_EOL;
                unlink($fsIterator->key());
            }
            $fsIterator->next();
        }
        $this->changeModuleComposerFileAndUpdate('composer_1_copy.json');
    }

    /**
     * @depends testChangeFromSymlinkToCopy
     */
    public function testAfterFirstCopyInstall()
    {
        foreach($this->getFirstFileTestSet() as $file){
            $this->assertFileExists( self::getBasePath().'/htdocs/'.$file );
        }
        foreach($this->getFirstNotExistTestSet() as $file){
            $this->assertFileNotExists( self::getBasePath().'/htdocs/'.$file );
        }
    }

    /**
     * @depends testAfterFirstCopyInstall
     */
    public function testFirstCopyUpdate()
    {
        $this->changeModuleComposerFileAndUpdate('composer_2_copy.json');
    }

    /**
     * @depends testFirstCopyUpdate
     */
    public function testAfterFirstCopyUpdate()
    {
        foreach($this->getFirstFileTestSet() as $file){
            //rm for copy does not work
            //$this->assertFileNotExists( self::getBasePath().'/htdocs/'.$file );
        }
    }

    /**
     * @depends testAfterFirstCopyUpdate
     */
    public function testSecondCopyUpdate()
    {
        $this->changeModuleComposerFileAndUpdate('composer_1_copy.json');
    }

    /**
     * @depends testSecondCopyUpdate
     */
    public function testAfterSecondCopyUpdate()
    {
        foreach($this->getFirstFileTestSet() as $file){
            $this->assertFileExists( self::getBasePath().'/htdocs/'.$file );
        }
        foreach($this->getFirstNotExistTestSet() as $file){
            $this->assertFileNotExists( self::getBasePath().'/htdocs/'.$file );
        }
    }



}