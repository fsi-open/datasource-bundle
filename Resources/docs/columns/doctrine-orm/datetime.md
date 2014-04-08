# DateTime

###[Available Options](shared_options.md)

####Available Comparison Types:
* eq
* neq
* lt
* lte
* gt
* gte
* in
* notIn,
* between
* isNull

### Usage example

```php
$datasource->addField('deletedAt','datetime','lte')
```

Result as DQL:
```dql
SELECT n FROM FSiDemoBundle:News n WHERE n.deletedAt <= :deletedAt
```

