
# Solution 3 Draft






### format of module composer.json


```json
{
    "name": "your-vendor-name/module-name",
    "type": "magento-composer-module",
    "license":"OSL-3.0",
    "description":"A short one line description of your module",
    "authors":[
        {
            "name":"Author Name",
            "email":"author@example.com"
        }
    ],
    "autoload":{
        "classmap": ["src/"]
    },
    "require": {
        "magento-hackathon/magento-composer-installer": "*"
    },
    "extra":{
        "magento-meta": [
            "module-etc"    "src/app/code/community/vendor-name/module-name/etc",
            "module-xml"    "src/etc/modules/something.xml",
            "admin-design"  "src/app/design/adminhtml/default/something"
            "design"        "src/app/design/frontend/default/something"
            "admin-skin"    "src/skin/adminhtml/default/something",
            "skin":         "src/skin/frontend/default/something",
            "js":           "src/js"
        ]
    }
}
```

as you see, we have source paths for skin and js files, but no destination paths.  
Thats because things should be keept simple.  
"js" just copys every directory into the magento/js directory.  
The "skin" ones are more restrictive, they will create a directory depending on your module name.  
This means, "skin" will take the content of the source path and put it into "skin/frontend/default/default/vendor-name/module-name"

"module-xml" and "module-etc" will not get copied, instead we do a modification into the xml loading which takes them
direct from vendor.
Similar is planed for "design" files.



## changes to magento

### general changes

the central change will be regarding the bootstrap.
We first need to add the composer autoloader
and as second add a custom config_model to Mage::run() $options. (possible without core rewrites)


### module xml loading

* By rewriting the loadBase()/loadModules()/_loadDeclaredModules() methods
of the config_model we are able to load module.xml files from arbitary places.

Here i have read in comments about magento2, people dont like to load modules from the module itself because they
are think a deactivated module will get activated again after an update.  
This is nonsense, we still have the local.xml, as this is the same xml object, we can deactivate modules therefrom.
And even if not, we could add an additional file which gets loaded afterwards and is able to deactivate modules.

### template file loading

yes, magento is a great piece of software architecture. It allows to rewrite this without touching block classes.
Thats because they base on Mage_Core_Model_Design_Package->getTemplateFilename().
Similar for the other Design parts.


## drawbacks

its not possible anymore to overwrite classes by creating a class with same name in code/local.
Codepools in general will not work.


