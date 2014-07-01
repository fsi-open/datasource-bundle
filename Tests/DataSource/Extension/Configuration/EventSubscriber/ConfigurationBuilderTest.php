<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\DataSource\Extension\Configuration\EventSubscriber;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLoader;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLocator;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\EventSubscriber\ConfigurationBuilder;
use FSi\Bundle\DataSourceBundle\Tests\Double\StubBundle;
use FSi\Bundle\DataSourceBundle\Tests\Double\StubKernel;
use FSi\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs;
use FSi\Component\DataSource\Event\DataSourceEvents;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpKernel\KernelInterface;

class ConfigurationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var ConfigurationBuilder
     */
    protected $subscriber;

    /**
     * @var \FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLoader
     */
    protected $configurationLoader;

    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $stream;

    private function initConfigurationBuilder()
    {
        $configurationLocator = new ConfigurationLocator($this->kernel);
        $this->configurationLoader = new ConfigurationLoader($this->kernel, $configurationLocator);
        $this->subscriber = new ConfigurationBuilder($this->kernel, $this->configurationLoader);
    }

    public function setUp()
    {
        $this->stream = vfsStream::setup("Fixtures");
        $this->kernel = new StubKernel($this->stream->url());

        $this->initConfigurationBuilder();
    }

    public function testSubscribedEvents()
    {
        $this->assertEquals(
            $this->subscriber->getSubscribedEvents(),
            array(DataSourceEvents::PRE_BIND_PARAMETERS => array('readConfiguration', 1024))
        );
    }

    public function testReadConfigurationWithImportSection()
    {
        $this->kernel->removeBundles();
        $this->kernel->injectBundle(new StubBundle('FooBundle', $this->stream->url()));
        $this->kernel->injectBundle(new StubBundle('BarBundle', $this->stream->url()));
        $this->initConfigurationBuilder();

        $fooBundleDatasourceConfig = <<<YML
fields:
  title:
    type: text
    comparison: like
    options:
      label: News Title

imports:
  - { resource: "BarBundle:news.yml" }
YML;
        $barBundleDatasourceConfig = <<<YML
fields:
  title:
    type: text
    comparison: eq
    options:
      label: Title
  quantity:
    type: number
    comparison: eq
    option:
      label: Quantity
YML;

        $this->createConfigFile('FooBundle/Resources/config/datasource/news.yml', $fooBundleDatasourceConfig);
        $this->createConfigFile('BarBundle/Resources/config/datasource/news.yml', $barBundleDatasourceConfig);

//        /** @var \FSi\Component\DataSource\DataSource $dataSource */
        $dataSource = $this->getMockBuilder('FSi\Component\DataSource\DataSource')
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('news'));

        $dataSource->expects($this->at(4))
            ->method('addField')
            ->with('title', 'text', 'eq', array('label' => 'Title'));
//
//        $dataSource->expects($this->at(3))
//            ->method('addField')
//            ->with('quantity', 'number', 'eq', array('label' => 'Quantity'));


        $event = new ParametersEventArgs($dataSource, array());

        $this->subscriber->readConfiguration($event);


    }

    public function testReadConfigurationFromOneBundle()
    {
        $self = $this;
        $this->kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnCallback(function() use ($self) {
                $bundle = $self->getMock('Symfony\Component\HttpKernel\Bundle\Bundle');
                $bundle->expects($self->any())
                    ->method('getPath')
                    ->will($self->returnValue(__DIR__ . '/../../../../Fixtures/FooBundle'));

                return array($bundle);
            }));

        $dataSource = $this->getMockBuilder('FSi\Component\DataSource\DataSource')
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('news'));

        $dataSource->expects($this->once())
            ->method('addField')
            ->with('title', 'text', 'like', array('label' => 'Title'));

        $event = new ParametersEventArgs($dataSource, array());

        $this->subscriber->readConfiguration($event);
    }

    public function testReadConfigurationFromManyBundles()
    {
        $self = $this;
        $this->kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnCallback(function() use ($self) {
                $fooBundle = $self->getMock('Symfony\Component\HttpKernel\Bundle\Bundle');
                $fooBundle->expects($self->any())
                    ->method('getPath')
                    ->will($self->returnValue(__DIR__ . '/../../../../Fixtures/FooBundle'));

                $barBundle = $self->getMock('Symfony\Component\HttpKernel\Bundle\Bundle');
                $barBundle->expects($self->any())
                    ->method('getPath')
                    ->will($self->returnValue(__DIR__ . '/../../../../Fixtures/BarBundle'));
                return array(
                    $fooBundle,
                    $barBundle
                );
            }));

        $dataSource = $this->getMockBuilder('FSi\Component\DataSource\DataSource')
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('news'));

        // 0 - 3 getName() is called
        $dataSource->expects($this->at(4))
            ->method('addField')
            ->with('title', 'text', 'like', array('label' => 'News Title'));

        $dataSource->expects($this->at(5))
            ->method('addField')
            ->with('author', null, null, array());


        $event = new ParametersEventArgs($dataSource, array());

        $this->subscriber->readConfiguration($event);
    }

    private function createConfigFile($fileName, $content)
    {
        $path = sprintf("%s/%s", $this->kernel->getRootDir(), $fileName);

        $dirName = dirname($path);

        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }

        file_put_contents($path, $content);

        return $path;
    }
}