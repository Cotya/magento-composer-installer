Make a module installable with composer
=======================================


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


