<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\DataSource\Extension\Symfony\Form\Field;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field\FormFieldExtension;

class FormFieldExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testFormFieldExtension()
    {
        $optionResolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $optionResolver->expects($this->once())
            ->method('setDefaults')
            ->with(array(
                'form_null_value' => 'datasource.form.choices.is_null',
                'form_not_null_value' => 'datasource.form.choices.is_not_null',
                'form_true_value' => 'datasource.form.choices.yes',
                'form_false_value' => 'datasource.form.choices.no',
                'form_translation_domain' => 'DataSourceBundle'
            ));

        $fieldType = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
        $fieldType->expects($this->atLeastOnce())
            ->method('getOptionsResolver')
            ->will($this->returnValue($optionResolver));

        $extension = new FormFieldExtension();

        $this->assertSame(
            array('text', 'number', 'date', 'time', 'datetime', 'entity', 'boolean'),
            $extension->getExtendedFieldTypes()
        );

        $extension->initOptions($fieldType);
    }
}
