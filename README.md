# About Hackathon Magento Composer

The purpose of this project is to enable composer to parse and execute
[modman](https://github.com/colinmollenhour/modman) files.
Magento modules are installable as long as they have a valid modman file.

The Magento root directory must be specified in the ```composer.json``` under ```extra.magento-root-dir```.

**NOTE:** modman's include and bash feature are currently not supported! Only
symlinks are created.


## Installation

### 1. Install PHP-Composer

#### On Linux/Mac

go to your project root directory

```
mkdir bin
$ curl -s https://getcomposer.org/installer | php -- --install-dir=bin
```

#### On Windows
Please take a look at http://getcomposer.org/doc/00-intro.md#installation-windows

Creation of symbolic links requires the SeCreateSymbolicLinkPrivilege (“Create symbolic links”), which is granted only to administrators by default (but you can change that using security policy).

To change the policies:
- Launch secpol.msc via Start or Start → Run.
- Open Security Settings → Local Policies → User Rights Assignment.
- In the list, find the "Create symbolic links" item, which represents SeCreateSymbolicLinkPrivilege.
    Double-click on the item and add yourself (or the whole Users group) to the list.

(Seen at http://superuser.com/questions/124679/how-do-i-create-an-mklink-in-windows-7-home-premium-as-a-regular-user#125981)


### 2. Download composer.json template

See Usage

### 3. Install Hackathon Magento Composer

```
./composer.phar install
```



## Usage

How to set up your ```composer.json``` in your module:

```
{
    "name": "firegento/germansetup",
    "type": "magento-module",
    "minimum-stability": "dev",
    "require": {
        "magento-hackathon/magento-composer-installer": "dev-master"
    },
    "repositories": [
        {
            "type": "composer",
            "url": "http://packages.firegento.com"
        }
    ]
}
```

How to set up your ```composer.json``` in your project:

```
{
    "minimum-stability": "dev",
    "require": {
        "firegento/germansetup": "dev-composer",
        "magento-hackathon/magento-composer-installer": "dev-master"
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

## Testing

First clone magento composter installer, then install dev-stuff:

```
./bin/composer.phar install --dev
```

then run ```phpunit``` in projekt-root directory

Windows users please run ```phpunit``` with Administrator permissions.

## How to Overwrite Dependencies

We don't want to use always the official repositories for specific dependencies.
For example for development purpose or use versions with custom patches.

For this case you have the _repositories_ section inside your project composer.json
Here you can define own package composer.json for specific dependencies by the package name.

This example shows how to use a local git projects local-master branch which even works without a composer.json inside
and a project in VCS with existing composer.json, which is not yet on packagist.

```json
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
   },
   {
      "type": "vcs",
      "url": "git://github.com/firegento/firegento-germansetup.git"
   }
]
```

