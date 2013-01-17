<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Lukasz Cybula <lukasz@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Twig\Driver;

use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Twig\Field\TwigFieldExtension;

class TwigDriverExtension extends DriverAbstractExtension
{
    public function getExtendedDriverTypes()
    {
        return array('doctrine');
    }

    public function loadFieldTypesExtensions()
    {
        return array(
            new TwigFieldExtension()
        );
    }
}
