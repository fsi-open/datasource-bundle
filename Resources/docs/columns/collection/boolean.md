# Boolean

###[Available Options](shared_options.md)

####Available Comparison Types:
* eq

### Usage example

```php
$datasource->addField('visible', 'boolean', 'eq')
```

Result as DQL:
```dql
SELECT n FROM FSiDemoBundle:News n WHERE n.visible = :visible
```
