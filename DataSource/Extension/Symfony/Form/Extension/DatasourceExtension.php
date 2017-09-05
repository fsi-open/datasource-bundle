<?php

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Extension;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Type\BetweenType;
use Symfony\Component\Form\AbstractExtension;

class DatasourceExtension extends AbstractExtension
{
    protected function loadTypes()
    {
        return [new BetweenType()];
    }
}
