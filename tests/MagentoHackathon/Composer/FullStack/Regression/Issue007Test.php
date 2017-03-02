<?php

namespace MagentoHackathon\Composer\Magento\Regression;

use Cotya\ComposerTestFramework;

class Issue007Test extends ComposerTestFramework\PHPUnit\FullStackTestCase
{

    /**
     * @group regression
     */
    public function testInstallWhenPackageDependsOnAlreadyRequiredPackage()
    {
        $composer           = new ComposerTestFramework\Composer\Wrapper();
        $projectDirectory   = new \SplFileInfo(self::getTempComposerProjectPath());
        $artifactDirectory  = new \SplFileInfo(__DIR__.'/../../../../../tests/FullStackTest/artifact');
        $composerJson       = new  \SplTempFileObject();
        $composerJsonContent = <<<JSON
{
    "repositories": [
        {
            "type": "artifact",
            "url": "$artifactDirectory/"
        }
    ],
    "require": {
        "magento-hackathon/magento-composer-installer": "*",
        "magento-hackathon/magento-composer-installer-test-issue-07-a": "1.0.0",
        "magento-hackathon/magento-composer-installer-test-issue-07-b": "1.0.0",
        "magento-hackathon/magento-composer-installer-test-issue-07-c": "1.0.0"
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

        $this->assertFileExists($projectDirectory->getPathname() . '/magento/a');
        $this->assertFileExists($projectDirectory->getPathname() . '/magento/b');
        $this->assertFileExists($projectDirectory->getPathname() . '/magento/c');
        $this->assertFileExists($projectDirectory->getPathname() . '/vendor/installed.json');


        $expectedStateContent = <<<EOF
[
    {
        "packageName": "magento-hackathon\/magento-composer-installer-test-issue-07-a",
        "version": "1.0.0.0",
        "installedFiles": [
            "\/a"
        ]
    },
    {
        "packageName": "magento-hackathon\/magento-composer-installer-test-issue-07-b",
        "version": "1.0.0.0",
        "installedFiles": [
            "\/b"
        ]
    },
    {
        "packageName": "magento-hackathon\/magento-composer-installer-test-issue-07-c",
        "version": "1.0.0.0",
        "installedFiles": [
            "\/c"
        ]
    }
]
EOF;

        $this->assertEquals(
            $expectedStateContent,
            file_get_contents($projectDirectory->getPathname() . '/vendor/installed.json')
        );
    }
}
