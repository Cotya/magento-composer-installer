## Change Log
All notable changes to this project will be documented in this file.  
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]
- Added a change log file

## [3.0.3-rc.2] - 2015-05-20
- The patching process was changed to _not_ create additional files (`bootstrap.php`, `Mage.class.php` etc.).
  Now, only the native `app/Mage.php` is changed.
- [New documentation](https://github.com/Cotya/magento-composer-installer/blob/3.0/doc/Autoloading.md) about the autoloader patching was added.
- Composer dependencies were updated

## [3.0.3-rc.1] - 2015-05-04
- New boolean `extra` config: `with-bootstrap-patch`. It controls whether the `app/Mage.php`
  file will be patched with the Composer autoloader ability. Defaults to `true`.
- Fixed unit tests calling Composer commands using a hardcoded `composer.phar`, breaking
  for people which had their command renamed to `composer`.
- Added support for modman's style of declaring just the source file (see [example](https://github.com/colinmollenhour/modman/blob/d58b80f2f9e60d3287577480ad78066d44ed530c/modman#L109-L110)).

## 3.0.2 - 2015-03-28

[unreleased]: https://github.com/Cotya/magento-composer-installer/compare/3.0.3-rc.2...HEAD
[3.0.3-rc.2]: https://github.com/Cotya/magento-composer-installer/compare/3.0.3-rc.1...3.0.3-rc.2
[3.0.3-rc.1]: https://github.com/Cotya/magento-composer-installer/compare/3.0.2...3.0.3-rc.1
