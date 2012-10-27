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

```
$ curl -s https://getcomposer.org/installer | php -- --install-dir=bin
```

#### On Windows
Please take a look at http://getcomposer.org/doc/00-intro.md#installation-windows

### 2. Download composer.json template


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
         "type": "vcs",
         "url": "git://github.com/magento-hackathon/magento-composer-installer.git"
      }
   ]
}
```

How to set up your ```composer.json``` in your project:

```
{
    "name": "companyname/projectname",
    "minimum-stability": "dev"
    "require": {
        "firegento/germansetup": "dev-composer",
        "magento-hackathon/magento-composer-installer": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git://github.com/firegento/firegento-germansetup.git"
        },
        {
            "type": "vcs",
            "url": "git://github.com/magento-hackathon/magento-composer-installer.git"
        }
    ],
    "extra":{
        "magento-root-dir": "htdocs/"
    }
}
```

## Testing ##

First install dev-stuff:

```
./bin/composer.phar install --dev
```

then run ```phpunit``` in projekt-root directory
