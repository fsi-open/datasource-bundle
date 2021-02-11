<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\Twig\Extension;

use FSi\Bundle\DataSourceBundle\Tests\Fixtures\StubTranslator;
use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceExtension;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Template;

/**
 * @author Stanislav Prokopov <stanislav.prokopov@gmail.com>
 */
class DataSourceExtensionTest extends TestCase
{
    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var DataSourceExtension
     */
    protected $extension;

    public function testInitRuntimeShouldThrowExceptionBecauseNotExistingTheme(): void
    {
        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage('Unable to find template "this_is_not_valid_path.html.twig"');

        $this->twig->addExtension(new DataSourceExtension($this->getContainer(), 'this_is_not_valid_path.html.twig'));
        // force initRuntime()
        $this->twig->loadTemplate('datasource.html.twig');
    }

    public function testInitRuntimeWithValidPathToTheme(): void
    {
        $this->twig->addExtension($this->extension);
        // force initRuntime()
        self::assertInstanceOf(Template::class, $this->twig->loadTemplate('datasource.html.twig'));
    }

    public function testDataSourceFilterCount(): void
    {
        $this->twig->addExtension($this->extension);
        // force initRuntime()
        $this->twig->loadTemplate('datasource.html.twig');

        $datasourceView = $this->getDataSourceView('datasource');
        $fieldView1 = $this->createMock(FieldViewInterface::class);
        $fieldView1->expects(self::atLeastOnce())->method('hasAttribute')->with('form')->willReturn(true);

        $fieldView2 = $this->createMock(FieldViewInterface::class);
        $fieldView2->expects(self::atLeastOnce())->method('hasAttribute')->with('form')->willReturn(false);

        $fieldView3 = $this->createMock(FieldViewInterface::class);
        $fieldView3->expects(self::atLeastOnce())->method('hasAttribute')->with('form')->willReturn(true);

        $datasourceView->expects(self::atLeastOnce())
            ->method('getFields')
            ->willReturn([$fieldView1, $fieldView2, $fieldView3])
        ;

        self::assertEquals(2, $this->extension->datasourceFilterCount($datasourceView));
    }

    public function testDataSourceRenderBlock(): void
    {
        $this->twig->addExtension($this->extension);
        // force initRuntime()
        $this->twig->loadTemplate('datasource.html.twig');
        $template = $this->getTemplateMock();
        $template->expects(self::at(0))->method('hasBlock')
            ->with('datasource_datasource_filter')
            ->willReturn(false)
        ;
        $template->expects(self::at(1))->method('getParent')->with([])->willReturn(false);
        $template->expects(self::at(2))->method('hasBlock')->with('datasource_filter')->willReturn(true);

        $datasourceView = $this->getDataSourceView('datasource');
        $this->extension->setTheme($datasourceView, $template);

        $template->expects(self::at(3))
            ->method('displayBlock')
            ->with('datasource_filter', [
                'datasource' => $datasourceView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true)
        ;

        $this->extension->datasourceFilter($datasourceView);
    }

    public function testDataSourceRenderBlockFromParent(): void
    {
        $this->twig->addExtension($this->extension);
        // force initRuntime()
        $this->twig->loadTemplate('datasource.html.twig');

        $parent = $this->getTemplateMock();
        $template = $this->getTemplateMock();
        $template->expects(self::at(0))
            ->method('hasBlock')
            ->with('datasource_datasource_filter')
            ->willReturn(false)
        ;

        $template->expects(self::at(1))->method('getParent')->with([])->willReturn(false);
        $template->expects(self::at(2))->method('hasBlock')->with('datasource_filter')->willReturn(false);
        $template->expects(self::at(3))->method('getParent')->with([])->willReturn($parent);
        $parent->expects(self::at(0))->method('hasBlock')->with('datasource_filter')->willReturn(true);

        $datasourceView = $this->getDataSourceView('datasource');
        $this->extension->setTheme($datasourceView, $template);

        $parent->expects(self::at(1))
            ->method('displayBlock')
            ->with('datasource_filter', [
                'datasource' => $datasourceView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);

        $this->extension->datasourceFilter($datasourceView);
    }

    protected function setUp(): void
    {
        $loader = new FilesystemLoader([
            __DIR__ . '/../../../vendor/symfony/twig-bridge/Resources/views/Form',
            __DIR__ . '/../../../Resources/views', // datasource base theme
        ]);

        $twig = new Environment($loader);
        $twig->addExtension(new TranslationExtension(new StubTranslator()));
        $twig->addExtension(new FormExtension());
        $twig->addGlobal('global_var', 'global_value');

        $this->twig = $twig;
        $this->extension = new DataSourceExtension($this->getContainer(), 'datasource.html.twig');
    }

    private function getRouter(): MockObject
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('some_route');

        return $router;
    }

    /**
     * @return ContainerInterface&MockObject
     */
    private function getContainer(): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('router')->willReturn($this->getRouter());

        return $container;
    }

    /**
     * @param string $name
     * @return DataSourceViewInterface&MockObject
     */
    private function getDataSourceView(string $name): DataSourceViewInterface
    {
        $datasourceView = $this->createMock(DataSourceViewInterface::class);
        $datasourceView->method('getName')->willReturn($name);

        return $datasourceView;
    }

    /**
     * @return Template&MockObject
     */
    private function getTemplateMock(): Template
    {
        return $this->createMock(Template::class);
    }
}
