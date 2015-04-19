<?php

namespace MagentoHackathon\Composer\Magento\FullStack;

use Symfony\Component\Process\Process;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    private static $composerCommandPath;

    protected static $processLogCounter = 1;

    public static function setUpBeforeClass()
    {
        $command = 'perl -pi.bak -e \'s/"test_version"/"version"/g\' ./composer.json';
        $process = self::runInProjectRoot($command);
        self::printProcessMessageIfError($process);

        @unlink(self::getProjectRoot().'/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getBasePath().'/magento/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getBasePath().'/magento-modules/vendor/theseer/directoryscanner/tests/_data/linkdir');
        @unlink(self::getProjectRoot().'/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        @unlink(self::getBasePath().'/magento/vendor/theseer/directoryscanner/tests/_data/nested/empty');
        @unlink(self::getBasePath().'/magento-modules/vendor/theseer/directoryscanner/tests/_data/nested/empty');

        $command = self::getComposerCommand().' archive --format=zip --dir="tests/FullStackTest/artifact" -vvv';
        $process = self::runInProjectRoot($command);

        if ($process->getExitCode() !== 0) {
            self::printProcessMessageIfError($process);
        } else {
            self::logProcessOutput($process, 'createComposerArtifact');
        }
    }

    public static function tearDownAfterClass()
    {
        $process = self::runInProjectRoot('perl -pi.bak -e \'s/"version"/"test_version"/g\' ./composer.json');
        self::printProcessMessageIfError($process);
    }

    protected static function getBasePath()
    {
        return realpath(__DIR__.'/../../../../FullStackTest');
    }

    protected static function getProjectRoot()
    {
        return realpath(__DIR__.'/../../../../..');
    }

    private static function resolveComposerCommand()
    {
        if (getenv('TRAVIS') == "true") {
            $command = self::getProjectRoot().'/composer.phar';
        } elseif (self::runInProjectRoot('composer.phar --version')->getExitCode() === 0) {
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
        file_put_contents(
            sprintf("%s/%s_%sOutput.log", self::getBasePath(), get_called_class(), $name),
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
