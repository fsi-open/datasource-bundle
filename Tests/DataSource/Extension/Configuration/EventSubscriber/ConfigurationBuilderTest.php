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
     * @var Kernel&MockObject
     */
    private $kernel;

    /**
     * @var ConfigurationBuilder
     */
    private $subscriber;

    public function testSubscribedEvents(): void
    {
        self::assertEquals(
            ConfigurationBuilder::getSubscribedEvents(),
            [DataSourceEvents::PRE_BIND_PARAMETERS => ['readConfiguration', 1024]]
        );
    }

    public function testReadConfigurationFromOneBundle(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn(null)
        ;
        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $bundle = $this->createMock(BundleInterface::class);
                    $bundle->method('getPath')->willReturn(__DIR__ . '/../../../../Fixtures/FooBundle');

                    return [$bundle];
                }
            );

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('news');
        $dataSource->expects(self::once())->method('addField')->with('title', 'text', 'like', ['label' => 'Title']);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testReadConfigurationFromManyBundles(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn(null)
        ;

        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $fooBundle = $this->createMock(BundleInterface::class);
                    $fooBundle->method('getPath')->willReturn(__DIR__ . '/../../../../Fixtures/FooBundle');

                    $barBundle = $this->createMock(BundleInterface::class);
                    $barBundle->method('getPath')->willReturn(__DIR__ . '/../../../../Fixtures/BarBundle');

                    return [$fooBundle, $barBundle];
                }
            );

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('news');

        // 0 - 1 getName() is called
        $dataSource->expects(self::at(2))->method('addField')->with('title', 'text', 'like', ['label' => 'News Title']);
        $dataSource->expects(self::at(3))->method('addField')->with('author', null, null, []);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testMainConfigurationOverridesBundles(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn(sprintf('%s/../../../../Resources/config/main_directory', __DIR__))
        ;

        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);
        $this->kernel->expects(self::never())->method('getBundles');

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('news');

        // 0  is when getName() is called
        $dataSource->expects(self::at(1))
            ->method('addField')
            ->with('title_short', 'text', null, ['label' => 'Short title'])
        ;

        $dataSource->expects(self::at(2))
            ->method('addField')
            ->with('created_at', 'date', null, ['label' => 'Created at'])
        ;

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testBundleConfigUsedWhenNoFileFoundInMainDirectory(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn(sprintf('%s/../../../../Resources/config/main_directory', __DIR__))
        ;

        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);
        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturnCallback(
                function (): array {
                    $bundle = $this->createMock(BundleInterface::class);
                    $bundle->method('getPath')->willReturn(__DIR__ . '/../../../../Fixtures/FooBundle');

                    return [$bundle];
                }
            );

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('user');
        $dataSource->expects(self::once())->method('addField')->with('username', 'text', null, []);

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    public function testExceptionThrownWhenMainConfigPathIsNotADirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"non existent directory" is not a directory!');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('getParameter')
            ->with('datasource.yaml.main_config')
            ->willReturn('non existent directory')
        ;

        $this->kernel->expects(self::once())->method('getContainer')->willReturn($container);

        $dataSource = $this->createMock(DataSourceInterface::class);
        $dataSource->method('getName')->willReturn('news');

        $this->subscriber->readConfiguration(new ParametersEventArgs($dataSource, []));
    }

    protected function setUp(): void
    {
        $kernelMockBuilder = $this->getMockBuilder(Kernel::class)->setConstructorArgs(['dev', true]);
        $kernelMockBuilder->setMethods(
            ['registerContainerConfiguration', 'registerBundles', 'getBundles', 'getContainer']
        );

        $this->kernel = $kernelMockBuilder->getMock();
        $this->subscriber = new ConfigurationBuilder($this->kernel);
    }
}
