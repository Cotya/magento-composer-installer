[![Build Status](https://travis-ci.org/Cotya/magento-composer-installer.png)](https://travis-ci.org/Cotya/magento-composer-installer)
[![Windows Build status](https://ci.appveyor.com/api/projects/status/1bm54s9jv3603xl5?svg=true)](https://ci.appveyor.com/project/Flyingmana/magento-composer-installer-396)
[![Dependency Status](https://www.versioneye.com/php/magento-hackathon:magento-composer-installer/badge.svg)](https://www.versioneye.com/php/magento-hackathon:magento-composer-installer/)
[![Reference Status](https://www.versioneye.com/php/magento-hackathon:magento-composer-installer/reference_badge.svg)](https://www.versioneye.com/php/magento-hackathon:magento-composer-installer/references)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Cotya/magento-composer-installer/badges/quality-score.png)](https://scrutinizer-ci.com/g/Cotya/magento-composer-installer/)
[![Code Coverage](https://scrutinizer-ci.com/g/Cotya/magento-composer-installer/badges/coverage.png)](https://scrutinizer-ci.com/g/Cotya/magento-composer-installer/)
[![Bountysource](https://www.bountysource.com/badge/tracker?tracker_id=284872)](https://www.bountysource.com/trackers/284872-magento-hackathon-magento-composer-installer?utm_source=284872&utm_medium=shield&utm_campaign=TRACKER_BADGE)
[![GetBadges Game](https://cotya-magento-composer-installer.getbadges.io/shield/company/cotya-magento-composer-installer)](https://cotya-magento-composer-installer.getbadges.io/?ref=shield-game)

# Magento Composer Installer 
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/magento-hackathon/magento-composer-installer?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


The purpose of this project is to 
enable [composer](https://github.com/composer/composer) to install Magento modules,
and automatically integrate them into a Magento installation and add Composer's vendor autoloader
ability the Magento's so that Composer-compatible 3rd party tools can be used.

If you want to install the Magento Core, you should try
[AydinHassan/magento-core-composer-installer](https://github.com/AydinHassan/magento-core-composer-installer)
as additional plugin.

We strongly recommend you to also read the general composer documentations on [getcomposer.org](http://getcomposer.org)

Also you should see:

 * [Using composer correctly (confoo) by Igor Wiedler](https://speakerdeck.com/igorw/using-composer-correctly-confoo)
 
 
## Magento 2

Congratulation to work with Magento 2. Dont try to use it together with this project.
Your princess is in [another Castle](http://devdocs.magento.com/guides/v2.0/install-gde/prereq/integrator_install.html#integrator-first-composer-ce)
 
## Project Details
 
This project only covers the custom installer for composer. If you have problems with outdated versions,
need to install magento connect modules or similar, you need to look for [packages.firegento.com](http://packages.firegento.com/)
which you probably should add as composer repository (globally)

```composer config -g repositories.firegento composer http://packages.firegento.com```

### supported PHP Versions

We don't officially support PHP versions which are [End of Life](https://secure.php.net/eol.php) means which dont get [security patches](https://secure.php.net/supported-versions.php) anymore. Even if the install requirement still allows them.  
This will change, as soon someone is willing to pay for supporting them.

### support contacts
 
If you have problems please have patience, as normal support is done during free time.  
If you are willing to pay to get your problem fixed, communicate this from the start to get faster responses.
 
If you need consulting, support, training or help regarding Magento and Composer,
you have the chance to hire one of the following people/companies.
 
* Daniel Fahlke aka Flyingmana (Maintainer): flyingmana@googlemail.com [@Flyingmana](https://twitter.com/Flyingmana)
* brandung - Magento Team: magento-team@brandung.de (http://brandung.de)
 
other support contacts
 
* irc: freenode the channels #magento-composer #magento-reddit and for german speaking people #magento-de 
* twitter: [@firegento](https://twitter.com/firegento)

### changelog

See [CHANGELOG.md](CHANGELOG.md).

=======
## Known issues

### need to redeploy packages

earlier we suggested the use of the command integrator package, that is not needed anymore.
```composer.phar run-script post-install-cmd -vvv -- --redeploy```  
This does remove all deployed files and redeploys every module

### using non default autoloading

we handle this topic in our [FAQ](doc/FAQ.md).

### Timeouts and slow downloading. 

Mostly caused by outages of Github, Repositories or the Internet. This is a common problem with having all 
packages remote.

For all of this issues you can make use of the commercial [Toran Proxy](https://toranproxy.com/).
It also allows hosting of private packages and speeds up the whole downloading process.

Another alternative is to look into [Satis](https://github.com/composer/satis), bare git mirrors and repository aliasing.

Another way to speedup downloads over ssh (also interesting for satis users) is to improve your ssh configs.
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

further information can be found on [wikibooks](https://en.wikibooks.org/wiki/OpenSSH/Cookbook/Multiplexing) 

## Usage

### Update the Installer

as this is a composer plugin, you should only use this both commands to update the installer

``` 
composer require --no-update  magento-hackathon/magento-composer-installer=3.0.*
composer update --no-plugins --no-scripts magento-hackathon/magento-composer-installer
```

the second command needs maybe a `--with-dependencies`  
Depending on your workflow with composer, you may want to use more explicite versions

### Install a module in your project

make sure to use [the public Magento module repository](https://packages.firegento.com) as composer repository:

```composer config -g repositories.firegento composer https://packages.firegento.com```

configure your `magento root dir`, the directory where your magento resides:  
```composer config extra.magento-root-dir "htdocs/"```
 
an example how your project ```composer.json``` could look like:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.firegento.com"
        }
    ],
    "extra":{
        "magento-root-dir": "htdocs/"
    }
}
```

### Auto add files to .gitignore

If you want to have the deployed files automatically added to your `.gitignore file`, then you can just set the `auto-append-gitignore` key to true:

```json
{
    "extra":{
        "magento-root-dir": "htdocs/",
        "auto-append-gitignore": true
    }
}
```

The `.gitignore` file will be loaded from the current directory, and if it does not exist, it will be created. Every set of module files, will have a comment above them
describing the module name for clarity.

Multiple deploys will not add additional lines to your `.gitignore`, they will only ever be added once.


### Adding Composer's autoloader to Magento

Documentation available [here](doc/Autoloading.md). 

### Overwriting a production setting (DevMode)

```json
{
    "extra":{
        "magento-deploystrategy": "copy",
        "magento-deploystrategy-dev": "symlink"
    }
}
```

Example in [devmode doc](doc/DevMode.md).


### Include your project in deployment

When the magento-composer-installer is run, it only looks for magento-modules among your project's dependencies. Thus, if
your project is a magento-module and you want to test it, you will need a second `composer.json` for deployment, 
where your project is configured as a required package.

If you wish to deploy your project's files (a.k.a. root package), too, you need to setup your `composer.json` as follows:

```
{
    "type": "magento-module",
    ...
    "extra": {
        "magento-root-dir": "htdocs/",
        "include-root-package": true
    }
}
```

### Testing

First clone the magento-composer-installer, then install the dev-stuff (installed by default):

```
./bin/composer.phar install
```

then run ```vendor/bin/phpunit``` in project-root directory.

Note: Windows users please run ```phpunit``` with Administrator permissions.


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

Teached me (Flyingmana) most I know about Magento and
paid my participation for the hackathon were the installer got created.

#####[melovely](http://www.melovely.de/)

Support me (Flyingmana) as my current employer very much in my work on everything composer related.
