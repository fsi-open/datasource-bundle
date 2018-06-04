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
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn(null)
        ;
        $this->kernel->expects($this->once())->method('getContainer')->willReturn($container);
        $this->kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnCallback(function(): array {
                $bundle = $this->createMock(BundleInterface::class, ['getPath']);
                $bundle->expects($this->any())
                    ->method('getPath')
                    ->will($this->returnValue(__DIR__ . '/../../../../Fixtures/FooBundle'));

                return [$bundle];
            }));

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->expects($this->any())->method('getName')->will($this->returnValue('news'));
        $dataSource->expects($this->once())->method('addField')->with('title', 'text', 'like', ['label' => 'Title']);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testReadConfigurationFromManyBundles()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn(null)
        ;

        $this->kernel->expects($this->once())->method('getContainer')->willReturn($container);
        $this->kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnCallback(function(): array {
                $fooBundle = $this->createMock(BundleInterface::class);
                $fooBundle->expects($this->any())
                    ->method('getPath')
                    ->willReturn(__DIR__ . '/../../../../Fixtures/FooBundle');

                $barBundle = $this->createMock(BundleInterface::class);
                $barBundle->expects($this->any())
                    ->method('getPath')
                    ->willReturn(__DIR__ . '/../../../../Fixtures/BarBundle')
                ;

                return [$fooBundle, $barBundle];
            }));

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->expects($this->any())->method('getName')->will($this->returnValue('news'));

        // 0 - 1 getName() is called
        $dataSource->expects($this->at(2))->method('addField')->with('title', 'text', 'like', ['label' => 'News Title']);
        $dataSource->expects($this->at(3))->method('addField')->with('author', null, null, []);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testMainConfigurationOverridesBundles()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn(sprintf('%s/../../../../Resources/config/main_directory', __DIR__))
        ;

        $this->kernel->expects($this->once())->method('getContainer')->willReturn($container);
        $this->kernel->expects($this->never())->method('getBundles');

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->expects($this->any())->method('getName')->will($this->returnValue('news'));

        // 0  is when getName() is called
        $dataSource->expects($this->at(1))->method('addField')->with('title_short', 'text', null, ['label' => 'Short title']);
        $dataSource->expects($this->at(2))->method('addField')->with('created_at', 'date', null, ['label' => 'Created at']);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testBundleConfigUsedWhenNoFileFoundInMainDirectory()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn(sprintf('%s/../../../../Resources/config/main_directory', __DIR__))
        ;

        $this->kernel->expects($this->once())->method('getContainer')->willReturn($container);
        $this->kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnCallback(function() {
                $bundle = $this->createMock(BundleInterface::class);
                $bundle->expects($this->any())
                    ->method('getPath')
                    ->willReturn(__DIR__ . '/../../../../Fixtures/FooBundle');

                return [$bundle];
            }));

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->expects($this->any())->method('getName')->will($this->returnValue('user'));
        $dataSource->expects($this->once())->method('addField')->with('username', 'text', null, []);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testExceptionThrownWhenMainConfigPathIsNotADirectory()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"non existant directory" is not a directory!');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn('non existant directory')
        ;

        $this->kernel->expects($this->once())->method('getContainer')->willReturn($container);

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->expects($this->any())->method('getName')->will($this->returnValue('news'));

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    protected function setUp()
    {
        $kernelMockBuilder = $this->getMockBuilder(Kernel::class)->setConstructorArgs(['dev', true]);
        if (version_compare(Kernel::VERSION, '2.7.0', '<')) {
            $kernelMockBuilder->setMethods(
                ['registerContainerConfiguration', 'registerBundles', 'getBundles', 'getContainer', 'init']
            );
        } else {
            $kernelMockBuilder->setMethods(
                ['registerContainerConfiguration', 'registerBundles', 'getBundles', 'getContainer']
            );
        }
        $this->kernel = $kernelMockBuilder->getMock();
        $this->subscriber = new ConfigurationBuilder($this->kernel);
    }
}
