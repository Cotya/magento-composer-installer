<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento\Regression;

use Cotya\ComposerTestFramework;

class Issue139Test extends ComposerTestFramework\PHPUnit\FullStackTestCase
{

    /**
     * @group regression
     */
    public function testCreateProject()
    {
        $composer = new ComposerTestFramework\Composer\Wrapper();
        $projectDirectory = new \SplFileInfo(self::getTempComposerProjectPath());


        $artifactDirectory = new \SplFileInfo(__DIR__.'/../../../../../tests/FullStackTest/artifact');


        $composerJson = new  \SplTempFileObject();
        $json = [
            'repositories' => [
                [
                    'type' => 'artifact',
                    'url' => $artifactDirectory->getRealPath(),
                ],
                [
                    'type' => 'composer',
                    'url' => 'http://packages.firegento.com'
                ],
            ],
            'require' => [
                'connect20/mage_all_latest' => '*',
                'magento-hackathon/magento-composer-installer' => '*',
                'composer/composer' => '*@dev'
            ],
            'extra' => [
                'magento-deploysttrategy' => 'copy',
                'magento-force' => 'override',
                'magento-root-dir' => './web'
            ]
        ];

        $composerJson->fwrite(json_encode($json, JSON_PRETTY_PRINT));

        $composer->install($projectDirectory, $composerJson);

        $this->assertFileExists($projectDirectory->getPathname().'/web/lib/Varien/Exception.php');
    }
}
