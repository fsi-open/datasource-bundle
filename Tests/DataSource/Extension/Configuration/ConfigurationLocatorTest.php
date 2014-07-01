<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\DataSource\Extension\Configuration;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLoader;
use FSi\Bundle\DataSourceBundle\Tests\Double\StubBundle;
use FSi\Bundle\DataSourceBundle\Tests\Double\StubKernel;
use FSi\Component\DataSource\DataSourceEvent;
use FSi\Component\DataSource\DataSourceEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class ConfigurationLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var \FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLocator
     */
    protected $configurationLocator;

    public function setUp()
    {
        $this->kernel = new StubKernel(__DIR__ . '/../../../Fixtures');
        $this->kernel->injectBundle(new StubBundle('FooBundle', $this->kernel->getRootDir()));
        $this->kernel->injectBundle(new StubBundle('BarBundle', $this->kernel->getRootDir()));
        $this->configurationLocator = $this->getMock(
            'FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLocator',
            array('__construct'),
            array($this->kernel)
        );
    }

    public function testLocateGlobalResource()
    {
        $configPath = '/app/config/datasource/news.yml';
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\Bundle');
        $bundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue(__DIR__ . '/../../../Fixtures/FooBundle'));

        $resourcePath = $this->configurationLocator->locateConfig($configPath, $bundle);
        $globalPath = $this->kernel->getRootDir() . '/app/config/datasource/news.yml';

        $this->assertEquals($globalPath, $resourcePath);
    }

    public function testLocateBundleResource()
    {
        $configPath = 'BarBundle:news.yml';

        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\Bundle');
        $bundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue(__DIR__ . '/../../../Fixtures/BarBundle'));


        $resourcePath = $this->configurationLocator->locateConfig($configPath, $bundle);
        $expectedPath = sprintf('%s/Resources/config/datasource/%s', $bundle->getPath(), 'news.yml');

        $this->assertEquals($expectedPath, $resourcePath);
    }

    public function testLocateInlineResource()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\Bundle');
        $bundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue(__DIR__ . '/../../../Fixtures/FooBundle'));

        $resourcePath = $this->configurationLocator->locateConfig('news.yml', $bundle);
        $expectedPath = sprintf('%s/Resources/config/datasource/%s', $bundle->getPath(), 'news.yml');

        $this->assertEquals($expectedPath, $resourcePath);
    }
}
