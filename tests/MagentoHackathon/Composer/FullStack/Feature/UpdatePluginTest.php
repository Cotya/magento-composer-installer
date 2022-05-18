<?php

namespace MagentoHackathon\Composer\Magento\Regression;

use Cotya\ComposerTestFramework;

class UpdatePluginTest extends ComposerTestFramework\PHPUnit\FullStackTestCase
{
    /**
     * @group regression
     */
    public function testDeployOfEarlierInstalledPackages()
    {
        $composer = new ComposerTestFramework\Composer\Wrapper();
        $projectDirectory = new \SplFileInfo(self::getTempComposerProjectPath());


        $path = str_replace('\\', '/', __DIR__);
        $artifactDirectory = new \SplFileInfo($path .'/../../../../../tests/FullStackTest/artifact');


        $composerJson = new  \SplTempFileObject();

        $dir = $artifactDirectory->getRealPath();

        $json = [
            'repositories' => [
                [
                    'type' => 'artifact',
                    'url' => $artifactDirectory->getRealPath(),
                ]
            ],
            'require' => [
                'magento-hackathon/magento-composer-installer' => '999.0.0'
            ],
            'extra' => [
                'magento-deploysttrategy' => 'copy',
                'magento-force' => 'override',
                'magento-root-dir' => './magento'
            ]
        ];

        $composerJson->fwrite(json_encode($json, JSON_PRETTY_PRINT));

        $composer->install($projectDirectory, $composerJson);


        $composerJson = new  \SplTempFileObject();

        $json = [
            'repositories' => [
                [
                    'type' => 'artifact',
                    'url' => $artifactDirectory->getRealPath(),
                ]
            ],
            'require' => [
                'magento-hackathon/magento-composer-installer' => '997.0.0'
            ],
            'extra' => [
                'magento-deploysttrategy' => 'copy',
                'magento-force' => 'override',
                'magento-root-dir' => './magento'
            ]
        ];

        $composerJson->fwrite(json_encode($json, JSON_PRETTY_PRINT));

        try {
            $composer->update($projectDirectory, $composerJson);
            $this->fail('This point should not be reached but an exception should have been thrown');
        } catch (\Exception $e) {
            $this->assertContains('Dont update the', $e->getMessage());
        }
    }
}
