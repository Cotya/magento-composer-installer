Mapping
=======

### Mapping in general

There are several ways how the mapping from files in the package into the Magento source is accomplished:

1. A mapping in the composer.json
2. MagentoConnect package.xml file
3. [modman](https://github.com/colinmollenhour/modman) file

As long as one of these mappings can be found, Magento modules are installable.

The package files are symlinked into the Magento instance by default. You can also use a copy or hardlink deploy strategy
by configuring it in the root composer.json (see below).

A repository of composer ready Magento modules can be found on http://packages.firegento.com/

The Magento root directory must be specified in the ```composer.json``` under ```extra.magento-root-dir```.

**NOTE:** modman's include and bash feature will never get supported!

### Mapping with composer.json
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

`magento-map-overwrite` parameter can be used to override module default mapping. For example, module default mapping is: `app/code/community/CompanyDir/ModuleDir/*` => `app/code/community/CompanyDir/ModuleDir/`, but you would like to have it as `app/code/community/CompanyDir/ModuleDir/*` => `app/code/local/CompanyDir/ModuleDir`.
So sample `module` is provided by `company` with version `*`.

Here is the entry for composer.json:
```
{
   "require": {
      ...
      "company/module": "*"
   },
   "repositories": [
      ...
   ],
   "extra": {
      ...
      "magento-map-overwrite": {
         "company/module": [
            ["app/code/community/CompanyDir/ModuleDir/*", "app/code/local/CompanyDir/ModuleDir"]
         ]
      }
   }
}
```

so `company/module` is an array of mapping entries - arrays where first key is source path and second key is destination path.

### Mapping with package.xml
If you wish to convert an existing Magento Connect repository with a minimum amount of effort, you use the existing package.xml. To enable that, simply specify `"extra": { "package-xml": "package.xml" }` in your composer.json. For example:

```json
{
   "name": "test/test",
   "type": "magento-module",
   "extra": {
		"package-xml": "package.xml"
	}
}
```
