### Adding Composer's autoloading ability to Magento

This installer can add Composer's autoloader to Magento's own autoloader 
stack, editing the "native" 
`app/Mage.php` file on Composer install/update. This way, you can use 
Composer-compatible [third parties](https://packagist.org/) in Magento modules.

#### What is patched?
After some consideration, it was decided that the most reliable place to push 
Composer's autoloader into Magento was `app/Mage.php`, effectively _changing_ it, 
including the necessary file just before the `Mage` class declaration, and after 
the registering of `Varien_Autoloader`. This _guarantees_ that access to third party 
packages is available in the web app, API calls, crons, FPC cached requests and shell scripts.       
The "first Magento event dispatch" strategy was also considered, but dismissed as not so reliable.  

The change is safe and minimal and it's more or less equivalent to
`require 'vendor/autoloader.php'`, with the required path being relative to Mage's root.

#### When is the patching applied?
The `app/Mage.php` file will be changed on Composer `install` or `update`. Or by calling `composer run-script <EVENT> -- --redeploy`, where `<EVENT>` can be either `post-install-cmd` or `post-update-cmd`.

The patching process is idempotent, meaning that multiple applies do not result in
multiple changes, as the patch is checked for its existence before it's applied.
It's safe to run it multiple times: the change will be made _once_.

#### Stack of autoloaders
Composer, by default, _prepends_ its autoloader handler to the existing stack which, 
in Magento's case, consists only of `Varien_Autoload::autoload()` 
(registered in `Varien_Autoload::register()`). Composer's autoloader **must** come
first, because `Varien_Autoload::autoload()`'s implementations actually ends up
doing a `return include $classFile;` _**without** checking if the file exists_, in which
case PHP would trigger a warning and this goes against [best practices](https://github.com/php-fig/fig-standards/blob/812b66522d9b6928a76f722de24531c2f6f044bd/accepted/PSR-4-autoloader-meta.md#41-chosen-approach). So make sure Composer's `prepend-autoloader`
[config](https://getcomposer.org/doc/04-schema.md#config) is set to `true`.

#### Configuration
The main "switch" setting is `with-bootstrap-patch`, in `extra`.
This is boolean and can control if this plugin will apply the patch or not.
By default, this is `true`. If `false`, this entire process is skipped.

The `magento-root-dir` extra config is used to resolve the path to `app/Mage.php` and must be set.

Vendor folder **name** changes (e.g via `COMPOSER_VENDOR_DIR` env or `vendor-dir` config) are supported.  
Do not confuse this with different vendor folder **paths**: only the folder _name_ is extracted from
 the path, and not the whole path. The vendor folder will still be assumed to be a sibling of Mage root
 or its child, so setting the vendor folder to `~/my_vendor` will not work as expected.

#### Valid project folder layout
Note that the `vendor` folder is assumed either as a _sibling_ of Mage root or as its _child_.
Hence the following folder layouts are supported:
```
├── mage_root
│   └── app
│       └── Mage.php
└── vendor
```
and
```
├── app
│   └── Mage.php
└── vendor
```
It's recommended to use the first layout with Mage root set as _web_ root, 
so no access to other files except Magento's is granted. This also keeps Mage root clean. 
