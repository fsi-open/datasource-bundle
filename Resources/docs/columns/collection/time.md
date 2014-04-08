# Time

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
$datasource->addField('expirationTime','time','gt')
```

Result as DQL:
```dql
SELECT n FROM FSiDemoBundle:News n WHERE n.expirationTime > :expirationTime
```
