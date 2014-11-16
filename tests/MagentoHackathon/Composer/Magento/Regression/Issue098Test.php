<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento\Regression;

use Cotya\ComposerTestFramework;

class Issue098Test extends ComposerTestFramework\PHPUnit\FullStackTestCase
{


    /**
     * @group regression
     */
    public function testDeployOfEarlierInstalledPackages()
    {
        $composer = new ComposerTestFramework\Composer\Wrapper();
        $projectDirectory = new \SplFileInfo(self::getTempComposerProjectPath());


        $artifactDirectory = new \SplFileInfo(__DIR__.'/../../../../../tests/FullStackTest/artifact');


        $composerJson = new  \SplTempFileObject();
        $composerJsonContent = <<<JSON
{
    "repositories": [
        {
            "type": "artifact",
            "url": "$artifactDirectory/"
        }
    ],
    "require": {
        "magento-hackathon/magento-composer-installer-test-sort1": "1.0.0"
    },
    "extra": {
        "magento-deploysttrategy": "copy",
        "magento-force": "override",
        "magento-root-dir": "./magento"
    }

}
JSON;

        $composerJson->fwrite($composerJsonContent);

        $composer->install($projectDirectory, $composerJson);

        $this->assertFileNotExists(
            $projectDirectory->getPathname().'/magento/app/design/frontend/test/default/installSort/test1.phtml'
        );

        $composerJson = new  \SplTempFileObject();
        $composerJsonContent = <<<JSON
{
    "repositories": [
        {
            "type": "artifact",
            "url": "$artifactDirectory/"
        }
    ],
    "require": {
        "magento-hackathon/magento-composer-installer-test-sort1": "1.0.0",
        "magento-hackathon/magento-composer-installer": "*"
    },
    "extra": {
        "magento-deploysttrategy": "copy",
        "magento-force": "override",
        "magento-root-dir": "./magento"
    }

}
JSON;

        $composerJson->fwrite($composerJsonContent);
        
        $composer->update($projectDirectory, $composerJson);
        
        
        $this->assertFileExists(
            $projectDirectory->getPathname().'/magento/app/design/frontend/test/default/installSort/test1.phtml'
        );
    }
}
