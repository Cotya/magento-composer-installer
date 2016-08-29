## Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]
-
-

## [3.0.7] - 2016-08-29
- Fixed an issue where symlinks were follwed when removing a module, causing files in vendor to be removed
- Added PHP 7.0, HHVM to Travis CI. Removed allow_failures for HHVM.
- Changes the way gitignore files are being processed. Retains the layout and other duplicates (comments, empty lines, etc.)
- README example snippet uses https to prevent 'configuration does not allow connections to' errors
- added PR [#90](https://github.com/Cotya/magento-composer-installer/pull/90): Added check to see if directory exists (for redeploy command)
- added PR [#91](https://github.com/Cotya/magento-composer-installer/pull/91): Resolve package.xml mappings edge case: Replace double slashes in mappings
- added regression test covering pull request [#65](https://github.com/Cotya/magento-composer-installer/pull/65)
- Added functionality to include root package when deploying modules
- remove eol php version (4.5) from tests
- add test for updating the installer
- annoy people when they try to update the installer with active plugins
- test the installer against different composer versions on Travis
- allow Symfony/console 3.x as parallel dependency


## [3.0.6] - 2015-10-21
- Fix problems with magento connect packages referencing non existent files
- Fix PHP TestSetup for windows with PR [#69](https://github.com/Cotya/magento-composer-installer/pull/69)
- Added PR [#73](https://github.com/Cotya/magento-composer-installer/pull/73): add workaround for paths with containing whitespace

## [3.0.5] - 2015-08-05
- Fixed Issue [#20](https://github.com/Cotya/magento-composer-installer/issues/20): 'mklink""' is not recognized as an internal or external command, operable program or batch file.
- Fixed issue [#48](https://github.com/Cotya/magento-composer-installer/issues/48): Package XML mappings have './' prepended to them which breaks git-ignore functionality. Also explicitly adds a fw-slash to git-ignore paths, if one does not exist already.
- Added functionality to remove entries from the git-ignore file when a module is uninstalled
- Fixed an issue where empty directories were left behind after un-installing a module. If a structure like `/folder1/folder2/file1.txt` was created, and both folders were created by the module. Only `folder2` would be removed. It now traverses up-to the root-dir removing any empty directories.
- Added documentation for package.xml mapping (PR [#47](https://github.com/Cotya/magento-composer-installer/pull/47))
- set 5.4 as minimum required Version

## [3.0.4] - 2015-07-12
- Added PR [#40](https://github.com/Cotya/magento-composer-installer/pull/40): extra config option to skip repository suggestions
- Reverted PR [#34](https://github.com/Cotya/magento-composer-installer/pull/34) in favor of a simpler solution without breaking BC
- Fixed issue [#7](https://github.com/Cotya/magento-composer-installer/issues/7) by filtering out Aliased Packages. This was with branch-aliasing where composer gave us two packages for the same module when it was setup to use branch aliasing
- Fixed issue [#33](https://github.com/Cotya/magento-composer-installer/issues/33) by using the source reference from the composer package as part of the internal version number for tracking packages
- Fixed issue [#39](https://github.com/Cotya/magento-composer-installer/issues/39) Symlinks were not removed correctly on module remove

## [3.0.4-beta1] - 2015-06-10
- Fixed error when redeploying with no modules, using PHP 5.3. [Issue #16](https://github.com/Cotya/magento-composer-installer/issues/16) [PR #29](https://github.com/Cotya/magento-composer-installer/pull/29)
- Fixed the Patcher throwing an exception if `app/Mage.php` was missing,
  even when `with-bootstrap-patch` was set to `false`. [Issue #31](https://github.com/Cotya/magento-composer-installer/issues/31) [PR #32](https://github.com/Cotya/magento-composer-installer/pull/32)
- Changed Patcher throwing an exception to just output a *comment* Message
- Add sourceReference support for installed.json, fixes issues with updates for dev-master type repositories
  where version is not a good indication of updates.
- Remove exception for `InstalledPackageFileSystemRepository::add()` method,
  the function is used for both updates and new installs.
- Relaxed the Plugin API constraint to `~1.0` so that the next version
  bump won't exclude this installer.
- Updated dependencies' versions.

## [3.0.3] - 2015-06-02
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

[unreleased]: https://github.com/Cotya/magento-composer-installer/compare/3.0.6...HEAD
[3.0.6]: https://github.com/Cotya/magento-composer-installer/compare/3.0.5...3.0.6
[3.0.5]: https://github.com/Cotya/magento-composer-installer/compare/3.0.4...3.0.5
[3.0.4]: https://github.com/Cotya/magento-composer-installer/compare/3.0.4-beta1...3.0.4
[3.0.4-beta1]: https://github.com/Cotya/magento-composer-installer/compare/3.0.3...3.0.4-beta1
[3.0.3]: https://github.com/Cotya/magento-composer-installer/compare/3.0.3-rc.2...3.0.3
[3.0.3-rc.2]: https://github.com/Cotya/magento-composer-installer/compare/3.0.3-rc.1...3.0.3-rc.2
[3.0.3-rc.1]: https://github.com/Cotya/magento-composer-installer/compare/3.0.2...3.0.3-rc.1
