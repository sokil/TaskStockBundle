# TaskStockBundle

Task tracker bundle

## Installation

Add dependency to composer:
```
composer.phar reequire sokil/task-stock-bundle
```

Add bundle to AppKernel:
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

Define required parameters in `./app/config/parameters.yml.dist`:
```yaml
# email server parameters
notification.from_email.address: some@email.address
notification.from_email.sender_name: Some sender name
```

Add roles to role hierarchy in file `./app/config/security.yml`:
```yaml
security:
    role_hierarchy:
        ROLE_TASK_VIEWER:           [ROLE_USER]
        ROLE_TASK_MANAGER:          [ROLE_TASK_VIEWER]
        ROLE_TASK_PROJECT_VIEWER:   [ROLE_USER]
        ROLE_TASK_PROJECT_MANAGER:  [ROLE_TASK_PROJECT_VIEWER]
```

This bundle also depends from other bundles, which also require configuration. If you yet not using it, configure them:
* [NotificationBundle](https://github.com/sokil/NotificationBundle/blob/master/README.md#installation)

## Configuring task states
