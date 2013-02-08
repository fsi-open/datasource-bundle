# FSi DataSource Bundle #

Main purpose of this bundle is to register FSi DataSource Component service 
and twig rendering functions. 

## Installation ##

* Download DataSourceBundle
* Enable the bundle
* Configure the DataSourceBundle in config.yaml 

### Step1: Download DataSourceBundle ###

Add to composer.json 

```

"require": {
    "fsi/datasource-bundle": "0.9.*"
}

```

### Step2: Enable the bundle ###

```
    
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new FSi\Bundle\DataSourceBundle\DataSourceBundle(),
    );
}

```

### Step3: Configure the DataSourceBundle in config.yaml ###

Add to config.yaml 

```
    
fsi_data_source: 
    twig: ~

```

## Usage ##

### Create DataSource in Controller ###

Basic DataSource usage.

```

<?php

namespace FSi\Bundle\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="demo_index")
     * @Template()
     */
    public function indexAction()
    {
        $driverFactory = $this->get('datasource.driver.doctrine.factory');
        $dataSource = $driverFactory->createDataSource('FSiSiteBundle:News');

        $datasource
            ->addField('title', 'text', 'like')
            ->addField('author', 'text', 'like')
            ->addField('id', 'number', 'eq', array(
                'ordering' => 'desc',
                'form_disabled' => true
            ))
            ->addField('createdate', 'datetime', 'between', array(
                'form_options' => array(
                    array(
                        'date_widget' => 'single_text',
                        'time_widget' => 'single_text',
                    )
                )
            ))
            ->addField('category', 'entity', 'in', array(
                'form_options' => array(
                    'class' => 'FSi\Bundle\SiteBundle\Entity\Category',
                ),
                'ordering_disabled' => true,
            ))
        ;

        $dataSource->bindParameters($this->getRequest());

        return array(
            'datasource' => $dataSource->createView(),
            'data' => $dataSource->getResult()
        );
    }
}

```

How to display created datasource in twig template: 

```

{# src/FSi/Bundle/DemoBundle/Resources/views/Default/index.html.twig #}

<form action="{{ path('demo_index') }}" method="post">
    {{ datasource_filter_widget(datasource) }}
</form>

{# display the data #}

<div class="pagination">
    {{ datasource_pagination_widget(datasource, {max_pages: 10, active_class: 'active', disabled_class: 'disabled' }) }}
</div>

```

## Registered Twig functions ##

This bundle registers a few new widgets' functions available in twig templates. These are:

### datasource_filter_widget ###

It renders datasource filter using ``datasource_filter`` twig block which could be overloaded in user bundle.

* ``view`` **(required)** - Instance of DataSourceViewInterface to render using ``datasource_field_widget``.
* ``vars`` **(optional)** - Array of additional variables passed to twig block's rendering context.

### datasource_field_widget ###

It renders form fields for only one field using ``datasource_field`` twig block  which could be overloaded in user bundle.

