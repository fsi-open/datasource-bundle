<?php

namespace FSi\Bundle\DataSourceBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;

class DataSourceExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterace
     */
    private $container;

    /**
     * @var Twig_TemplateInterface
     */
    private $template;

    /**
     * @var Twig_Environment
     */
    private $environment;

    public function __construct(ContainerInterface $container, $template)
    {
        $this->container = $container;
        $this->template = $template;
    }

    public function getName()
    {
        return 'datasource';
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
        $this->template = $this->environment->loadTemplate($this->template);
    }

    public function getFunctions()
    {
        return array(
            'datasource_filter_widget' => new \Twig_Function_Method($this, 'datasourceFilter', array('is_safe' => array('html'))),
            'datasource_field_widget' => new \Twig_Function_Method($this, 'datasourceField', array('is_safe' => array('html'))),
            'datasource_sort_asc_url' =>  new \Twig_Function_Method($this, 'datasourceSortAscendingUrl', array('is_safe' => array('html'))),
            'datasource_sort_desc_url' =>  new \Twig_Function_Method($this, 'datasourceSortDescendingUrl', array('is_safe' => array('html'))),
            'datasource_pagination_widget' =>  new \Twig_Function_Method($this, 'datasourcePagination', array('is_safe' => array('html'))),
            'datasource_anchor' =>  new \Twig_Function_Method($this, 'datasourceAnchor', array('is_safe' => array('html'))),
            'datasource_render_attributes' =>  new \Twig_Function_Method($this, 'datasourceAttributes', array('is_safe' => array('html'))),
        );
    }

    public function datasourceFilter(DataSourceViewInterface $view, array $exclude = array(), array $vars = array())
    {
        $fields = array();
        foreach ($view as $fieldView) {
            if (!in_array($fieldView->getField()->getName(), $exclude)) {
                $fields[$fieldView->getField()->getName()] = $fieldView;
            }
        }

        return $this->template->renderBlock('datasource_filter', array(
            'datasource' => $view,
            'fields' => $fields,
            'vars' => $vars
        ));
    }

    public function datasourceField(FieldViewInterface $fieldView, array $vars = array())
    {
        $filter_wrapper_attributes = $fieldView->getAttribute('filter_wrapper_attributes');

        return $this->template->renderBlock('datasource_field', array(
            'form' => $fieldView->getAttribute('form'),
            'filter_wrapper_attributes' => $filter_wrapper_attributes,
            'vars' => $vars
        ));
    }

    public function datasourceSortAscendingUrl(FieldViewInterface $fieldView, $route = null, array $additionalParameters = array())
    {
        $router = $this->container->get('router');
        return $router->generate(
            isset($route)?$route:$this->getCurrentRoute(),
            array_merge($additionalParameters, $fieldView->getAttribute('ordering_ascending'))
        );
    }

    public function datasourceSortDescendingUrl(FieldViewInterface $fieldView, $route = null, array $additionalParameters = array())
    {
        $router = $this->container->get('router');
        return $router->generate(
            isset($route)?$route:$this->getCurrentRoute(),
            array_merge($additionalParameters, $fieldView->getAttribute('ordering_descending'))
        );
    }

    private function validateAnchorOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setOptional(array('route', 'active_class', 'href'))
            ->setDefaults(array(
                'route' => $this->getCurrentRoute(),
                'additional_parameters' => array(),
                'attributes' => array(),
                'wrapper_attributes' => array(),
                'content' => ''
            ))
            ->setAllowedTypes(array(
                'href' => 'string',
                'route' => 'string',
                'additional_parameters' => 'array',
                'attributes' => 'array',
                'wrapper_attributes' => 'array',
                'active_class' => 'string',
                'content' => 'string'
            ));
        return $optionsResolver->resolve($options);
    }

    private function validateOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setOptional(array('max_pages'))
            ->setDefaults(array(
                'route' => $this->getCurrentRoute(),
                'additional_parameters' => array(),
                'wrapper_attributes' => array(),
                'active_class' => 'active',
                'disabled_class' => 'disabled',
                'translation_domain' => 'DataSourceBundle'
            ))
            ->setAllowedTypes(array(
                'route' => 'string',
                'additional_parameters' => 'array',
                'max_pages' => 'int',
                'wrapper_attributes' => 'array',
                'active_class' => 'string',
                'disabled_class' => 'string',
                'translation_domain' => 'string'
            ));
        $options = $optionsResolver->resolve($options);
        return $options;
    }

    public function datasourcePagination(DataSourceViewInterface $view, $options = array())
    {
        $options = $this->validateOptions($options);
        $router = $this->container->get('router');

        $pagesParams = $view->getAttribute('pages');
        $current = $view->getAttribute('page_current');
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
            'wrapper_attributes' => $options['wrapper_attributes'],
            'page_anchors' => $pagesAnchors,
            'pages_urls' => $pagesUrls,
            'first' => 1,
            'first_url' => $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[1])),
            'last' => $pageCount,
            'last_url' => $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[$pageCount])),
            'current' => $current,
            'active_class' => $options['active_class'],
            'disabled_class' => $options['disabled_class'],
            'translation_domain' => $options['translation_domain']
        );
        if ($current != 1) {
            $viewData['prev'] = $current - 1;
            $viewData['prev_url'] = $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[$current - 1]));
        }
        if ($current != $pageCount) {
            $viewData['next'] = $current + 1;
            $viewData['next_url'] = $router->generate($options['route'], array_merge($options['additional_parameters'], $pagesParams[$current + 1]));
        }

        return $this->template->renderBlock('datasource_pagination', $viewData);
    }

    public function datasourceAnchor($options = array())
    {
        return $this->template->renderBlock('datasource_anchor', $options);
    }

    public function datasourceAttributes(array $attributes)
    {
        return $this->template->renderBlock('datasource_render_attributes', array(
            'attributes' => $attributes,
        ));
    }

    private function getCurrentRoute()
    {
        $router = $this->container->get('router');
        $request = $this->container->get('request');
        $parameters = $router->match($request->getPathInfo());
        return $parameters['_route'];
    }
}
