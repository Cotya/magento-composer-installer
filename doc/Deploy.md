
### Deploy per Copy instead of Symlink

There is a deploy per copy strategy. This can only be configured in the root composer.json, it can't be configured on a per-package level.
Here is how to use it:

```json
{
    ...
    "extra":{
        "magento-root-dir": "htdocs/",
        "magento-deploystrategy": "copy"
    }
    ...
}
```

### overwrite deploy method per module

Caution: this feature is new, so doku may be wrong, not uptodate or we have a bug somewhere.
please test and give feedback.

```json
{
	"extra":{
		"magento-root-dir": "../htdocs/",
		"magento-deploystrategy": "symlink",
        "magento-deploystrategy-overwrite": {
            "magento-hackathon/magento-composer-installer-test-sort1": "copy",
            "magento-hackathon/magento-composer-installer-test-sort2": "copy"
        }
	}
}
```


### Define order in which you want your magento packages deployed

In some cases you may want to deploy your magento modules in specific order.
For example when you have conflicting files and you know which module should be the overwriting one.
As this makes most sense when you use copy with allowed force overwrite, here an example.

 
```json
{
	"extra":{
		"magento-root-dir": "../htdocs/",
		"magento-deploystrategy": "copy",
        "magento-force": true,
        "magento-deploy-sort-priority": {
            "magento-hackathon/magento-composer-installer-test-sort1": "200",
            "magento-hackathon/magento-composer-installer-test-sort2": "400",
            "magento-hackathon/magento-composer-installer-test-sort3": "200"
        }
	}
}
```

As we also have the earlier described `magento-deploystrategy-overwrite` you can build some interesting stuff.  
For note: no priority defined means 100, if its deploy strategy copy, we use 101 as default.
So copy gets per default deployed before the symlinked modules now.

### Prevent single Files from Deploy
 
In some cases, you may only want single files/directories not get deployed,
for this you can use `magento-deploy-ignore` which works either global or on module level.


```json
{
	"extra":{
		"magento-root-dir": "../htdocs/",
		"magento-deploystrategy": "copy",
        "magento-force": true,
        "magento-deploy-ignore": {
            "*": ["/index.php"],
            "connect20/mage_core_modules": ["/shell/compiler.php"]
        },
	}
}
```

may not work for symlink, when file/directory is content of a symlinked directory
 

### None Deploy
If you only want to place packages into the vendor directory with no linking/copying into Magento's folder structure use this deploy strategy.

```json
{
    ...
    "extra":{
        "magento-deploystrategy": "none"
    }
    ...
}
```

### Trigger deploy manually

On occasions you want trigger the deploy of magento modules without the need of an update/install process.

In short, there is an optional dependency to https://github.com/magento-hackathon/composer-command-integrator/.
To be able to use it, you need to add to your requirements of the project.

```json
{
    ...
    "require": {
        ...
        "magento-hackathon/composer-command-integrator": "*",
    },
    ...
```

If done and installed, you are able to use the commands:
```
./vendor/bin/composerCommandIntegrator.php
./vendor/bin/composerCommandIntegrator.php list
./vendor/bin/composerCommandIntegrator.php magento-module-deploy

```



