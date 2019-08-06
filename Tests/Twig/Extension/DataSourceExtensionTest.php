<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\Twig\Extension;

use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceExtension;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Tests\Extension\Fixtures\StubTranslator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
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

    public function testInitRuntimeShouldThrowExceptionBecauseNotExistingTheme()
    {
        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage('Unable to find template "this_is_not_valid_path.html.twig"');

        $this->twig->addExtension(new DataSourceExtension($this->getContainer(), 'this_is_not_valid_path.html.twig'));
        // force initRuntime()
        $this->twig->loadTemplate('datasource.html.twig');
    }

    public function testInitRuntimeWithValidPathToTheme()
    {
        $this->twig->addExtension($this->extension);
        // force initRuntime()
        $this->assertInstanceOf(Template::class, $this->twig->loadTemplate('datasource.html.twig'));
    }

    public function testDataSourceFilterCount()
    {
        $this->twig->addExtension($this->extension);
        // force initRuntime()
        $this->twig->loadTemplate('datasource.html.twig');

        $datasourceView = $this->getDataSourceView('datasource');
        $fieldView1 = $this->createMock(FieldViewInterface::class);
        $fieldView1->expects($this->atLeastOnce())->method('hasAttribute')->with('form')->willReturn(true);

        $fieldView2 = $this->createMock(FieldViewInterface::class);
        $fieldView2->expects($this->atLeastOnce())->method('hasAttribute')->with('form')->willReturn(false);

        $fieldView3 = $this->createMock(FieldViewInterface::class);
        $fieldView3->expects($this->atLeastOnce())->method('hasAttribute')->with('form')->willReturn(true);

        $datasourceView->expects($this->atLeastOnce())
            ->method('getFields')
            ->willReturn([$fieldView1, $fieldView2, $fieldView3])
        ;

        $this->assertEquals(2, $this->extension->datasourceFilterCount($datasourceView));
    }

    public function testDataSourceRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        // force initRuntime()
        $this->twig->loadTemplate('datasource.html.twig');
        $template = $this->getTemplateMock();
        $template->expects($this->at(0))->method('hasBlock')
            ->with('datasource_datasource_filter')
            ->willReturn(false)
        ;
        $template->expects($this->at(1))->method('getParent')->with([])->willReturn(false);
        $template->expects($this->at(2))->method('hasBlock')->with('datasource_filter')->willReturn(true);

        $datasourceView = $this->getDataSourceView('datasource');
        $this->extension->setTheme($datasourceView, $template);

        $template->expects($this->at(3))
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

    public function testDataSourceRenderBlockFromParent()
    {
        $this->twig->addExtension($this->extension);
        // force initRuntime()
        $this->twig->loadTemplate('datasource.html.twig');

        $parent = $this->getTemplateMock();
        $template = $this->getTemplateMock();
        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datasource_datasource_filter')
            ->willReturn(false)
        ;

        $template->expects($this->at(1))->method('getParent')->with([])->willReturn(false);
        $template->expects($this->at(2))->method('hasBlock')->with('datasource_filter')->willReturn(false);
        $template->expects($this->at(3))->method('getParent')->with([])->willReturn($parent);
        $parent->expects($this->at(0))->method('hasBlock')->with('datasource_filter')->willReturn(true);

        $datasourceView = $this->getDataSourceView('datasource');
        $this->extension->setTheme($datasourceView, $template);

        $parent->expects($this->at(1))
            ->method('displayBlock')
            ->with('datasource_filter', [
                'datasource' => $datasourceView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->willReturn(true);

        $this->extension->datasourceFilter($datasourceView);
    }

    protected function setUp()
    {
        $subPath = version_compare(Kernel::VERSION, '2.7.0', '<') ? 'Symfony/Bridge/Twig/' : '';
        $loader = new FilesystemLoader([
            __DIR__ . '/../../../vendor/symfony/twig-bridge/' . $subPath . 'Resources/views/Form',
            __DIR__ . '/../../../Resources/views', // datasource base theme
        ]);

        $twig = new Environment($loader);
        $twig->addExtension(new TranslationExtension(new StubTranslator()));
        $twig->addExtension($this->getFormExtension($subPath !== ''));
        $twig->addGlobal('global_var', 'global_value');
        $this->twig = $twig;

        $this->extension = new DataSourceExtension($this->getContainer(), 'datasource.html.twig');
    }

    private function getRouter(): MockObject
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->any())->method('generate')->willReturn('some_route');

        return $router;
    }

    private function getContainer(): MockObject
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('get')->with('router')->willReturn($this->getRouter());

        return $container;
    }

    private function getDataSourceView(string $name): MockObject
    {
        $datasourceView = $this->createMock(DataSourceViewInterface::class);
        $datasourceView->expects($this->any())->method('getName')->willReturn($name);

        return $datasourceView;
    }

    private function getTemplateMock(): MockObject
    {
        return $this->createMock(Template::class);
    }

    private function getFormExtension(bool $legacy): FormExtension
    {
        if (true === $legacy) {
            $rendererEngine = new TwigRendererEngine(['form_div_layout.html.twig',]);
            $renderer = new TwigRenderer($rendererEngine);
            $formExtension = new FormExtension($renderer);
        } else {
            $formExtension = new FormExtension();
        }

        return $formExtension;
    }
}
