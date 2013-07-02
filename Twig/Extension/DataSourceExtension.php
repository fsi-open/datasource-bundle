<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Lukasz Cybula <lukasz@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FSi\Bundle\DataSourceBundle\Twig\TokenParser\DataSourceThemeTokenParser;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Field\FieldViewInterface;

class DataSourceExtension extends \Twig_Extension
{
    /**
     * Default theme key in themes array.
     */
    const DEFAULT_THEME = 'default_theme';

    /**
     * @var array
     */
    private $themes;

    /**
     * @var array
     */
    private $themesVars;

    /**
     * @var Twig_TemplateInterface
     */
    private $baseTemplate;

    /**
     * @var ContainerInterace
     */
    private $container;

    /**
     * @var Twig_Environment
     */
    private $environment;

    public function __construct(ContainerInterface $container, $template)
    {
        $this->themes = array();
        $this->themesVars = array();
        $this->container = $container;
        $this->baseTemplate = $template;
    }

    public function getName()
    {
        return 'datasource';
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
        $this->themes[self::DEFAULT_THEME] = $this->environment->loadTemplate($this->baseTemplate);
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return array(
            'datasource_filter_widget' => new \Twig_Function_Method($this, 'datasourceFilter', array('is_safe' => array('html'))),
            'datasource_field_widget' => new \Twig_Function_Method($this, 'datasourceField', array('is_safe' => array('html'))),
            'datasource_sort_widget' => new \Twig_Function_Method($this, 'datasourceSort', array('is_safe' => array('html'))),
            'datasource_pagination_widget' =>  new \Twig_Function_Method($this, 'datasourcePagination', array('is_safe' => array('html'))),
            'datasource_max_results_widget' =>  new \Twig_Function_Method($this, 'datasourceMaxResults', array('is_safe' => array('html'))),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenParsers()
    {
        return array(
            new DataSourceThemeTokenParser(),
        );
    }

    /**
     * Set theme for specific DataSource.
     * Theme is nothing more than twig template that contains some or all of blocks required to render DataSource.
     *
     * @param DataSourceViewInterface $dataSource
     * @param $theme
     * @param array $vars
     */
    public function setTheme(DataSourceViewInterface $dataSource, $theme, array $vars = array())
    {
        $this->themes[$dataSource->getName()] = ($theme instanceof \Twig_TemplateInterface)
            ? $theme
            : $this->environment->loadTemplate($theme);
        $this->themesVars[$dataSource->getName()] = $vars;
    }

    public function datasourceFilter(DataSourceViewInterface $view, array $vars = array())
    {
        $blockNames = array(
            'datasource_' . $view->getName() . '_filter',
            'datasource_filter',
        );

        $viewData = array(
            'datasource' => $view,
            'vars' => array_merge(
                $this->getVars($view),
                $vars
            )
        );

        return $this->renderTheme($view, $viewData, $blockNames);
    }

    public function datasourceField(FieldViewInterface $fieldView, array $vars = array())
    {
        $dataSourceView = $fieldView->getDataSourceView();
        $blockNames = array(
            'datasource_' . $dataSourceView->getName() . '_field_name_' . $fieldView->getName(),
            'datasource_' . $dataSourceView->getName() . '_field_type_' . $fieldView->getType(),
            'datasource_field_name_' . $fieldView->getName(),
            'datasource_field_type_' . $fieldView->getType(),
            'datasource_' . $dataSourceView->getName() . '_field',
            'datasource_field',
        );

        $viewData = array(
            'field' => $fieldView,
            'vars' => array_merge(
                $this->getVars($fieldView->getDataSourceView()),
                $vars
            )
        );

        return $this->renderTheme($dataSourceView, $viewData, $blockNames);
    }

    private function validateSortOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
        ->setDefaults(array(
                'route' => $this->getCurrentRoute(),
                'additional_parameters' => array(),
                'ascending' => '&uarr;',
                'descending' => '&darr;',
        ))
        ->setAllowedTypes(array(
                'route' => 'string',
                'additional_parameters' => 'array',
                'ascending' => 'string',
                'descending' => 'string',
        ));
        $options = $optionsResolver->resolve($options);
        return $options;
    }

    public function datasourceSort(FieldViewInterface $fieldView, array $options = array(), array $vars = array())
    {
        if (!$fieldView->getAttribute('sortable'))
            return;

        $dataSourceView = $fieldView->getDataSourceView();
        $blockNames = array(
            'datasource_' . $dataSourceView->getName() . '_sort',
            'datasource_sort',
        );

        $options = $this->validateSortOptions($options);
        $ascendingUrl = $this->container->get('router')->generate(
            $options['route'],
            array_merge($options['additional_parameters'], $fieldView->getAttribute('parameters_sort_ascending'))
        );
        $descendingUrl = $this->container->get('router')->generate(
            $options['route'],
            array_merge($options['additional_parameters'], $fieldView->getAttribute('parameters_sort_descending'))
        );

        $viewData = array(
            'field' => $fieldView,
            'ascending_url' => $ascendingUrl,
            'descending_url' => $descendingUrl,
            'ascending' => $options['ascending'],
            'descending' => $options['descending'],
            'vars' => array_merge(
                $this->getVars($fieldView->getDataSourceView()),
                $vars
            )
        );

        return $this->renderTheme($dataSourceView, $viewData, $blockNames);
    }

    private function validatePaginationOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setOptional(array('max_pages'))
            ->setDefaults(array(
                'route' => $this->getCurrentRoute(),
                'additional_parameters' => array(),
                'active_class' => 'active',
                'disabled_class' => 'disabled',
                'translation_domain' => 'DataSourceBundle'
            ))
            ->setAllowedTypes(array(
                'route' => 'string',
                'additional_parameters' => 'array',
                'max_pages' => 'int',
                'active_class' => 'string',
                'disabled_class' => 'string',
                'translation_domain' => 'string'
            ));
        $options = $optionsResolver->resolve($options);
        return $options;
    }

