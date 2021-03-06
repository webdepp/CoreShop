# CoreShop Product Compare

To compare some products, the default CoreShop Theme provides already a controller and some interactivity. 

**Compare Values**

To compare real values you need to add a Hook to your Theme. In your `CoreShop\Controller\Action.php` add:

```php
 \CoreShop\Plugin::getEventManager()
       ->attach(
          'compare.products', array( '\CoreShop\Helper\ProductCompare', 'compareProductParams' )
       );
```

**Compare Helper:**

```php

<?php
namespace CoreShop\Helper;

class ProductCompare {

    public function compareProductParams( $e )
    {
        $products = $e->getParam('products');

        if (empty($products))
        {
            return false;
        }

        $values = array();

        foreach( $products as $product ) 
        {
            $params = array();

            $params[] = array(
               'name' => "name",
               'value' => "value"
            );

            $values[ $product->getId() ] = $params;

        }

        $e->stopPropagation(true);

        return $values;

    }
```

add your own logic in `scripts/coreshop/compare/list.php` if you want.