# Boolean

<table>
    <head>
        <tr>
            <td><b>Option source</b></td>
            <td><b>Option name</b></td>
            <td><b>Value type</b></td>
            <td><b>Default Value</b></td>
        </tr>
    </head>
    <tbody>

        <tr>
            <td>Doctrine Field Options</td>
            <td>
                <ul>
                    <li>auto_alias</li>
                    <li>clause</li>
                    <li>field (optional) </li>

                </ul>
            </td>
            <td>
                <ul>
                    <li>bool</li>
                    <li>where|having</li>
                    <li>string|null</li>
                </ul>
            </td>
            <td>
                <ul>
                    <li><code>true</code></li>
                    <li><code>where</code></li>
                    <li><code></code></li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>Field Extension Options</td>
            <td>
                <ul>
                    <li>default_sort</li>
                    <li>sortable</li>
                    <li>default_sort_priority (optional)</li>
                </ul>
            </td>
            <td>
                <ul>
                    <li>null|asc|desc</li>
                    <li>bool</li>
                    <li>integer</li>


                </ul>
            </td>
            <td>
                <ul>
                    <li><code>null</code></li>
                    <li><code>true</code></li>
                    <li><code></code></li>

                </ul>
            </td>
        </tr>
        <tr>
            <td>Form Extension</td>
            <td>
                <ul>
                    <li>form_filter</li>
                    <li>form_options</li>
                    <li>form_from_options</li>
                    <li>form_to_options</li>
                    <li>form_type (optional) </li>
                    <li>form_order (optional) </li>

                </ul>
            </td>
            <td>
                <ul>
                    <li>bool</li>
                    <li>array</li>
                    <li>array</li>
                    <li>array</li>
                    <li>integer</li>
                    <li>string</li>
                </td>
            </td>
            <td>
                <ul>
                    <li><code>true</code></li>
                    <li><code>array()</code></li>
                    <li><code>array()</code></li>
                    <li><code>array()</code></li>
                    <li><code></code></li>
                    <li><code></code></li>
                </ul>
            </td>
        </tr>
    </tbody>
</table>

### Usage example

Input class

```php
class User
{
    /* @var \Boolean */
    public $active;


}
```
======
#### Example 1

**Column Configuration**
```php
$datasource->addField('active', 'boolean', 'eq');
```

**Input**
```php
$user = new User();
$user->active = false;
```

**Output**
> false

======
#### Example 2

**Column Configuration**
```php
$datasource->addField('active', 'boolean', array(
    'auto_alias' => true,
    'default_sort' => 'desc',
    'clause' => 'where'
));
```

**Input**
```php
$user = new User();
$user->active = true;
```

**Output**
> true
