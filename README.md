# TaskStockBundle

Task tracker bundle

## Installation

Add dependency to composer:
```
composer.phar reequire sokil/task-stock-bundle
```

Add dundle to AppKernel:
```php
<?php

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Sokil\TaskStockBundle\TaskStockBundle(),
        );
    }
}

```
