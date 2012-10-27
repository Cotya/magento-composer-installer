# About Hackathon Magento Composer

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
   "type": "library",
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
    ]
}
```
