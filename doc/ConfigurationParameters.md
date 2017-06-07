# Configuration parameters

In the `extra` section of a `composer.json` file you can add some extra configuration parameters.
Here a overview of all available parameters.

## Project

- magento-root-dir : `"../relative/path"` [README - root dir](../README.md#install-a-module-in-your-project)
- magento-project : `{"libraryPath": "../relative/path", "libraries": {"vendor/package": "../relative/path", ...}}`
- with-bootstrap-patch : `true|false` [Autoloading](Autoloading.md)
- magento-map-overwrite : `{"vendor/package": {"virtual/path/file.php": "real/path/file.php", ...}, ...}` [Mapping](Mapping.md)
  - package-xml : `"path/to/package.xml"` [Mapping Package XML](Mapping.md#mapping-with-packagexml)

## Deploy

- magento-deploystrategy : `"copy|symlink|absoluteSymlink|link|none"` [Deploy strategy](Deploy.md)
- magento-deploystrategy-overwrite : `{"vendor/package": "copy|symlink|absoluteSymlink|link|none", ...}` [Deploy overwrite](Deploy.md#overwrite-deploy-method-per-module)
- magento-deploy-sort-priority : `{"vendor/package": 200, ...}` (Deploy sort priority)[Deploy.md#define-order-in-which-you-want-your-magento-packages-deployed]
- magento-deploy-ignore : `{"vendor/package": ["file/to/exclude.php"], ...}` [Deploy ignore files](Deploy.md#prevent-single-files-from-deploy)
- magento-force : `true|false` [Deploy force overwrite](Deploy.md#define-order-in-which-you-want-your-magento-packages-deployed)
- path-mapping-translations : `["old/path/file.txt": "path/new/file.txt"]`

## Developer

- skip-suggest-repositories : `true|false` [FAQ - Disable suggestions](FAQ.md#can-i-disable-repository-suggestions)
- auto-append-gitignore : `true|false` [README - gitignore](../README.md#auto-add-files-to-gitignore)
- include-root-package : `true|false` [README - root pacakge](../README.md#include-your-project-in-deployment)
- *-dev : `` [Deveveloper Mode](DevMode.md)
