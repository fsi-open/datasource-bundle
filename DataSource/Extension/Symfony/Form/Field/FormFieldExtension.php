<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field;

use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldAbstractExtension;

class FormFieldExtension extends FieldAbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedFieldTypes()
    {
        return array('text', 'number', 'date', 'time', 'datetime', 'entity', 'boolean');
    }

    /**
     * {@inheritdoc}
     */
    public function initOptions(FieldTypeInterface $field)
    {
        $field->getOptionsResolver()
            ->setDefaults(array(
                'form_null_value' => 'datasource.form.choices.is_null',
                'form_not_null_value' => 'datasource.form.choices.is_not_null',
                'form_true_value' => 'datasource.form.choices.yes',
                'form_false_value' => 'datasource.form.choices.no',
                'form_translation_domain' => 'DataSourceBundle'
            ));
    }
}
