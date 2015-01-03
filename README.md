[![Build Status](https://travis-ci.org/cotya/magento-composer-installer.png)](https://travis-ci.org/magento-hackathon/magento-composer-installer)
[![Dependency Status](https://www.versioneye.com/php/magento-hackathon:magento-composer-installer/2.0.0/badge.svg)](https://www.versioneye.com/php/magento-hackathon:magento-composer-installer/2.0.0)
[![Reference Status](https://www.versioneye.com/php/magento-hackathon:magento-composer-installer/reference_badge.svg)](https://www.versioneye.com/php/magento-hackathon:magento-composer-installer/references)
[![Bountysource](https://www.bountysource.com/badge/tracker?tracker_id=284872)](https://www.bountysource.com/trackers/284872-magento-hackathon-magento-composer-installer?utm_source=284872&utm_medium=shield&utm_campaign=TRACKER_BADGE)

# Magento Composer Installer 
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/magento-hackathon/magento-composer-installer?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

<img src="https://getcomposer.org/img/logo-composer-transparent.png" itemprop="image" alt="Dart Mosaic" style="height:50px;">

The purpose of this project is to 
enable [composer](https://github.com/composer/composer) to install Magento modules,
and automatically integrate them into a Magento installation.

We strongly recommend you to also read the general composer documentations on [getcomposer.org](http://getcomposer.org)

Also you should see [Using composer correctly (confoo) by Igor Wiedler](https://speakerdeck.com/igorw/using-composer-correctly-confoo)

 
## Project Details
 
This project only covers the custom installer for composer. If you have problems with outdated versions,
need to install magento connect modules or similar, you need to look for [packages.firegento.com](http://packages.firegento.com/)
 
 
### support contacts
 
If you have problems please have patience, as normal support is done during free time.  
If you are willing to pay to get your problem fixed, communicate this from the start to get faster responses.
 
 
If you need consulting, support, training or help regarding Magento and Composer,
you have the chance to hire one of the following people/companies.
 
* Daniel Fahlke aka Flyingmana (Maintainer): flyingmana@googlemail.com [@Flyingmana](https://twitter.com/Flyingmana)
* brandung - Magento Team: magento-team@brandung.de (http://brandung.de)
* Your Name here
* Your Name here
* Your Company Name here
* Your Company Name here
 
other support contacts
 
* irc: freenode the channels #magento-composer #magento-reddit and for german speaking people #magento-de 
* twitter: [@firegento](https://twitter.com/firegento)

## Known issue
- Error message: `Fatal error: Call to undefined method MagentoHackathon\Composer\Magento\Installer::setDeployManager()` happens when you update from 1.x to 2.x, as we switched from pure installer to plugin.

Solution: remove the `vendor` directory and the `composer.lock` and do a fresh install.
=======
## Known issues

### When upgrading from 1.x to 2.x 

The update from 1.x to 2.x has to be done with no plugins as otherwise a fatal error will be triggered (which does not hurt, just run the update again and it runs through).

- Error message: `Fatal error: Call to undefined method MagentoHackathon\Composer\Magento\Installer::setDeployManager()` 

To prevent this error, upgrade only *magento-composer-installer* first:

```composer update --no-plugins --no-dev "magento-hackathon/magento-composer-installer"``` 

Fallback Solutions:

1. execute `composer install` two times.
2. remove the `vendor` directory and `composer.lock` and do a fresh install.

### Timeouts and slow downloading. 

Mostly caused by outtages of Github, Repositories or the Internet. This is a common problem with having all 
packges remote.

For all of this Issues you can make use of the commercial [Toran Proxy](https://toranproxy.com/).
It also allows hosting of private packages and speeds up the whole downloading process.

Another alternative is to look into [Satis](https://github.com/composer/satis), bare git mirrors and repository aliasing.

Another way to speedup downloads over ssh(also interesting for satis users) is to improve your ssh configs.
At least for newer versions of openSSH you can add the following to your ```.ssh/config``` to reuse previous connections.
```
Host * 
    ControlPath ~/.ssh/controlmasters/%r@%h:%p
    ControlMaster auto
    ControlPersist 10m
```

also you need to create the ```controlmasters``` directory:
```sh
mkdir ~/.ssh/controlmasters
chmod go-xr ~/.ssh/controlmasters
```

further information can be found on [wikibooks](http://en.wikibooks.org/wiki/OpenSSH/Cookbook/Multiplexing) 

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
* [Make a Magento module installable with composer](doc/MakeAModuleInstallableWithComposer.md)
* [About File Mapping like for example modman](doc/Mapping.md)
* [About Deploying files into your Magento root and possible configs](doc/Deploy.md)

### External Links

* [Composer How to Screencast](http://www.youtube.com/watch?v=m_yprtQiFgk)
* [Introducing Composer Blog on Magebase.com](http://magebase.com/magento-tutorials/composer-with-magento/)
* [Magento, Composer and Symfonys Dependency Injection](http://www.piotrbelina.com/magento-composer-and-dependency-injection/)
* [Using Composer for Magento(at engineyard)](https://blog.engineyard.com/2014/composer-for-magento)

### Core Contributors

* Daniel Fahlke aka Flyingmana (Maintainer)
* Jörg Weller
* Karl Spies
* Tobias Vogt
* David Fuhr
* Amir Tchavoshinia
* Vinai Kopp (Maintainer)


## Thank You

There are a few companies we want to thank for supporting this project in one way or another.

#####[digital.manufaktur GmbH](https://www.digitalmanufaktur.com/)

Teached me(Flyingmana) most I know about Magento and
paid my participation for the hackathon were the installer got created.

#####[melovely](http://www.melovely.de/)

Support me(Flyingmana) as my current employer very much in my work on everything composer related.


