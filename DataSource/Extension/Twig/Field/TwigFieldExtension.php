<?php

/*
 * This file is part of the FSi Component package.
*
* (c) Lukasz Cybula <lukasz@fsi.pl>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Twig\Field;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Event\FieldEvent;

class TwigFieldExtension extends FieldAbstractExtension
{
    /**
     * @var ContainerInterace
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedFieldTypes()
    {
        return array('text', 'number', 'date', 'time', 'datetime', 'entity');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FieldEvents::POST_BUILD_VIEW => array('postBuildView'),
        );
    }

    public function loadOptionsConstraints(OptionsResolverInterface $optionsResolver)
    {
        $optionsResolver
            ->setDefaults(array(
                'form_wrapper_attributes' => array(),
                'anchors' => array(),
                'sort_anchors' => array(),
                'sort_ascending_anchor' => array(),
                'sort_descending_anchor' => array(),
            ))
            ->setAllowedTypes(array(
                'form_wrapper_attributes' => 'array',
                'anchors' => 'array',
                'sort_anchors' => 'array',
                'sort_ascending_anchor' => 'array',
                'sort_descending_anchor' => 'array'
            ));
    }

    private function validateAnchorOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setOptional(array('active_class'))
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

    /**
     * {@inheritdoc}
     */
    public function postBuildView(FieldEvent\ViewEventArgs $event)
    {
        $field = $event->getField();
        $view = $event->getView();

        $dataSourceName = $field->getDataSource()->getName();
        $id = $dataSourceName . '_' . $field->getName();

        $fieldOptions = array();

        $fieldOptions['form_wrapper_attributes'] = $field->getOption('form_wrapper_attributes');
        $fieldOptions['anchors'] = $this->validateAnchorOptions($field->getOption('anchors'));
        $fieldOptions['sort_anchors'] = array_merge(
            $fieldOptions['anchors'],
            $this->validateAnchorOptions($field->getOption('sort_anchors'))
        );
        $fieldOptions['sort_anchors']['attributes'] = array_merge($fieldOptions['anchors']['attributes'], $fieldOptions['sort_anchors']['attributes']);
        $fieldOptions['sort_anchors']['additional_parameters'] = array_merge($fieldOptions['anchors']['additional_parameters'], $fieldOptions['sort_anchors']['additional_parameters']);
        $fieldOptions['sort_ascending_anchor'] = array_merge(
            $fieldOptions['sort_anchors'],
            $this->validateAnchorOptions($field->getOption('sort_ascending_anchor'))
        );
        $fieldOptions['sort_ascending_anchor']['attributes'] = array_merge($fieldOptions['sort_anchors']['attributes'], $fieldOptions['sort_ascending_anchor']['attributes']);
        $fieldOptions['sort_ascending_anchor']['additional_parameters'] = array_merge($fieldOptions['sort_anchors']['additional_parameters'], $fieldOptions['sort_ascending_anchor']['additional_parameters']);
        $fieldOptions['sort_descending_anchor'] = array_merge(
            $fieldOptions['sort_anchors'],
            $this->validateAnchorOptions($field->getOption('sort_descending_anchor'))
        );
        $fieldOptions['sort_descending_anchor']['attributes'] = array_merge($fieldOptions['sort_anchors']['attributes'], $fieldOptions['sort_descending_anchor']['attributes']);
        $fieldOptions['sort_descending_anchor']['additional_parameters'] = array_merge($fieldOptions['sort_anchors']['additional_parameters'], $fieldOptions['sort_descending_anchor']['additional_parameters']);

        if (isset($fieldOptions['form_wrapper_attributes']['id'])) {
            $fieldOptions['form_wrapper_attributes']['id'] = $id . $fieldOptions['form_wrapper_attributes']['id'];
        }

        if (isset($fieldOptions['sort_ascending_anchor']['attributes']['id']))
            $fieldOptions['sort_ascending_anchor']['attributes']['id'] = $id . '_asc' . $fieldOptions['sort_ascending_anchor']['attributes']['id'];

        if (isset($fieldOptions['sort_descending_anchor']['attributes']['id']))
            $fieldOptions['sort_descending_anchor']['attributes']['id'] = $id . '_asc' . $fieldOptions['sort_descending_anchor']['attributes']['id'];

        if ($view->getAttribute('ordering_current') === 'asc') {
            $fieldOptions['sort_ascending_anchor']['attributes']['href'] = '#';
            $fieldOptions['sort_ascending_anchor']['attributes']['class'] =
                (isset($fieldOptions['sort_ascending_anchor']['attributes']['class']) ? ($fieldOptions['sort_ascending_anchor']['attributes']['class'] . ' ') : '' ) .
                $fieldOptions['sort_ascending_anchor']['active_class'];
        }

        if ($view->getAttribute('ordering_current') === 'desc') {
            $fieldOptions['sort_descending_anchor']['attributes']['href'] = '#';
            $fieldOptions['sort_descending_anchor']['attributes']['class'] =
                (isset($fieldOptions['sort_descending_anchor']['attributes']['class']) ? ($fieldOptions['sort_descending_anchor']['attributes']['class'] . ' ') : '' ) .
                $fieldOptions['sort_descending_anchor']['active_class'];
        }

        $view->setAttribute('options', $fieldOptions);
    }

    private function getCurrentRoute()
    {
        $router = $this->container->get('router');
        $request = $this->container->get('request');
        $parameters = $router->match($request->getPathInfo());
        return $parameters['_route'];
    }
}
