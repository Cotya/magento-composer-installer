<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento\FullStack;

use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{

    protected static $processLogCounter = 1;

    public static function setUpBeforeClass()
    {
        $process = new Process(
            'perl -pi -e \'s/"test_version"/"version"/g\' ./composer.json',
            self::getProjectRoot()
        );
        $process->run();
        if ($process->getExitCode() !== 0) {
            $message = 'process for <code>'.$process->getCommandLine().'</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText();
            $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
            $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
            echo $message;
        }
        
        @unlink(self::getProjectRoot().'/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getBasePath().'/magento/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getBasePath().'/magento-modules/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getProjectRoot().'/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        @unlink(self::getBasePath().'/magento/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        @unlink(self::getBasePath().'/magento-modules/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        
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
        }else{
            self::logProcessOutput($process,'createComposerArtifact');
        }

    }
    
    public static function tearDownAfterClass()
    {
        $process = new Process(
            'perl -pi -e \'s/"version"/"test_version"/g\' ./composer.json',
            self::getProjectRoot()
        );
        $process->run();
        if ($process->getExitCode() !== 0) {
            $message = 'process for <code>'.$process->getCommandLine().'</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText();
            $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
            $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
            echo $message;
        }
    }

    protected static function getBasePath(){
        return realpath(__DIR__.'/../../../../FullStackTest');
    }

    protected static function getProjectRoot(){
        return realpath(__DIR__.'/../../../../..');
    }

    protected static function getComposerCommand(){
        $command = 'composer.phar';
        if( getenv('TRAVIS') == "true" ){
            $command = self::getProjectRoot().'/composer.phar';
        }
        return $command;
    }

    protected static function getComposerArgs(){
        return '--prefer-dist --no-dev --no-progress --no-interaction --profile -vvv';
    }

    protected static function logProcessOutput(Process $process, $name = null){
        if($name === null){
            $name = self::$processLogCounter;
            self::$processLogCounter++;
        }
        file_put_contents( self::getBasePath().'/'.get_called_class().'_'.$name.'Output.log', $process->getCommandLine() ."\n\n". $process->getOutput() );
    }

    public function assertProcess(Process $process)
    {
        $message = 'process for <code>'.$process->getCommandLine().
            '</code> exited with '.$process->getExitCode().': '.$process->getExitCodeText().
            'from class '.get_class($this);
        $message .= PHP_EOL.'Error Message:'.PHP_EOL.$process->getErrorOutput();
        $message .= PHP_EOL.'Output:'.PHP_EOL.$process->getOutput();
        $this->assertEquals(0, $process->getExitCode(), $message);
    }
} 