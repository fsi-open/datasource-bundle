# This file contains changes in the 2.x branch

## Deleted the generic doctrine driver option

It was renamed to `doctrine-orm` to better fit it's functionality.

## Rewritten DependencyInjectionExtension and DriverExtension

These extensions have been completely rewritten and now accept real services in 
constructors instead of just their IDs.

## Autoconfiguration of DataSource extensions

All services implementing the following interfaces are now automatically tagged
with corresponding tags:

`FSi\Component\DataSource\Driver\DriverFactoryInterface` - `'datasource.driver.factory'`
`FSi\Component\DataSource\Driver\DriverExtensionInterface` - `'datasource.driver.extension'`
`FSi\Component\DataSource\Driver\Collection\CollectionAbstractField` - `'datasource.driver.collection.field'`
`FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Collection\FieldEventSubscriberInterface` - `'datasource.driver.collection.field.subscriber'`
`FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Collection\EventSubscriberInterface` - `'datasource.driver.collection.subscriber'`
`FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField` - `'datasource.driver.doctrine.field'` (*deprecated*)
`FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\FieldEventSubscriberInterface` - `'datasource.driver.doctrine.field.subscriber'` (*deprecated*)
`FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\EventSubscriberInterface` - `'datasource.driver.doctrine.subscriber'` (*deprecated*)
`FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineAbstractField` - `'datasource.driver.doctrine-orm.field'`
`FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\ORM\FieldEventSubscriberInterface` - `'datasource.driver.doctrine-orm.field.subscriber'`
`FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\ORM\EventSubscriberInterface` - `'datasource.driver.doctrine-orm.subscriber'`
`FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField` - `'datasource.driver.doctrine-dbal.field'`
`FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\DBAL\FieldEventSubscriberInterface` - `'datasource.driver.doctrine-dbal.field.subscriber'`
`FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\DBAL\EventSubscriberInterface` - `'datasource.driver.doctrine-dbal.subscriber'`

## Dropped support for PHP below 7.1

To be able to fully utilize new functionality introduced in 7.1, we have decided
to only support PHP versions equal or higher to it. All bundle's classes and interfaces
utilize new php 7.1 features like scalar typehints, return typehints and nullable types.
