# Date

###[Available Options](shared_options.md)

####Available Comparison Types:
* eq
* neq
* lt
* lte
* gt
* gte
* in
* nin (not in),
* between

### Usage example

```php
$datasource->addField('createdAt','date','lte',array(
                       'auto_alias' => true,
                       'clause' => 'where',
                       'field' => 'updatedAt'
                   ))
```

Result as DQL:
```dql
SELECT e FROM FSiDemoBundle:News e WHERE e.updatedAt <= :createdAt
```

