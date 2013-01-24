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

``datasource_filter_widget(DataSourceViewInterface $view, array $exclude = array(), array $vars = array())``

* ``$view`` - Datasource's view to render` using ``datasource_field_widget``.
* ``$exclude`` - Array of names of fields that should be omitted during rendering. It could be usefull when some fields should be
  rendered in completely different place than this function is called from.
* ``$vars`` - Array of additional variables passed to twig block rendering context.

It renders datasource filter using ``datasource_filter`` twig block which could be overloaded in user bundle.

``datasource_field_widget(FieldViewInterface $fieldView, array $vars = array())``

* ``$fieldView`` - Field to render (it's form) using ``form_label``, ``form_errors`` and ``form_widget``.
* ``$vars`` - Array of additional variables passed to twig block rendering context.

It renders form fields for only one field using ``datasource_field`` twig block  which could be overloaded in user bundle.

``datasource_sort_asc_url(FieldViewInterface $fieldView, $route = null, array $additionalParameters = array())``

This function returns URL which will sort datasource's results first by specified field in ascending order. You can optionally
pass ``$additionalParameters`` to be merged with parameters from datasource. If ``$route`` is not specified then currently active
route will be used.

``datasource_sort_desc_url(FieldViewInterface $fieldView, $route = null, array $additionalParameters = array())``

This function returns URL which will sort datasource's results first by specified field in descending order. You can optionally
pass ``$additionalParameters`` to be merged with parameters from datasource. If ``$route`` is not specified then currently active
route will be used.

``datasource_pagination_widget(DataSourceViewInterface $view, $options = array())``

This function renders pagination control for specified datasource using ``datasource_pagination`` twig block which could
be overloaded in user bundle. It can take following options:

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


