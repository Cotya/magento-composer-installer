# Configuration parameters

In the `extra` section of a `composer.json` file you can add some extra configuration parameters.
Here a overview of all available parameters.

* Not all are documented yet, feel free to enhance.

## Project

- magento-root-dir : `"../relative/path"` [README.md](../README.md#install-a-module-in-your-project)
- magento-project : `{"libraryPath": "../relative/path", "libraries": {"composer/library": "../relative/path"}}`
- with-bootstrap-patch : `true|false` [Autoloading.md](Autoloading.md)

## Deploy

- magento-deploystrategy : `"copy|symlink|absoluteSymlink|link|none"` 
- magento-deploystrategy-overwrite : `{"project/name": "copy|symlink|absoluteSymlink|link|none"}` [Deploy.md](Deploy.md#overwrite-deploy-method-per-module)
- magento-map-overwrite
- magento-deploy-sort-priority
- magento-deploy-ignore
- magento-force

## Developer

- skip-suggest-repositories
- *-dev
- auto-append-gitignore
- path-mapping-translations
- include-root-package
