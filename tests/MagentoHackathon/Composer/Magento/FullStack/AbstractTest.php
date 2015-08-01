<?php

namespace MagentoHackathon\Composer\Magento\FullStack;

use Symfony\Component\Process\Process;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    private static $composerCommandPath;

    protected static $processLogCounter = 1;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }

    protected static function getBasePath()
    {
        return str_replace('\\', '/', realpath(__DIR__.'/../../../../FullStackTest'));
    }

    protected static function getProjectRoot()
    {
        return str_replace('\\', '/', realpath(__DIR__.'/../../../../..'));
    }

    private static function resolveComposerCommand()
    {
        if (getenv('TRAVIS') == "true") {
            $command = self::getProjectRoot() . '/composer.phar';
        } elseif (getenv('APPVEYOR') == 'True ') {
            $command = 'php composer.phar';
        } elseif (self::runInProjectRoot('./composer.phar --version')->getExitCode() === 0) {
            $command = 'composer.phar';
        } else {
            $command = 'composer';
        }
        return $command;
    }

    protected static function getComposerCommand()
    {
        if (self::$composerCommandPath === null) {
            self::$composerCommandPath = self::resolveComposerCommand();
        }
        return self::$composerCommandPath;
    }

    protected static function getComposerArgs()
    {
        return '--prefer-dist --no-dev --no-progress --no-interaction --profile -vvv';
    }

    protected static function logProcessOutput(Process $process, $name = null)
    {
        if ($name === null) {
            $name = self::$processLogCounter;
            self::$processLogCounter++;
        }
        $classParts = explode('\\', get_called_class());
        $class = end($classParts);

        $log = sprintf("%s/%s_%sOutput.log", self::getBasePath(), $class, $name);
        $log = str_replace('\\', '/', $log);

        file_put_contents(
            $log,
            sprintf("%s\n\n%s", $process->getCommandLine(), $process->getOutput())
        );
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

    protected static function printProcessMessageIfError(Process $process)
    {
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

    protected static function runInProjectRoot($command)
    {
        $process = new Process($command, self::getProjectRoot());
        $process->run();
        return $process;
    }
}
