<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\DataSource\Extension\Configuration;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLoader;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLocator;
use FSi\Bundle\DataSourceBundle\Tests\Double\StubBundle;
use FSi\Bundle\DataSourceBundle\Tests\Double\StubKernel;
use Symfony\Component\HttpKernel\KernelInterface;

class ConfigurationLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var \FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLocator
     */
    protected $configurationLocator;

    /**
     * @var \FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLoader
     *
     */
    protected $configurationLoader;

    public function setUp()
    {
        $this->kernel = new StubKernel(__DIR__.'/../../../Fixtures');
        $this->kernel->injectBundle(new StubBundle('FooBundle', $this->kernel->getRootDir()));
        $this->configurationLocator = new ConfigurationLocator($this->kernel);
        $this->configurationLoader = new ConfigurationLoader($this->kernel, $this->configurationLocator);
    }

    public function testImportConfig()
    {

        $configs = array(
            'fields' => array(),
            'imports' => array(
                array ('resource' => 'news.yml')
            )
        );

        $configLoaded = $this->configurationLoader->load($configs, $this->kernel->getBundle('FooBundle'));

        $expected = array(
            'fields' => array(
                'title' => array(
                    'type' => 'text',
                    'comparison' => 'like',
                    'options' => array('label' => 'Title')
                )
            )
        );

        $this->assertEquals($expected, $configLoaded);
    }
}
