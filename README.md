[![Build Status](https://travis-ci.org/magento-hackathon/magento-composer-installer.png)](https://travis-ci.org/magento-hackathon/magento-composer-installer)

# Magento Composer Installer

The purpose of this project is to enable composer to install Magento modules, and automatically integrate them into
a Magneto installation.

There are several ways how the mapping from files in the package into the Magento source is accomplished:

1. [modman](https://github.com/colinmollenhour/modman) files.
2. Magento-Connect package.xml files
3. A mapping in the composer.json

As long as one of these mappings can be found, Magento modules are installable.

The package files are symlinked into the Magento instance by default. You can also use a copy or hardlink deploy strategy
by configuring it in the root composer.json (see below).

A repository of composer ready Magento modules can be found on http://packages.firegento.com/

The Magento root directory must be specified in the ```composer.json``` under ```extra.magento-root-dir```.

**NOTE:** modman's include and bash feature are currently not supported!


## Usage

See below for a generic instruction on how to install composer if you aren't familiar with it.

If you want to use the pubic Magento module repository, set up your root ```composer.json``` in your project like this:

```json
{
    "require": {
        "your-vendor-name/module-name": "*"
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
    ],
    "require": {
        "magento-hackathon/magento-composer-installer": "*"
    }
}
```


If you would like to publish your module on http://packages.firegento.com/, please fork
https://github.com/magento-hackathon/composer-repository, add your module to the satis.jason on the master branch and
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


### Mapping per JSON
If you don't like modman files, you can define mappings in a package composer.json file instead.

```json
{
   "name": "test/test",
   "type": "magento-module",
   "require": {
      "magento-hackathon/magento-composer-installer": "*"
   },
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

### Deploy per Copy or Hardlink

There is a deploy per copy strategy. This is how to use it for your deploys:

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

### Trigger deploy manually

Om occasions you want trigger the deploy of magento modules without the need of an update/install process.

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
        "skip-package-deployment": true
    }
    ...
}
```


### Testing

First clone the magento-composer-installer, then install the dev-stuff:

```
./bin/composer.phar install --dev
```

then run ```phpunit``` in projekt-root directory.

Windows users please run ```phpunit``` with Administrator permissions.


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

go to your project root directory

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

See Usage

### 3. Install Magento modules via composer

```
php ./composer.phar install
```


