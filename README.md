# Magento Composer Installer [![Build Status](https://travis-ci.org/magento-hackathon/magento-composer-installer.png)](https://travis-ci.org/magento-hackathon/magento-composer-installer)

The purpose of this project is to 
enable [composer](https://github.com/composer/composer) to install Magento modules,
and automatically integrate them into a Magento installation.

We strongly recommend you to also read the general composer documentations on [getcomposer.org](http://getcomposer.org) 

 
## Project Details
 
This project only covers the custom installer for composer. If you have problems with outdated versions,
need to install magento connect modules or similar, you need to look for [packages.firegento.com](http://packages.firegento.com/)
 
 
### support contacts
 
If you have problems please have patience, as normal support is done during free time.  
If you are willing to pay to get your problem fixed, communicate this from the start to get faster responses.
 
 
If you need consulting, support, training or help regarding Magento and Composer,
you have the chance to hire one of the following people/companies.
 
* Daniel Fahlke aka Flyingmana (Maintainer): flyingmana@googlemail.com [@Flyingmana](https://twitter.com/Flyingmana)
* Your Name here
* Your Name here
* Your Company Name here
* Your Company Name here
 
other support contacts
 
* irc: freenode the channels #magento-composer #magento-reddit and for german speaking people #magento-de 
* twitter: [@firegento](https://twitter.com/firegento)



## Usage

See below for a [generic instruction on how to install composer](#installation-of-composer) if you aren't familiar with it.

### Install a module in your project

If you want to use [the public Magento module repository](http://packages.firegento.com),
set up your root ```composer.json``` in your project like this:

```json
{
    "require": {
        "your-vendor-name/module-name": "*",
        "magento-hackathon/magento-composer-installer": "*"
    },
    "repositories": [
        {
            "type": "composer",
            "url": "http://packages.firegento.com"
        }
    ],
    "extra":{
        "magento-root-dir": "htdocs/"
    }
}
```

If you want to use a github/git/svn/etc repository, 
set up your root ```composer.json``` in your project like this:

```json
{
    "require": {
        "magento-hackathon/magento-composer-installer":"*",
        "the-vendor-name/the-module-name": "*"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/magento-hackathon/magento-composer-installer"
        },
        {
            "type": "vcs",
            "url": "the/github/or/git/or/svn/etc/repository/uri-of-the-module"
        }
    ],
    "extra":{
        "magento-root-dir": "htdocs/"
    }
}
```
Notes:

1. More information about VCS repositories can be found 
   at [getcomposer.org](http://getcomposer.org/doc/05-repositories.md#vcs)


### Make a module installable with composer


To make a Magento module installable with composer, this is how to set up the ```composer.json``` for your extension:

```json
{
    "name": "your-vendor-name/module-name",
    "type": "magento-module",
    "license":"OSL-3.0",
    "description":"A short one line description of your module",
    "authors":[
        {
            "name":"Author Name",
            "email":"author@example.com"
        }
    ]
}
```


If you would like to publish your module on http://packages.firegento.com/, please fork
https://github.com/magento-hackathon/composer-repository, add your module to the [satis.json](https://github.com/magento-hackathon/composer-repository/blob/master/satis.json)  on the master branch and
open a pull request.

If you want to install your module without publishing it on http://packages.firegento.com/, you can add your repository
to your projects composer.json directly and it will install, too.

```json
{
    ...
    "repositories": [
        {
            "type": "vcs",
            "url": "your/github/or/git/or/svn/etc/repository/uri"
        }
    ],
    ...
}
```
More information can be found at
http://getcomposer.org/doc/05-repositories.md#vcs


### Change the Vendor/Name of your Module

sometimes it will happen, that you change the name or the vendor of a package.
For example you developed a module in your own namespace and later moved it to an organization, or you moved it
from one to another organization.
In this cases you should change your ```composer.json``` a bit to make it for users easier.
Look for the ```replace``` statement


```json
{
    "name": "your-new-vendor-name/module-name",
    "type": "magento-module",
    "license":"OSL-3.0",
    "description":"A short one line description of your module",
    "authors":[
        {
            "name":"Author Name",
            "email":"author@example.com"
        }
    ],
    "replace": {
        "your-vendor-name/module-name":"*"
    }
}
```

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

### Custom Magento module location

By default all magento-modules packages will be installed in the configured "vendor-dir" (which is "vendor" by default).
The package name will be used as a directory path and if there is a "target-dir" configured this will also be appended.
This results in packaged being installed in a path like this one: vendor/colinmollenhour/cm_diehard.

Originally modman packages "live" in a directory called ".modman". This directory can be inside your htdocs directory,
next to it or where ever you want it to be.

If you want magento-composer-installer to install your Magento extensions in a custom location, this can be configured
as follows:

```json
{
    ...
    "extra":{
        "magento-root-dir": "htdocs/",
        "modman-root-dir": ".modman"
    }
    ...
}
```

Make sure the .modman directory exists before updating. There is a fallback in place that will try to find the directory
relative to your vendor dir if it wasn't found in the first place.

If you want to still use the original modman script, too, and if your modman-root-dir configuration is
not "htdocs/.modman" you'll need a ".basedir" file inside ".modman" that specifies where to find the htdocs folder (see
the [modman](https://github.com/colinmollenhour/modman/blob/master/modman#L268-L279) documentation)

Should you choose to only use the original modman script to deploy packages, you will not want to have the
magento-composer-installer deploy the packages. So this can be disabled:

```json
{
    ...
    "extra":{
        "magento-root-dir": "htdocs/",
        "modman-root-dir": ".modman",
        "magento-deploystrategy": "none"
    }
    ...
}
```
### Auto add files to .gitignore

If you want to have the deployed files automatically added to your .gitignore file, then you can just set the `auto-append-gitignore` key to true:

```json
{
    ...
    "extra":{
        "magento-root-dir": "htdocs/",
        "auto-append-gitignore": true
    }
    ...
}
```

The `.gitignore` file will be loaded from the current directory, and if it does not exist, it will be created. Every set of module files, will have a comment above them
describing the module name for clarity.

Multiple deploys will not add additional lines to your .gitignore, they will only ever be added once.
 

### Testing

First clone the magento-composer-installer, then install the dev-stuff:

```
./bin/composer.phar install --dev
```

then run ```vendor/bin/phpunit``` in project-root directory.

Note: Windows users please run ```phpunit``` with Administrator permissions.


### How to overwrite dependencies

We don't want to use always the official repositories for specific dependencies.
For example for development purpose or use versions with custom patches.

For this case you have the _repositories_ section inside your project composer.json
Here you can define own package composer.json for specific dependencies by the package name.

This example shows how to use a local git projects local-master branch which even works without a composer.json inside
and a project in VCS with existing composer.json, which is not yet on packagist.

```json
{
   ...
   "repositories": [
       {
          "type": "package",
          "package": {
             "name": "magento-hackathon/magento-composer-installer",
             "version": "dev-master",
             "type": "composer-installer",
             "source": {
                "type": "git",
                "url": "/public_projects/magento/magento-composer-installer/",
                "reference": "local-master"
             },
             "autoload": {
                "psr-0": {"MagentoHackathon\\Composer\\Magento": "src/"}
             },
             "extra": {
                "class": "\\MagentoHackathon\\Composer\\Magento\\Installer"
             }
          }
       }
    ]
}
```

## Installation of composer

### 1. Install PHP-Composer

#### On Linux/Mac

Go to your project root directory and run:

```
mkdir bin
curl -s https://getcomposer.org/installer | php -- --install-dir=bin
```

#### On Windows
Please take a look at http://getcomposer.org/doc/00-intro.md#installation-windows

Creation of symbolic links requires the SeCreateSymbolicLinkPrivilege (“Create symbolic links”), which is granted only
to administrators by default (but you can change that using security policy).

To change the policies:
- Launch secpol.msc via Start or Start → Run.
- Open Security Settings → Local Policies → User Rights Assignment.
- In the list, find the "Create symbolic links" item, which represents SeCreateSymbolicLinkPrivilege.
    Double-click on the item and add yourself (or the whole Users group) to the list.

(Seen at http://superuser.com/questions/124679/how-do-i-create-an-mklink-in-windows-7-home-premium-as-a-regular-user#125981)


### 2. Download composer.json template

See [Usage](#usage).


### 3. Install Magento modules via composer

```
php bin/composer.phar install
```





## Further Information

* [FAQ](doc/FAQ.md)

### External Links

* [Composer How to Screencast](http://www.youtube.com/watch?v=m_yprtQiFgk)
* [Introducing Composer Blog on Magebase.com](http://magebase.com/magento-tutorials/composer-with-magento/)

### Core Contributors

* Daniel Fahlke aka Flyingmana (Maintainer)
* Jörg Weller
* Karl Spies
* Tobias Vogt
* David Fuhr
* Vinai Kopp (Maintainer)


## Thank You

There are a few companies we want to thank for supporting this project in one way or another.

#####[digital.manufaktur GmbH](https://www.digitalmanufaktur.com/)

Teached me(Flyingmana) most I know about Magento and
paid my participation for the hackathon were the installer got created.

#####[melovely](http://www.melovely.de/)

Support me(Flyingmana) as my current employer very much in my work on everything composer related.


