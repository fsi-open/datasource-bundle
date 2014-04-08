# Entity

###[Available Options](shared_options.md)

####Available Comparison Types:

* eq
* memberof
* in
* isNull

### Usage example

```php
$datasource->addField('user','entity','in')
```

Result as DQL:
```dql
SELECT g FROM FSiDemoBundle:Group g WHERE g.user IN(:user)
```
