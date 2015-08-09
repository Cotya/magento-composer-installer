<?php

namespace MagentoHackathon\Composer\Magento\Regression;

use Cotya\ComposerTestFramework;

class IssueC039Test extends ComposerTestFramework\PHPUnit\FullStackTestCase
{


    /**
     * @group regression
     */
    public function testAddAndRemoveSymlinkedModule()
    {
        $composer = new ComposerTestFramework\Composer\Wrapper();
        $projectDirectory = new \SplFileInfo(self::getTempComposerProjectPath());


        $artifactDirectory = new \SplFileInfo(__DIR__.'/../../../../../tests/FullStackTest/artifact');

        $testFilePath = $projectDirectory->getPathname().
            '/build/app/design/frontend/test/default/updateFileRemove/design/test1.phtml';

        $composerJson = new  \SplTempFileObject();
        $composerJsonContent = <<<JSON
{
    "repositories": [
        {
            "type": "composer",
            "url": "http://packages.firegento.com"
        },
        {
            "type": "artifact",
            "url": "$artifactDirectory/"
        }
    ],
    "require": {
        "magento-hackathon/magento-composer-installer": "999.0.0",
        "magento-hackathon/magento-composer-installer-test-updateFileRemove": "1.0.0"
    },
    "extra": {
        "magento-deploysttrategy": "symlink",
        "magento-root-dir": "./build"
    }

}
JSON;

        $composerJson->fwrite($composerJsonContent);

        $composer->install($projectDirectory, $composerJson);

        $this->assertFileExists($testFilePath);



        $composerJson = new  \SplTempFileObject();
        $composerJsonContent = <<<JSON
{
    "repositories": [
        {
            "type": "artifact",
            "url": "$artifactDirectory/"
        },
        {
            "type": "composer",
            "url": "http://packages.firegento.com"
        }
    ],
    "require": {
        "magento-hackathon/magento-composer-installer": "*"
    },
    "extra": {
        "magento-deploysttrategy": "symlink",
        "magento-root-dir": "./build"
    }

}
JSON;

        $composerJson->fwrite($composerJsonContent);

        $composer->update($projectDirectory, $composerJson);

        $this->assertFileNotExists($testFilePath);
        $this->assertFalse(is_link($testFilePath), 'There is still a link');
        
    }
}
