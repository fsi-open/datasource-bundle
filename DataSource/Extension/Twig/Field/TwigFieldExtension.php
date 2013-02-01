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
                'filter_wrapper_attributes' => array()
            ))
            ->setAllowedTypes(array(
                'filter_wrapper_attributes' => 'array'
            ));
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

        $filter_wrapper_attributes = $field->getOption('filter_wrapper_attributes');
        if (isset($filter_wrapper_attributes['id'])) {
            $filter_wrapper_attributes['id'] = $id . $filter_wrapper_attributes['id'];
        }

        $view->setAttribute('filter_wrapper_attributes', $filter_wrapper_attributes);
    }
}
