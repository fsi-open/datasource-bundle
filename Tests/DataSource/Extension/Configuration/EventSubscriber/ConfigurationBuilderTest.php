<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\DataSource\Extension\Configuration\EventSubscriber;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\EventSubscriber\ConfigurationBuilder;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs;
use FSi\Component\DataSource\Event\DataSourceEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

class ConfigurationBuilderTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $kernel;

    /**
     * @var MockObject
     */
    private $subscriber;

    public function testSubscribedEvents()
    {
        $this->assertEquals(
            ConfigurationBuilder::getSubscribedEvents(),
            [DataSourceEvents::PRE_BIND_PARAMETERS => ['readConfiguration', 1024]]
        );
    }

    public function testReadConfigurationFromOneBundle()
    {
        $this->kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnCallback(function(): array {
                $bundle = $this->createMock(BundleInterface::class, ['getPath']);
                $bundle->expects($this->any())
                    ->method('getPath')
                    ->will($this->returnValue(__DIR__ . '/../../../../Fixtures/FooBundle'));

                return [$bundle];
            }));

        $dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource->expects($this->any())->method('getName')->will($this->returnValue('news'));
        $dataSource->expects($this->once())->method('addField')->with('title', 'text', 'like', ['label' => 'Title']);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testReadConfigurationFromManyBundles()
    {
        $this->kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnCallback(function(): array {
                $fooBundle = $this->createMock(BundleInterface::class);
                $fooBundle->expects($this->any())
                    ->method('getPath')
                    ->will($this->returnValue(__DIR__ . '/../../../../Fixtures/FooBundle'));

                $barBundle = $this->createMock(BundleInterface::class);
                $barBundle->expects($this->any())
                    ->method('getPath')
                    ->will($this->returnValue(__DIR__ . '/../../../../Fixtures/BarBundle'))
                ;

                return [$fooBundle, $barBundle];
            }));

        $dataSource = $this->getMockBuilder(DataSourceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource->expects($this->any())->method('getName')->will($this->returnValue('news'));

        // 0 - 3 getName() is called
        $dataSource->expects($this->at(4))->method('addField')->with('title', 'text', 'like', ['label' => 'News Title']);
        $dataSource->expects($this->at(5))->method('addField')->with('author', null, null, []);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    protected function setUp()
    {
        $kernelMockBuilder = $this->getMockBuilder(Kernel::class)->setConstructorArgs(['dev', true]);
        if (version_compare(Kernel::VERSION, '2.7.0', '<')) {
            $kernelMockBuilder->setMethods(['registerContainerConfiguration', 'registerBundles', 'getBundles', 'init']);
        } else {
            $kernelMockBuilder->setMethods(['registerContainerConfiguration', 'registerBundles', 'getBundles']);
        }
        $this->kernel = $kernelMockBuilder->getMock();
        $this->subscriber = new ConfigurationBuilder($this->kernel);
    }
}
