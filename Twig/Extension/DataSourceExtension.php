<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\Extension;

use FSi\Bundle\DataSourceBundle\Twig\TokenParser\DataSourceRouteTokenParser;
use FSi\Bundle\DataSourceBundle\Twig\TokenParser\DataSourceThemeTokenParser;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Field\FieldViewInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\InitRuntimeInterface;
use Twig\Template;
use Twig\TwigFunction;

class DataSourceExtension extends AbstractExtension implements InitRuntimeInterface
{
    /**
     * Default theme key in themes array.
     */
    public const DEFAULT_THEME = 'default_theme';

    /**
     * @var array
     */
    private $themes;

    /**
     * @var array
     */
    private $themesVars;

    /**
     * @var array
     */
    private $routes;

    /**
     * @var array
     */
    private $additionalParameters;

    /**
     * @var Template
     */
    private $baseTemplate;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Environment
     */
    private $environment;

    public function __construct(ContainerInterface $container, string $template)
    {
        $this->themes = [];
        $this->themesVars = [];
        $this->container = $container;
        $this->baseTemplate = $template;
    }

    public function getName()
    {
        return 'datasource';
    }

    public function initRuntime(Environment $environment)
    {
        $this->environment = $environment;
        $this->themes[self::DEFAULT_THEME] = $this->environment->loadTemplate($this->baseTemplate);
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('datasource_filter_widget', [$this, 'datasourceFilter'], ['is_safe' => ['html']]),
            new TwigFunction('datasource_filter_count', [$this, 'datasourceFilterCount'], ['is_safe' => ['html']]),
            new TwigFunction('datasource_field_widget', [$this, 'datasourceField'], ['is_safe' => ['html']]),
            new TwigFunction('datasource_sort_widget', [$this, 'datasourceSort'], ['is_safe' => ['html']]),
            new TwigFunction('datasource_pagination_widget', [$this, 'datasourcePagination'], ['is_safe' => ['html']]),
            new TwigFunction('datasource_max_results_widget', [$this, 'datasourceMaxResults'], ['is_safe' => ['html']]),
        ];
    }

    public function getTokenParsers()
    {
        return [
            new DataSourceThemeTokenParser(),
            new DataSourceRouteTokenParser(),
        ];
    }

    /**
     * Set theme for specific DataSource.
     * Theme is nothing more than twig template that contains some or all of blocks required to render DataSource.
     *
     * @param DataSourceViewInterface $dataSource
     * @param $theme
     * @param array $vars
     */
    public function setTheme(DataSourceViewInterface $dataSource, $theme, array $vars = [])
    {
        $this->themes[$dataSource->getName()] = ($theme instanceof Template)
            ? $theme
            : $this->environment->loadTemplate($theme);
        $this->themesVars[$dataSource->getName()] = $vars;
    }

    /**
     * Set route and optionally additional parameters for specific DataSource.
     *
     * @param DataSourceViewInterface $dataSource
     * @param $route
     * @param array $additionalParameters
     */
    public function setRoute(DataSourceViewInterface $dataSource, $route, array $additionalParameters = [])
    {
        $this->routes[$dataSource->getName()] = $route;
        $this->additionalParameters[$dataSource->getName()] = $additionalParameters;
    }

    public function datasourceFilter(DataSourceViewInterface $view, array $vars = [])
    {
        $blockNames = [
            'datasource_' . $view->getName() . '_filter',
            'datasource_filter',
        ];

        $viewData = [
            'datasource' => $view,
            'vars' => array_merge(
                $this->getVars($view),
                $vars
            )
        ];

        return $this->renderTheme($view, $viewData, $blockNames);
    }

    public function datasourceFilterCount(DataSourceViewInterface $view)
    {
        $fields = $view->getFields();
        $count = 0;
        /** @var $field FieldViewInterface */
        foreach ($fields as $field) {
            if ($field->hasAttribute('form')) {
                $count++;
            }
        }
        return $count;
    }

    public function datasourceField(FieldViewInterface $fieldView, array $vars = [])
    {
        $dataSourceView = $fieldView->getDataSourceView();
        $blockNames = [
            'datasource_' . $dataSourceView->getName() . '_field_name_' . $fieldView->getName(),
            'datasource_' . $dataSourceView->getName() . '_field_type_' . $fieldView->getType(),
            'datasource_field_name_' . $fieldView->getName(),
            'datasource_field_type_' . $fieldView->getType(),
            'datasource_' . $dataSourceView->getName() . '_field',
            'datasource_field',
        ];

        $viewData = [
            'field' => $fieldView,
            'vars' => array_merge(
                $this->getVars($fieldView->getDataSourceView()),
                $vars
            )
        ];

        return $this->renderTheme($dataSourceView, $viewData, $blockNames);
    }

    private function resolveSortOptions(array $options, DataSourceViewInterface $dataSource)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefaults([
                'route' => $this->getCurrentRoute($dataSource),
                'additional_parameters' => [],
                'ascending' => '&uarr;',
                'descending' => '&darr;',
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('additional_parameters', 'array')
            ->setAllowedTypes('ascending', 'string')
            ->setAllowedTypes('descending', 'string');

        return $optionsResolver->resolve($options);
    }

    public function datasourceSort(FieldViewInterface $fieldView, array $options = [], array $vars = [])
    {
        if (!$fieldView->getAttribute('sortable')) {
            return;
        }

        $dataSourceView = $fieldView->getDataSourceView();
        $blockNames = [
            'datasource_' . $dataSourceView->getName() . '_sort',
            'datasource_sort',
        ];

        $options = $this->resolveSortOptions($options, $dataSourceView);
        $ascendingUrl = $this->getUrl($dataSourceView, $options, $fieldView->getAttribute('parameters_sort_ascending'));
        $descendingUrl = $this->getUrl($dataSourceView, $options, $fieldView->getAttribute('parameters_sort_descending'));

        $viewData = [
            'field' => $fieldView,
            'ascending_url' => $ascendingUrl,
            'descending_url' => $descendingUrl,
            'ascending' => $options['ascending'],
            'descending' => $options['descending'],
            'vars' => array_merge(
                $this->getVars($fieldView->getDataSourceView()),
                $vars
            )
        ];

        return $this->renderTheme($dataSourceView, $viewData, $blockNames);
    }

    private function resolvePaginationOptions(array $options, DataSourceViewInterface $dataSource)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefined(['max_pages'])
            ->setDefaults([
                'route' => $this->getCurrentRoute($dataSource),
                'additional_parameters' => [],
                'active_class' => 'active',
                'disabled_class' => 'disabled',
                'translation_domain' => 'DataSourceBundle'
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('additional_parameters', 'array')
            ->setAllowedTypes('max_pages', 'int')
            ->setAllowedTypes('active_class', 'string')
            ->setAllowedTypes('disabled_class', 'string')
            ->setAllowedTypes('translation_domain', 'string');

        return $optionsResolver->resolve($options);
    }

    public function datasourcePagination(DataSourceViewInterface $view, $options = [], $vars = [])
    {
        $blockNames = [
            'datasource_' . $view->getName() . '_pagination',
            'datasource_pagination',
        ];

        $options = $this->resolvePaginationOptions($options, $view);

        $pagesParams = $view->getAttribute('parameters_pages');
        $current = $view->getAttribute('page');
        $pageCount = count($pagesParams);
        if ($pageCount < 2)
            return;

        if (isset($options['max_pages'])) {
            $delta = ceil($options['max_pages'] / 2);

            if ($current - $delta > $pageCount - $options['max_pages']) {
                $pages = range(max($pageCount - $options['max_pages'] + 1, 1), $pageCount);
            } else {
                if ($current - $delta < 0) {
                    $delta = $current;
                }

                $offset = $current - $delta;
                $pages = range($offset + 1, min($offset + $options['max_pages'], $pageCount));
            }
        } else {
            $pages = range(1, $pageCount);
        }
        $pagesAnchors = [];
        $pagesUrls = [];
        foreach ($pages as $page) {
            $pagesUrls[$page] = $this->getUrl($view, $options, $pagesParams[$page]);
        }

        $viewData = [
            'datasource' => $view,
            'page_anchors' => $pagesAnchors,
            'pages_urls' => $pagesUrls,
            'first' => 1,
            'first_url' => $this->getUrl($view, $options, $pagesParams[1]),
            'last' => $pageCount,
            'last_url' => $this->getUrl($view, $options, $pagesParams[$pageCount]),
            'current' => $current,
            'active_class' => $options['active_class'],
            'disabled_class' => $options['disabled_class'],
            'translation_domain' => $options['translation_domain'],
            'vars' => array_merge($this->getVars($view), $vars),
        ];
        if ($current != 1 && isset($pagesParams[$current - 1])) {
            $viewData['prev'] = $current - 1;
            $viewData['prev_url'] = $this->getUrl($view, $options, $pagesParams[$current - 1]);
        }
        if ($current != $pageCount && isset($pagesParams[$current + 1])) {
            $viewData['next'] = $current + 1;
            $viewData['next_url'] = $this->getUrl($view, $options, $pagesParams[$current + 1]);
        }

        return $this->renderTheme($view, $viewData, $blockNames);
    }

    /**
     * Validate and resolve options passed in Twig to datasource_results_per_page_widget
     *
     * @param array $options
     * @return array
     */
    private function resolveMaxResultsOptions(array $options, DataSourceViewInterface $dataSource): array
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefaults([
                'route' => $this->getCurrentRoute($dataSource),
                'active_class' => 'active',
                'additional_parameters' => [],
                'results' => [5, 10, 20, 50, 100]
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('active_class', 'string')
            ->setAllowedTypes('additional_parameters', 'array')
            ->setAllowedTypes('results', 'array');

        return $optionsResolver->resolve($options);
    }

    public function datasourceMaxResults(DataSourceViewInterface $view, $options = [], $vars = []): string
    {
        $options = $this->resolveMaxResultsOptions($options, $view);
        $blockNames = [
            'datasource_' . $view->getName() . '_max_results',
            'datasource_max_results',
        ];

        $baseParameters = $view->getAllParameters();
        if (!isset($baseParameters[$view->getName()])) {
            $baseParameters[$view->getName()] = [];
        }

        $results = [];
        foreach ($options['results'] as $resultsPerPage) {
            $baseParameters[$view->getName()][PaginationExtension::PARAMETER_MAX_RESULTS] = $resultsPerPage;
            $results[$resultsPerPage] = $this->getUrl($view, $options, $baseParameters);
        }

        $viewData = [
            'datasource' => $view,
            'results' => $results,
            'active_class' => $options['active_class'],
            'max_results' => $view->getAttribute('max_results'),
            'vars' => array_merge($this->getVars($view), $vars),
        ];

        return $this->renderTheme($view, $viewData, $blockNames);
    }

    private function getCurrentRoute(DataSourceViewInterface $dataSource): string
    {
        if (isset($this->routes[$dataSource->getName()])) {
            return $this->routes[$dataSource->getName()];
        }

        /* @var $requestStack RequestStack */
        $requestStack = $this->container->get('request_stack');
        $request = $requestStack->getMasterRequest();
        if ($request->attributes->get('_route') === '_fragment') {
            throw new RuntimeException(
                'Some datasource widget was called during Symfony internal request.
                You must use {% datasource_route %} twig tag to specify target
                route and/or additional parameters for this datasource\'s actions'
            );
        }
        $router = $this->container->get('router');
        $parameters = $router->match($request->getPathInfo());
        return $parameters['_route'];
    }

    /**
     * Return list of templates that might be useful to render DataSourceView.
     * Always the last template will be default one.
     *
     * @param DataSourceViewInterface $dataSource
     * @return array
     */
    private function getTemplates(DataSourceViewInterface $dataSource): array
    {
        $templates = [];
        if (isset($this->themes[$dataSource->getName()])) {
            $templates[] = $this->themes[$dataSource->getName()];
        }

        $templates[] = $this->themes[self::DEFAULT_THEME];
        return $templates;
    }

    /**
     * Return vars passed to theme. Those vars will be added to block context.
     *
     * @param DataSourceViewInterface $dataSource
     * @return array
     */
    private function getVars(DataSourceViewInterface $dataSource)
    {
        return isset($this->themesVars[$dataSource->getName()])
            ? $this->themesVars[$dataSource->getName()]
            : []
        ;
    }

    /**
     * Return additional parameters that should be passed to the URL generation for specified datasource.
     *
     * @param DataSourceViewInterface $dataSource
     * @return array
     */
    private function getUrl(DataSourceViewInterface $dataSource, array $options = [], array $parameters = [])
    {
        /** @var UrlGeneratorInterface $router */
        $router = $this->container->get('router');

        return $router->generate(
            $options['route'],
            array_merge(
                isset($this->additionalParameters[$dataSource->getName()])
                    ? $this->additionalParameters[$dataSource->getName()]
                    : [],
                isset($options['additional_parameters'])
                    ? $options['additional_parameters']
                    : [],
                $parameters
            )
        );
    }

    /**
     * @param DataSourceViewInterface $view
     * @param array $contextVars
     * @param $availableBlocks
     * @return string
     */
    private function renderTheme(
        DataSourceViewInterface $view,
        array $contextVars = [],
        array $availableBlocks = []
    ): string {
        $templates = $this->getTemplates($view);
        $contextVars = $this->environment->mergeGlobals($contextVars);

        ob_start();
        foreach ($availableBlocks as $blockName) {
            foreach ($templates as $template) {
                $template = $this->findTemplateWithBlock($template, $blockName, $contextVars);
                if (true === $template instanceof Template) {
                    $template->displayBlock($blockName, $contextVars);
                    return ob_get_clean();
                }
            }
        }

        return ob_get_clean();
    }

    private function findTemplateWithBlock(Template $template, string $blockName, array $contextVars): ?Template
    {
        if ($template->hasBlock($blockName, $contextVars)) {
            return $template;
        }

        // Check parents
        $parent = $template->getParent([]);
        if (false !== $parent) {
            return $this->findTemplateWithBlock($parent, $blockName, $contextVars);
        }

        return null;
    }
}
