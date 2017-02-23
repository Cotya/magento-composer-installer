<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento\Regression;

use Cotya\ComposerTestFramework;
use Symfony\Component\Process\Process;

class IssueC118Test extends ComposerTestFramework\PHPUnit\FullStackTestCase
{


    /**
     * @group regression
     */
    public function testInstallAndUpdateForDeprecatedMessagePackages()
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
                ],
                [
                    'type' => 'composer',
                    'url' => 'https://packages.firegento.com'
                ],
            ],
            'require' => [
                'connect20/firegento_pdf' => '1.3.0',
                'magento-hackathon/magento-composer-installer' => '*'
            ],
            'extra' => [
                'magento-deploysttrategy' => 'copy',
                'magento-force' => 'override',
                'magento-root-dir' => './magento'
            ]
        ];

        $composerJson->fwrite(json_encode($json, JSON_PRETTY_PRINT));

        $composer->install($projectDirectory, $composerJson);
        $this->assertNoDeprecatedNotice($composer->getLastRunProcessObject());

        $composerJson = new  \SplTempFileObject();

        $json = [
            'repositories' => [
                [
                    'type' => 'artifact',
                    'url' => $artifactDirectory->getRealPath(),
                ],
                [
                    'type' => 'composer',
                    'url' => 'https://packages.firegento.com'
                ],
            ],
            'require' => [
                'connect20/firegento_pdf' => '1.2.0',
                'magento-hackathon/magento-composer-installer' => '*'
            ],
            'extra' => [
                'magento-deploysttrategy' => 'copy',
                'magento-force' => 'override',
                'magento-root-dir' => './magento'
            ]
        ];

        $composerJson->fwrite(json_encode($json, JSON_PRETTY_PRINT));

        $composer->update($projectDirectory, $composerJson);
        $this->assertNoDeprecatedNotice($composer->getLastRunProcessObject());
    }
    
    private function assertNoDeprecatedNotice(Process $process)
    {
        $this->assertNotContains(
            'Deprecation Notice',
            $process->getErrorOutput(),
            'The last command contained a Deprecated Notice'
        );
    }
}
