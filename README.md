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

Register routing in `./app/console/routing.yml`:
```yaml
task_stock:
    resource: "@TaskStockBundle/Resources/config/routing.yml"
```

Bundle uses assetic so you need to register it in assetic config and do some configuration:
```yaml
assetic:
    bundles:
        - TaskStockBundle
    variables:
        locale: [uk, en, de. fr]
        env: [dev,prod]
```

Paramater `varailbes` passes some valiables to assets, tham will be used to build path to assets.

This bundle uses [FrontendBundle](https://github.com/sokil/FrontendBundle) for building frontend, so configure SPA, and add some dependencies to it:
```twig
{% import "@FrontendBundle/Resources/views/macro.html.twig" as frontend %}
{% import "@TaskStockBundle/Resources/views/Spa/macro.html.twig" as taskSpa %}

{{ frontend.commonCssResources() }}
{{ frontend.commonJsResources() }}
{{ frontend.spaJsResources() }}

{{ taskSpa.cssResources() }}
{{ taskSpa.jsResources() }}

<script type="text/javascript">
    (function() {
        // app options
        var options = {{ applicationData|json_encode|raw }};
        // router
        options.router = new Marionette.AppRouter();
        var taskStockRouter = new TaskStockRouter();
        options.router.processAppRoutes(taskStockRouter, taskStockRouter.routes);
        // container
        options.container = new Container(_.extend(
                {},
                TaskStockServiceDefinition
        ));
        // start app
        window.app = new Application(options);
        window.app.start();
    })();
</script>
```

This bundle also depends from other bundles, which also require configuration. If you yet not using it, configure them:
* [FrontendBundle](https://github.com/sokil/FrontendBundle/blob/master/README.md#installation)
* [NotificationBundle](https://github.com/sokil/NotificationBundle/blob/master/README.md#installation)
* [UserBundle](https://github.com/sokil/UserBundle/blob/master/README.md#installation)
* [FileStorageBundle](https://github.com/sokil/FileStorageBundle/blob/master/README.md#installation)

## Configuring task states
