<?php
/**
 * Copyright (c) 2008-2015 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 26.08.2015
 *
 * Contributors:
 * Felix Glaser - initial contents
 *
 * @category    Dotsource
 */

namespace MagentoHackathon\Composer\FullStack\Regression;

use Composer\Util\Filesystem;
use Cotya\ComposerTestFramework;

class IssueC065Test extends ComposerTestFramework\PHPUnit\FullStackTestCase
{
    /** @var  ComposerTestFramework\Composer\Wrapper */
    protected $composer;

    /** @var  \SplFileInfo */
    protected $projectDirectory;

    /** @var  \SplFileInfo */
    protected $artifactDirectory;

    /** @var  string */
    protected $testFilePath;

    protected function setUp()
    {
        $this->composer = new ComposerTestFramework\Composer\Wrapper();
        $this->projectDirectory = new \SplFileInfo(self::getTempComposerProjectPath());
        $this->artifactDirectory = new \SplFileInfo(__DIR__.'/../../../../../tests/FullStackTest/artifact');
        $this->testFilePath = $this->projectDirectory->getPathname()."/mage/app/code/local/Test/Module";
    }

    private function prepareProject()
    {
        $fs = new Filesystem();
        $fs->remove($this->projectDirectory->getPathname()."/mage/app");
        $fs->ensureDirectoryExists($this->projectDirectory->getPathname()."/app/code/local/Test/Module");

        $modman = new \SplFileObject($this->projectDirectory->getPathname()."/modman", "w");
        $modmanContent = <<<MODMAN
app/code/local/*    app/code/local/
MODMAN;

        $modman->fwrite($modmanContent);
    }

    /**
     * @group regression
     */
    public function testIncludeRootPackageNotSet()
    {
        $this->prepareProject();

        $composerJson = new  \SplTempFileObject();
        $composerJsonContent = <<<JSON
{
    "name": "test/module",
    "type": "magento-module",
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.firegento.com"
        },
        {
            "type": "artifact",
            "url": "$this->artifactDirectory/"
        }
    ],
    "require": {
        "magento-hackathon/magento-composer-installer": "999.0.0"
    },
    "extra": {
        "magento-deploy-strategy": "symlink",
        "magento-root-dir": "./mage"
    }

}
JSON;

        $composerJson->fwrite($composerJsonContent);

        $this->composer->install($this->projectDirectory, $composerJson);

        $this->assertFileNotExists($this->testFilePath);
    }

    /**
     * @group regression
     */
    public function testIncludeRootPackageIsFalse()
    {
        $this->prepareProject();


        $composerJson = new  \SplTempFileObject();
        $composerJsonContent = <<<JSON
{
    "name": "test/module",
    "type": "magento-module",
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.firegento.com"
        },
        {
            "type": "artifact",
            "url": "$this->artifactDirectory/"
        }
    ],
    "require": {
        "magento-hackathon/magento-composer-installer": "999.0.0"
    },
    "extra": {
        "magento-deploy-strategy": "symlink",
        "magento-root-dir": "./mage",
        "include-root-package": false
    }

}
JSON;

        $composerJson->fwrite($composerJsonContent);

        $this->composer->install($this->projectDirectory, $composerJson);

        $this->assertFileNotExists($this->testFilePath);
    }

    /**
     * @group regression
     */
    public function testIncludeRootPackageIsTrue()
    {
        $this->prepareProject();


        $composerJson = new  \SplTempFileObject();
        $composerJsonContent = <<<JSON
{
    "name": "test/module",
    "type": "magento-module",
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.firegento.com"
        },
        {
            "type": "artifact",
            "url": "$this->artifactDirectory/"
        }
    ],
    "require": {
        "magento-hackathon/magento-composer-installer": "999.0.0"
    },
    "extra": {
        "magento-deploy-strategy": "symlink",
        "magento-root-dir": "./mage",
        "include-root-package": true
    }

}
JSON;

        $composerJson->fwrite($composerJsonContent);

        $this->composer->install($this->projectDirectory, $composerJson);

        $this->assertFileExists($this->testFilePath);
    }
}
