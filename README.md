# FSi DataSource Bundle #

Main purpose of this bundle is to register ``FSi DataSource Component`` service 
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
        $driver = $driverFactory->createDriver('FSiSiteBundle:News');
        $dataSource = $this->get('datasource.factory')->createDataSource($driver);

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
    {{ datasource_pagination_widget(datasource, {max_pages: 10, page_anchors: {active_class: 'active'} }) }}
</div>

```

## Additional Field Options ##

There are several additional field options added by DataSourceBundle.

* ``filter_wrapper_attributes`` - array of attributes added to the wrapper tag for this filter field
* ``sort_anchors`` - options affecting all sorting anchors for this field
    * ``active_class`` - class added to the class attribute when current anchor is active
    * ``route`` - route for generating anchor URLs, default: current route extracted from ``router`` service
    * ``additional_parameters`` - additional parameters merged with datasource parameters during URL generation
    * ``attributes`` - array of attributes that are added to the anchor tag
    * ``content`` - content of an anchor, default: ``''``
* ``sort_ascending_anchor`` - options affecting only anchor for sorting ascending wich will overwrite those from ``sort_anchors``
* ``sort_descending_anchor`` - options affecting only anchor for sorting descending wich will overwrite those from ``sort_anchors``

Example usage: 

```
<?php

    $dataSource->add("title", "text", "like", array(
        "form_wrapper_attributes" => array(
            "class" => "div",
            "id" => "wrapper"
        ),
        "sort_anchors" => array(
            "active_class" => "active"
        ),
        "sort_ascending_anchor" => array(
            "content" => "&uarr;"
        ),
        "sort_descending_anchor" => array(
            "content" => "&darr;"
        )
    );

```


