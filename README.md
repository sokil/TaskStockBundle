# TaskStockBundle

Task tracker bundle

* [Installation](#installation)
* [Configuration](#configuration)
    * [Basic bundle configuration](#basic-bundle-configuration)
    * [Configuring SPA](#configuring-spa)
    * [Configuring file storage](#configuring-file-storage)
    * [Configuring task states](#configuring-task-states)

## Installation

Add dependency to composer:
```
composer.phar reequire sokil/task-stock-bundle
```

## Configuration

This bundle depends from other bundles, which also require configuration. If you yet not using it, configure them:
* [FrontendBundle](https://github.com/sokil/FrontendBundle/blob/master/README.md#installation)
* [FileStorageBundle](https://github.com/sokil/FileStorageBundle/blob/master/README.md#installation)
* [NotificationBundle](https://github.com/sokil/NotificationBundle/blob/master/README.md#installation)
* [UserBundle](https://github.com/sokil/UserBundle/blob/master/README.md#installation)

### Basic bundle configuration

1) Add bundle to AppKernel:
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

2) Define required parameters in `./app/config/parameters.yml.dist`:
```yaml
# email server parameters
notification.from_email.address: ~
notification.from_email.sender_name: ~
```

3) Add roles to role hierarchy in file `./app/config/security.yml`:
```yaml
security:
    role_hierarchy:
        ROLE_TASK_VIEWER:           [ROLE_USER]
        ROLE_TASK_MANAGER:          [ROLE_TASK_VIEWER]
        ROLE_TASK_PROJECT_VIEWER:   [ROLE_USER]
        ROLE_TASK_PROJECT_MANAGER:  [ROLE_TASK_PROJECT_VIEWER]
```

4) Register routing in `./app/console/routing.yml`:
```yaml
task_stock:
    resource: "@TaskStockBundle/Resources/config/routing.yml"
```

5) Bundle uses assetic so you need to register it in assetic config and do some configuration:
```yaml
assetic:
    bundles:
        - TaskStockBundle
    variables:
        locale: [uk, en, de. fr]
        env: [dev,prod]
```

Paramater `varailbes` passes some valiables to assets, tham will be used to build path to assets.

### Configuring SPA

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
        // requirejs
        options.requireJs = [
            TaskStockRequireJsConfig
        ];
        // start app
        window.app = new Application(options);
        window.app.start();
    })();
</script>
```

### Configuring file storage

This bundle uses [FileStorageBundle](https://github.com/sokil/FileStorageBundle) to handle file uploads.

You need to configure some filesystem to handle uploads, for example `task_stock.attachments`. 
Add to your `./app/config/config.yml`:

```yaml
knp_gaufrette:
    factories:
        - "%kernel.root_dir%/../vendor/sokil/file-storage-bundle/src/Resources/config/adapter_factories.xml"
    adapters:
        task_stock.attachments:
            internal:
                pathStrategy:
                    name: chunkpath
                    options:
                        chunksNumber: 2
                        chunkSize: 3
                        preserveExtension: false
                        baseDir: "%kernel.root_dir%/files/task_attach"
    filesystems:
        task_stock.attachments:
            adapter: task_stock.attachments
```

Then add this filesystem to bundle's config at `./app/config/config.yml`:

```yaml
task_stock:
   attachments_filesystem: "task_stock.attachments"
```

### Configuring task states

Task belongs to some category: `Bug`, `Enhancement`, `Feature`, `Design`, etc. Different categories of tasks may have different state flows. Bug may have states `New`, `In Progres`, `Test`, `Resolved`, and `Design` may have states `New`, `Prototyping`, `Drawing`, `Markup`, `Resolved`. This groups of states called `Schema`. Task with category `Design` may be related to `Drawing schema`, and tasks `Bug`, `Feature` may be relared to `Development` schema.

Add schema configuration to your `./app/config/config.yaml`:

```yaml
taskStock:
   stateConfig:
      - id: 0
        name: Development
        states:
          new:
            label: task_status_new
            initial: true
            transitions:
              to_in_progress:
                resultingState: in_progress
                label: task_transiotion_open
                icon: glyphicon glyphicon-play
              to_rejected:
                resultingState: rejected
                label: task_transiotion_reject
                icon: glyphicon glyphicon-ban-circle
          in_progress:
            label: task_status_in_progress
            transitions:
              to_resolved:
                resultingState: resolved
                   label: task_transiotion_resolve
                   icon: glyphicon glyphicon-ok
                 to_rejected:
                   resultingState: rejected
                   label: task_transiotion_reject
                   icon: glyphicon glyphicon-ban-circle
             rejected:
               label: task_status_rejected
               transitions:
                 to_in_progress:
                   resultingState: in_progress
                   label: task_transiotion_reopen
                   icon: glyphicon glyphicon-repeat
             resolved:
               label: task_status_resolved
               transitions:
                 to_in_progress:
                   resultingState: in_progress
                   label: task_transiotion_reopen
                   icon: glyphicon glyphicon-repeat
```