    public function datasourcePagination(DataSourceViewInterface $view, $options = array(), $vars = array())
    {
        $blockNames = array(
            'datasource_' . $view->getName() . '_pagination',
            'datasource_pagination',
        );

        $options = $this->validatePaginationOptions($options);
        $router = $this->container->get('router');

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
        $pagesAnchors = array();
        $pagesUrls = array();
        foreach ($pages as $page) {
            $pagesUrls[$page] = $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[$page]));
        }

        $viewData = array(
            'datasource' => $view,
            'page_anchors' => $pagesAnchors,
            'pages_urls' => $pagesUrls,
            'first' => 1,
            'first_url' => $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[1])),
            'last' => $pageCount,
            'last_url' => $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[$pageCount])),
            'current' => $current,
            'active_class' => $options['active_class'],
            'disabled_class' => $options['disabled_class'],
            'translation_domain' => $options['translation_domain'],
            'vars' => array_merge($this->getVars($view), $vars),
        );
        if ($current != 1) {
            $viewData['prev'] = $current - 1;
            $viewData['prev_url'] = $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[$current - 1]));
        }
        if ($current != $pageCount) {
            $viewData['next'] = $current + 1;
            $viewData['next_url'] = $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[$current + 1]));
        }

        return $this->renderTheme($view, $viewData, $blockNames);
    }

    /**
     * Validate and resolve options passed in Twig to datasource_results_per_page_widget
     *
     * @param array $options
     * @return array
     */
    private function validateMaxResultsOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefaults(array(
                'route' => $this->getCurrentRoute(),
                'active_class' => 'active',
                'additional_parameters' => array(),
                'results' => array(5, 10, 20, 50, 100)
            ))
            ->setAllowedTypes(array(
                'route' => 'string',
                'active_class' => 'string',
                'additional_parameters' => 'array',
                'results' => 'array'
            ));

        $options = $optionsResolver->resolve($options);

        return $options;
    }

    public function datasourceMaxResults(DataSourceViewInterface $view, $options = array(), $vars = array())
    {
        $options = $this->validateMaxResultsOptions($options);
        $router = $this->container->get('router');
        $blockNames = array(
            'datasource_' . $view->getName() . '_max_results',
            'datasource_max_results',
        );

        $baseParameters = $view->getAllParameters();
        if (!isset($baseParameters[$view->getName()])) {
            $baseParameters[$view->getName()] = array();
        }

        $results = array();
        foreach ($options['results'] as $resultsPerPage) {
            $baseParameters[$view->getName()][PaginationExtension::PARAMETER_MAX_RESULTS] = $resultsPerPage;
            $results[$resultsPerPage] = $router->generate($options['route'], array_merge( $options['additional_parameters'], $baseParameters));
        }

        $viewData = array(
            'datasource' => $view,
            'results' => $results,
            'active_class' => $options['active_class'],
            'max_results' => $view->getAttribute('max_results'),
            'vars' => array_merge($this->getVars($view), $vars),
        );

        return $this->renderTheme($view, $viewData, $blockNames);
    }

    private function getCurrentRoute()
    {
        $router = $this->container->get('router');
        $request = $this->container->get('request');
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
    private function getTemplates(DataSourceViewInterface $dataSource)
    {
        $templates = array();

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
        if (isset($this->themesVars[$dataSource->getName()])) {
            return $this->themesVars[$dataSource->getName()];
        }

        return array();
    }

    /**
     * @param DataSourceViewInterface $view
     * @param array $contextVars
     * @param $availableBlocks
     * @return string
     */
    private function renderTheme(DataSourceViewInterface $view, array $contextVars = array(), $availableBlocks = array())
    {
        $templates = $this->getTemplates($view);

        ob_start();

        foreach ($availableBlocks as $blockName) {
            foreach ($templates as $template) {
                if (false !== ($template = $this->findTemplateWithBlock($template, $blockName))) {
                    $template->displayBlock($blockName, $contextVars);

                    return ob_get_clean();
                }
            }
        }

        return ob_get_clean();
    }

    /**
     * @param \Twig_TemplateInterface $template
     * @param string $blockName
     * @return \Twig_TemplateInterface|bool
     */
    private function findTemplateWithBlock(\Twig_TemplateInterface $template, $blockName)
    {
        if ($template->hasBlock($blockName)) {
            return $template;
        }

        // Check parents
        if (false !== ($parent = $template->getParent(array()))) {
            return $this->findTemplateWithBlock($parent, $blockName);
        }

        return false;
    }
}