* ``fieldView`` **(required)** - Instance of FieldViewInterface to render (it's form) using ``form_label``, ``form_errors`` and ``form_widget``.
* ``vars`` **(optional)** - Array of additional variables passed to twig block's rendering context.

### datasource_sort_widget ###

It renders anchors for sorting datasource's result by specified field.

* ``fieldView`` **(required)** - Instance of FieldViewInterface to render anchors for.
* ``options`` **(optional)** - Array of one or more of the following options:
    * ``route`` - Route's name, if empty currently active route will be used.
    * ``additional_parameters`` - Array of additional parameters to be merged with parameters from datasource.
    * ``ascending`` - content of the ascending anchor, default: ``'&uarr;'``.
    * ``descending`` - content of the descending anchor, default: ``'&darr;'``.
* ``vars`` **(optional)** - Array of additional variables passed to twig block's rendering context.

### datasource_pagination_widget ###

This function renders pagination control for specified datasource using ``datasource_pagination`` twig block which could
be overloaded in user bundle. It can take following options:

* ``view`` (**required**) - Instance of DataSourceViewInterface to render paginatior widget for.
* ``options`` (**optional**) - Array of one or more of the following options.
    * ``max_pages`` - Maximum number of pages that can be rendered at once not including first, previous, next and last pages
    * ``wrapper_attributes`` - Array of attributes that will be added to the ``<ul>`` tag wrapping the pagination widget
    * ``route`` - Route which will be used to generate all URLs. If not specified then currently active route will be used
    * ``additional_parameters`` - Array of parameters that will be merged with parameters generated from datasource
    * ``active_class`` - Class attribute that will be added to the ``<li>`` wrapper tag of currently active page anchor
    * ``disabled_class`` - Class attribute that will be added to the ``<li>`` wrapper tag of disabled page anchors (i.e first,
      previous, next or last page anchors)
    * ``translation_domain`` - Translation domain which will be used to translate anchors' contents of first, previous, next
      and last page anchors

## Additional Field Options ##

There are several additional field options added by DataSourceBundle.

* ``filter_wrapper_attributes`` - array of attributes added to the wrapper tag for this filter field

Example usage: 

```
<?php

    $dataSource->add("title", "text", "like", array(
        "filter_wrapper_attributes" => array(
            "class" => "div",
            "id" => "wrapper"
        ),
    );

```

## Theming DataSource ##

Default DataSource blocks used to render each parts of DataSource are very simple, so in many cases you will need to overwrite
them. This can be easily done with theming mechanism. Theme is nothing else than a twig template that contains blocks with
specific names.

### Basic Themes ###

If you want to set theme for your DataSourceView Object you need to use special tag ``datasource_theme``.  

example: 

```
{% block body %}
    {% datasource_theme datasource_view 'FSiDemoBundle::datasource.html.twig' %}

    {{ datasource_filter_widget(datasource_view) }}
{% endblock %}
    
```
    
Now in file ``FSiDemoBundle::datasource.html.twig`` you can create block ``datasource_filter`` and ``datasource_filter_widget``
will use it to render DataSourceView.  
**Heads Up!!** You can also pass any kind of resources into theme. All you need to do is to pass them in array 

```
{% datasource_theme datasource_view 'FSiDemoBundle::datasource.html.twig' with {'dg' : datagrid} %}
```

And then ``datagrid`` object will be available in all blocks from theme under ``vars.dg``.  

You can also use ``_self`` theme location instead of standalone file.

```
{% datasource_theme datasource_view _self with {'dg' : datagrid} %}
```
 

```
{# FSiDemoBundle::datagrid.html.twig #}

{% block datasource_filter %}
    {% for field in datasource.fields %}
        <div id="{{ datasource.name }}_{{ field.name }}_wrapper" class="filter_wrapper">
            {{ datasource_field_widget(field, vars) }}
        </div>
    {% endfor %}
{% endblock %}
```

But what if you have to render two different DataSourceView objects, one in admin panel ane second one in user part? 
You can simply create two different themes and use them depending on situation, but there is also another way.  
There is a way to create block for specific datasource filter, pagination or field.  

Only thing you need to do is to create block name in proper naming convention. 

For ``datasource_filter_widget`` you can use two patterns: 
* datasource_{source_name}_filter
* datasource_filter

Example: 

```
{# FSiDemoBundle::datasource.html.twig #}

{% block datasource_admin_filter %}
    {% for field in datasource.fields %}
        <div id="{{ datasource.name }}_{{ field.name }}_wrapper" class="admin_filter_wrapper">
            {{ datasource_field_widget(field, vars) }}
        </div>
    {% endfor %}
{% endblock %}

{% block datasource_orders_filter %}
    {% for field in datasource.fields %}
        <div id="{{ datasource.name }}_{{ field.name }}_wrapper" class="frontend_filter_wrapper">
            {{ datasource_field_widget(field, vars) }}
        </div>
    {% endfor %}
{% endblock %}
```

### Blocks naming conventions for widgets ###

``datasource_filter_widget``
* datasource_{source_name}_filter
* datasource_filter

``datasource_field_widget``
* datasource_{source_name}_field_name_{field_name}
* datasource_{source_name}_field_type_{field_type}
* datasource_field_name_{field_name}
* datasource_field_type_{field_type}
* datasource_{source_name}_field
* datasource_field

``datasource_sort_widget``
* datasource_{source_name}_sort
* datasource_sort

``datasource_pagination_widget``
* datasource_{source_name}_pagination
* datasource_pagination

As you can see there are many ways to overwrite default block even for specific field in specific datasource.
