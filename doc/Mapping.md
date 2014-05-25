Mapping
=======

### Mapping in general

There are several ways how the mapping from files in the package into the Magento source is accomplished:

1. [modman](https://github.com/colinmollenhour/modman) file
2. MagentoConnect package.xml file
3. A mapping in the composer.json

As long as one of these mappings can be found, Magento modules are installable.

The package files are symlinked into the Magento instance by default. You can also use a copy or hardlink deploy strategy
by configuring it in the root composer.json (see below).

A repository of composer ready Magento modules can be found on http://packages.firegento.com/

The Magento root directory must be specified in the ```composer.json``` under ```extra.magento-root-dir```.

**NOTE:** modman's include and bash feature will never get supported!



### Mapping per JSON
If you don't like modman files, you can define mappings in a package composer.json file instead.

```json
{
   "name": "test/test",
   "type": "magento-module",
    "extra": {
        "map": [
            ["themes/default/skin", "public/skin/frontend/foo/default"],
            ["themes/default/design", "public/app/design/frontend/foo/default"],
            ["modules/My_Module/My_Module.xml", "public/app/etc/modules/My_Module.xml"],
            ["modules/My_Module/code", "public/app/code/local/My/Module"],
            ["modules/My_Module/frontend/layout/mymodule.xml", "public/app/design/frontend/base/default/layout/mymodule.xml"]
        ]
    }
}
```


