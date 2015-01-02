<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;

/**
 * Class DeploystrategyFactoryTest
 * @package MagentoHackathon\Composer\Magento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class DeploystrategyFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $root;

    public function setUp()
    {
        $this->root = vfsStream::setup('root', null, array('vendor' => array(), 'htdocs' => array()));
    }

    /**
     * @dataProvider strategyProvider
     * @param string $strategy
     * @param string $expectedClass
     */
    public function testCorrectDeployStrategyIsReturned($strategy, $expectedClass)
    {
        $package = new Package("some/package", "1.0.0", "some/package");
        $config = new ProjectConfig(array(
            'magento-deploystrategy' => $strategy,
            'magento-root-dir' => vfsStream::url('root/htdocs'),
        ));
        $factory = new DeploystrategyFactory($config);
        $instance = $factory->make($package, sprintf('%s/some/package', vfsStream::url('root/vendor')));
        $this->assertInstanceOf($expectedClass, $instance);
    }

    /**
     * @return array
     */
    public function strategyProvider()
    {
        return array(
            array('copy',    '\MagentoHackathon\Composer\Magento\Deploystrategy\Copy'),
            array('symlink', '\MagentoHackathon\Composer\Magento\Deploystrategy\Symlink'),
            array('link',    '\MagentoHackathon\Composer\Magento\Deploystrategy\Link'),
            array('none',    '\MagentoHackathon\Composer\Magento\Deploystrategy\None'),
        );
    }

    public function testSymlinkStrategyIsUsedIfConfiguredStrategyNotFound()
    {
        $package = new Package("some/package", "1.0.0", "some/package");
        $config = new ProjectConfig(array(
            'magento-deploystrategy' => 'lolnotarealstrategy',
            'magento-root-dir' => vfsStream::url('root/htdocs'),
        ));
        $factory = new DeploystrategyFactory($config);

        $instance = $factory->make($package, sprintf('%s/some/package', vfsStream::url('root/vendor')));
        $this->assertInstanceOf('\MagentoHackathon\Composer\Magento\Deploystrategy\Symlink', $instance);
    }

    public function testIndividualOverrideTakesPrecedence()
    {
        $package = new Package("some/package", "1.0.0", "some/package");
        $config = new ProjectConfig(array(
            'magento-deploystrategy' => 'symlink',
            'magento-deploystrategy-overwrite' => array('some/package' => 'none'),
            'magento-root-dir' => vfsStream::url('root/htdocs'),
        ));

        $factory = new DeploystrategyFactory($config);

        $instance = $factory->make($package, sprintf('%s/some/package', vfsStream::url('root/vendor')));
        $this->assertInstanceOf('\MagentoHackathon\Composer\Magento\Deploystrategy\None', $instance);
    }
}

/**
 * Override realpath function so we can force it to work with vfsStream
 *
 * @param string $path
 * @return string
 */
function realpath($path)
{
    return $path;
}
