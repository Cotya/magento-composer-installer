Frequently asked Questions
==========================

### Should my module require the Installer

No, it should not. But it can suggest using it (same syntax as require, but you use "suggest" instead of "require".

Why? Because there can be alternatives to this Installer or some people dont want to use an installer for them at all. 
As a module publisher you only decide the type of your package. It is the responsibility of the package user to decide
what installer or plugin he uses to handle this package type.
Also by require the installer, you can produce version conflicts,
as people could start require specific versions of the Installer.
Which makes absolutely no sense for a module.

### Can I install the Installer as global composer Plugin

currently No. As we need special configs this makes things a lot more complicated then installing on project level.


### I want to use the composer autoloader or some other different from magento one, how do I do this?

This installer patches the Mage.php in a very special way, which allows to add own bootstrapping code in a file
located at `/app/bootstrap.php`.
You can add the composer autoloader there,
and it will work for any script in magento kontext from the very first moment.  
Other solutions and existing autolaod modules needed to rewrite core classes or did not work by default when requests
happened outside the default webserver request flow. Like for example `/shell/some_script.php` calls or external code
using magento via `Mage::app()`

### There was some part about installing Magento-Core some time ago

Yes, we got a big contribution for a special install method regarding the magento/core.  
Sadly the transfare into this installer project was more complicated then expected and it occured some issues.
As the Issues were not resolveable, and there were not enoug tests for this part, we decided to remove this feature.

If you want to use it, we suggest to use the [bragento-composer-installer](https://github.com/bragento/bragento-composer-installer). 

