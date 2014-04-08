# Text

###[Available Options](shared_options.md)

####Available Comparison Types:
* eq
* neq
* in
* notIn,
* contains


### Usage example

```php
$datasource->addField('title','text','contains')
```

Result as DQL:
```dql
SELECT n FROM FSiDemoBundle:News n WHERE n.title LIKE :title
```
