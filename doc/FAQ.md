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

