# Configuration parameters

In the `extra` section of a `composer.json` file you can add some extra configuration parameters.
Here a overview of all available parameters.

* Not all are documented yet, feel free to enhance.

## Project

- magento-root-dir : `"../relative/path"` [README](../README.md#install-a-module-in-your-project)
- magento-project : `{"libraryPath": "../relative/path", "libraries": {"vendor/package": "../relative/path", ...}}`
- with-bootstrap-patch : `true|false` [Autoloading](Autoloading.md)
- magento-map-overwrite : `{"vendor/package": {"virtual/path/file.php": "real/path/file.php", ...}, ...}` [Mapping](Mapping.md)
  - package-xml : `"path/to/package.xml"` [Mapping Package XML](Mapping.md#mapping-with-packagexml)

## Deploy

- magento-deploystrategy : `"copy|symlink|absoluteSymlink|link|none"` [Deploy strategy](Deploy.md)
- magento-deploystrategy-overwrite : `{"vendor/package": "copy|symlink|absoluteSymlink|link|none", ...}` [Deploy.md](Deploy.md#overwrite-deploy-method-per-module)
- magento-deploy-sort-priority : `{"vendor/package": 200, ...}` (Deploy.md)[Deploy.md#define-order-in-which-you-want-your-magento-packages-deployed]
- magento-deploy-ignore : `{"vendor/package": ["file/to/exclude.php"], ...}` [Deploy.md](Deploy.md#prevent-single-files-from-deploy)
- magento-force : `true|false` [Deploy.md](Deploy.md#define-order-in-which-you-want-your-magento-packages-deployed)

## Developer

- skip-suggest-repositories
- *-dev
- auto-append-gitignore
- path-mapping-translations
- include-root-package
