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
            'datasource_sort_widget' =>  new \Twig_Function_Method($this, 'datasourceSort', array('is_safe' => array('html'))),
            'datasource_sort_ascending_widget' =>  new \Twig_Function_Method($this, 'datasourceSortAscending', array('is_safe' => array('html'))),
            'datasource_sort_descending_widget' =>  new \Twig_Function_Method($this, 'datasourceSortDescending', array('is_safe' => array('html'))),
            'datasource_pagination_widget' =>  new \Twig_Function_Method($this, 'datasourcePagination', array('is_safe' => array('html'))),
            'datasource_anchor' =>  new \Twig_Function_Method($this, 'datasourceAnchor', array('is_safe' => array('html'))),
            'datasource_render_attributes' =>  new \Twig_Function_Method($this, 'datasourceAttributes', array('is_safe' => array('html'))),
        );
    }

    public function datasourceFilter(DataSourceViewInterface $view, $options = array())
    {
        if (!isset($options['exclude']))
            $options['exclude'] = array();

        if (!is_array($options['exclude']))
            $options['exclude'] = array($options['exclude']);

        $fields = array();
        foreach ($view as $fieldView) {
            if (!in_array($fieldView->getField()->getName(), $options['exclude'])) {
                $fields[$fieldView->getField()->getName()] = $fieldView;
            }
        }

        return $this->template->renderBlock('datasource_filter', array(
            'datasource' => $view,
            'fields' => $fields
        ));
    }

    public function datasourceField(FieldViewInterface $fieldView, $options = array())
    {
        $fieldOptions = array_merge($fieldView->getAttribute('options'), $options);

        return $this->template->renderBlock('datasource_field', array(
            'form' => $fieldView->getAttribute('form'),
            'options' => $fieldOptions
        ));
    }

    public function datasourceSort(FieldViewInterface $fieldView, $options = array())
    {
        if (!$fieldView->getAttribute('ordering_disabled')) {
            $options = array_merge($fieldView->getAttribute('options'), $options);
            return $this->template->renderBlock('datasource_sort', array(
                'field' => $fieldView,
            ));
        }
    }

    public function datasourceSortAscending(FieldViewInterface $fieldView, $options = array())
    {
        if (!$fieldView->getAttribute('ordering_disabled')) {
            $options = array_merge($fieldView->getAttribute('options'), $options);
            $options['sort_ascending_anchor']['parameters'] = $fieldView->getAttribute('ordering_ascending');
            return $this->template->renderBlock('datasource_anchor', $options['sort_ascending_anchor']);
        }
    }

    public function datasourceSortDescending(FieldViewInterface $fieldView, $options = array())
    {
        if (!$fieldView->getAttribute('ordering_disabled')) {
            $options = array_merge($fieldView->getAttribute('options'), $options);
            $options['sort_descending_anchor']['parameters'] = $fieldView->getAttribute('ordering_descending');
            return $this->template->renderBlock('datasource_anchor', $options['sort_descending_anchor']);
        }
    }

    private function validateAnchorOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setOptional(array('route', 'active_class'))
            ->setDefaults(array(
                'route' => $this->getCurrentRoute(),
                'additional_parameters' => array(),
                'attributes' => array(),
                'content' => ''
            ))
            ->setAllowedTypes(array(
                'route' => 'string',
                'additional_parameters' => 'array',
                'attributes' => 'array',
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
                'anchors' => array(),
                'page_anchors' => array(),
                'previous_anchor' => array(),
                'next_anchor' => array(),
                'first_anchor' => array(),
                'last_anchor' => array(),
            ))
            ->setAllowedTypes(array(
                'max_pages' => 'int',
                'anchors' => 'array',
                'page_anchors' => 'array',
                'previous_anchor' => 'array',
                'next_anchor' => 'array',
                'first_anchor' => 'array',
                'last_anchor' => 'array',
            ));
        $options = $optionsResolver->resolve($options);
        $options['anchors'] = $this->validateAnchorOptions($options['anchors']);
        $options['page_anchors'] = array_merge(
            $options['anchors'],
            $this->validateAnchorOptions($options['page_anchors'])
        );
        $options['page_anchors']['attributes'] = array_merge($options['anchors']['attributes'], $options['page_anchors']['attributes']);
        $options['page_anchors']['additional_parameters'] = array_merge($options['anchors']['additional_parameters'], $options['page_anchors']['additional_parameters']);
        $options['previous_anchor'] = array_merge(
            $options['page_anchors'],
            $this->validateAnchorOptions($options['previous_anchor'])
        );
        $options['previous_anchor']['attributes'] = array_merge($options['page_anchors']['attributes'], $options['previous_anchor']['attributes']);
        $options['previous_anchor']['additional_parameters'] = array_merge($options['page_anchors']['additional_parameters'], $options['previous_anchor']['additional_parameters']);
        $options['next_anchor'] = array_merge(
            $options['page_anchors'],
            $this->validateAnchorOptions($options['next_anchor'])
        );
        $options['next_anchor']['attributes'] = array_merge($options['page_anchors']['attributes'], $options['next_anchor']['attributes']);
        $options['next_anchor']['additional_parameters'] = array_merge($options['page_anchors']['additional_parameters'], $options['next_anchor']['additional_parameters']);
        $options['first_anchor'] = array_merge(
                $options['page_anchors'],
                $this->validateAnchorOptions($options['first_anchor'])
        );
        $options['first_anchor']['attributes'] = array_merge($options['page_anchors']['attributes'], $options['first_anchor']['attributes']);
        $options['first_anchor']['additional_parameters'] = array_merge($options['page_anchors']['additional_parameters'], $options['first_anchor']['additional_parameters']);
        $options['last_anchor'] = array_merge(
                $options['page_anchors'],
                $this->validateAnchorOptions($options['last_anchor'])
        );
        $options['last_anchor']['attributes'] = array_merge($options['page_anchors']['attributes'], $options['last_anchor']['attributes']);
        $options['last_anchor']['additional_parameters'] = array_merge($options['page_anchors']['additional_parameters'], $options['last_anchor']['additional_parameters']);
        return $options;
    }

    public function datasourcePagination(DataSourceViewInterface $view, $options = array())
    {
        $options = $this->validateOptions($options);

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
        foreach ($pages as $page) {
            $pagesAnchors[$page] = $options['page_anchors'];
            $pagesAnchors[$page]['content'] = $page;
            if (!isset($pagesAnchors[$page]['attributes']['title']))
                $pagesAnchors[$page]['attributes']['title'] = $page;
            if ($page != $current) {
                $pagesAnchors[$page]['parameters'] = $pagesParams[$page];
            } else {
                $pagesAnchors[$page]['attributes']['href'] = '#';
                if (isset($options['page_anchors']['active_class'])) {
                    $pagesAnchors[$page]['attributes']['class'] =
                        (isset($pagesAnchors[$page]['attributes']['class']) ? ($pagesAnchors[$page]['attributes']['class'] . ' ') : '' ) .
                        $options['page_anchors']['active_class'];
                }
            }
        }

        $viewData = array(
            'page_anchors' => $pagesAnchors,
        );

        $viewData['first_anchor'] = array_merge($options['page_anchors'], $options['first_anchor']);
        if ($current != 1) {
            $viewData['first_anchor']['parameters'] = $pagesParams[1];
        } else {
            $viewData['first_anchor']['attributes']['href'] = '#';
            if (isset($viewData['first_anchor']['active_class'])) {
                $viewData['first_anchor']['attributes']['class'] =
                    (isset($viewData['first_anchor']['attributes']['class']) ? ($viewData['first_anchor']['attributes']['class'] . ' ') : '' ) .
                    $viewData['first_anchor']['active_class'];
            }
        }
        if (!isset($viewData['first_anchor']['attributes']['title']))
            $viewData['first_anchor']['attributes']['title'] = '1';

        $viewData['last_anchor'] = array_merge($options['page_anchors'], $options['last_anchor']);
        if ($current + 1 != $pageCount) {
            $viewData['last_anchor']['parameters'] = $pagesParams[$pageCount];
        } else {
            $viewData['last_anchor']['attributes']['href'] = '#';
            if (isset($viewData['last_anchor']['active_class'])) {
                $viewData['last_anchor']['attributes']['class'] =
                    (isset($viewData['last_anchor']['attributes']['class']) ? ($viewData['last_anchor']['attributes']['class'] . ' ') : '' ) .
                    $viewData['last_anchor']['active_class'];
            }
        }
        if (!isset($viewData['last_anchor']['attributes']['title']))
            $viewData['last_anchor']['attributes']['title'] = (string) $pageCount;

        $viewData['previous_anchor'] = array_merge($options['page_anchors'], $options['previous_anchor']);
        if ($current - 1 > 0) {
            $viewData['previous_anchor']['parameters'] = $pagesParams[$current - 1];
            if (!isset($viewData['previous_anchor']['attributes']['title']))
                $viewData['previous_anchor']['attributes']['title'] = (string) ($current - 1);
        } else {
            $viewData['previous_anchor']['attributes']['href'] = '#';
            if (isset($viewData['previous_anchor']['active_class'])) {
                $viewData['previous_anchor']['attributes']['class'] =
                    (isset($viewData['previous_anchor']['attributes']['class']) ? ($viewData['previous_anchor']['attributes']['class'] . ' ') : '' ) .
                    $viewData['previous_anchor']['active_class'];
            }
        }

        $viewData['next_anchor'] = array_merge($options['page_anchors'], $options['next_anchor']);
        if ($current + 1 <= $pageCount) {
            $viewData['next_anchor']['parameters'] = $pagesParams[$current + 1];
            if (!isset($viewData['next_anchor']['attributes']['title']))
                $viewData['next_anchor']['attributes']['title'] = (string) ($current + 1);
        } else {
            $viewData['next_anchor']['attributes']['href'] = '#';
            if (isset($viewData['next_anchor']['active_class'])) {
                $viewData['next_anchor']['attributes']['class'] =
                    (isset($viewData['next_anchor']['attributes']['class']) ? ($viewData['next_anchor']['attributes']['class'] . ' ') : '' ) .
                    $viewData['next_anchor']['active_class'];
            }
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
