<?php

namespace MagentoHackathon\Composer\Magento\FullStack;

use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

class GlobalPluginTest extends AbstractTest
{

    protected static $processLogCounter = 1;

    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    protected function prepareCleanDirectories()
    {
        $fs = new Filesystem();
        $fs->removeDirectory(self::getBasePath().'/home/vendor');
        $fs->removeDirectory(self::getBasePath().'/home/cache');
        $fs->remove(self::getBasePath().'/home/composer.lock');
    }

    public function testGlobalInstall()
    {
        $this->markTestSkipped('This has not been implemented yet.');
        $process = new Process(
            self::getComposerCommand().' global install ' . self::getComposerArgs(),
            self::getProjectRoot()
        );
        $process->setEnv(array('COMPOSER_HOME'=>self::getBasePath().'/home'));

        $process->run();
        $this->assertProcess($process);
    }

    public function testGlobalUpdate()
    {
        $this->markTestSkipped('This has not been implemented yet.');
        $process = new Process(
            self::getComposerCommand().' global update ' . self::getComposerArgs(),
            self::getProjectRoot()
        );
        $process->setEnv(array('COMPOSER_HOME'=>self::getBasePath().'/home'));

        $process->run();
        $this->assertProcess($process);
    }
}
