<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\Twig\Extension;

use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceExtension;
use FSi\Component\DataSource\DataSourceViewInterface;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Tests\Extension\Fixtures\StubTranslator;

/**
 * @author Stanislav Prokopov <stanislav.prokopov@gmail.com>
 */
class DataSourceExtensionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var DataSourceExtension
     */
    protected $extension;

    public function setUp()
    {
        $loader = new \Twig_Loader_Filesystem(array(
            __DIR__ . '/../../../vendor/symfony/twig-bridge/Symfony/Bridge/Twig/Resources/views/Form',
            __DIR__ . '/../../../Resources/views', // datasource base theme
        ));

        $rendererEngine = new TwigRendererEngine(array(
            'form_div_layout.html.twig',
        ));
        $renderer = new TwigRenderer($rendererEngine);

        $twig = new \Twig_Environment($loader);
        $twig->addExtension(new TranslationExtension(new StubTranslator()));
        $twig->addExtension(new FormExtension($renderer));
        $this->twig = $twig;

        $this->extension = new DataSourceExtension($this->getContainer(), 'datasource.html.twig');
    }

    /**
     * @expectedException \Twig_Error_Loader
     * @expectedExceptionMessage Unable to find template "this_is_not_valid_path.html.twig"
     */
    public function testInitRuntimeShouldThrowExceptionBecauseNotExistingTheme()
    {
        $this->twig->addExtension(new DataSourceExtension($this->getContainer(), 'this_is_not_valid_path.html.twig'));
        $this->twig->initRuntime();
    }

    public function testInitRuntimeWithValidPathToTheme()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
    }

    public function testDataSourceRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
        $template = $this->getMock('\Twig_TemplateInterface', array('hasBlock', 'render', 'display', 'getEnvironment', 'displayBlock', 'getParent'));

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datasource_datasource_filter')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with(array())
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datasource_filter')
            ->will($this->returnValue(true));

        $datasourceView = $this->getDataSourceView('datasource');
        $this->extension->setTheme($datasourceView, $template);

        $template->expects($this->at(3))
            ->method('displayBlock')
            ->with('datasource_filter', array(
                'datasource' => $datasourceView,
                'vars' => array()
            ))
            ->will($this->returnValue(true));

        $this->extension->datasourceFilter($datasourceView);
    }

    public function testDataSourceRenderBlockFromParent()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();

        $parent = $this->getMock('\Twig_TemplateInterface', array('hasBlock', 'render', 'display', 'getEnvironment', 'displayBlock', 'getParent'));
        $template = $this->getMock('\Twig_TemplateInterface', array('hasBlock', 'render', 'display', 'getEnvironment', 'displayBlock', 'getParent'));

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datasource_datasource_filter')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with(array())
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datasource_filter')
            ->will($this->returnValue(false));

        $template->expects($this->at(3))
            ->method('getParent')
            ->with(array())
            ->will($this->returnValue($parent));

        $parent->expects($this->at(0))
            ->method('hasBlock')
            ->with('datasource_filter')
            ->will($this->returnValue(true));

        $datasourceView = $this->getDataSourceView('datasource');
        $this->extension->setTheme($datasourceView, $template);

        $parent->expects($this->at(1))
            ->method('displayBlock')
            ->with('datasource_filter', array(
                'datasource' => $datasourceView,
                'vars' => array()
            ))
            ->will($this->returnValue(true));

        $this->extension->datasourceFilter($datasourceView);
    }

    private function getRouter()
    {
        $router = $this->getMock('\Symfony\Component\Routing\RouterInterface', array('getRouteCollection', 'match', 'setContext', 'getContext', 'generate'));
        $router->expects($this->any())
            ->method('generate')
            ->will($this->returnValue('some_route'));

        return $router;
    }

    private function getContainer()
    {
        $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface', array('set', 'has', 'getParameter', 'hasParameter', 'setParameter', 'enterScope', 'leaveScope', 'addScope', 'hasScope', 'isScopeActive', 'get'));
        $container->expects($this->any())
            ->method('get')
            ->with('router')
            ->will($this->returnValue($this->getRouter()));

        return $container;
    }

    private function getDataSourceView($name)
    {
        $datasourceView = $this->getMockBuilder('FSi\Component\DataSource\DataSourceViewInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $datasourceView->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $datasourceView;
    }
}
