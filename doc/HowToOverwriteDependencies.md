How to Overwrite Dependencies
=============================


We dont want to use always the officiell repositories for specific Dependencies.
For Example for development purpose or use versions with custom patches.

For this case you have the _repositories_ section inside your project composer.json
Here you can define own package composer.json for specific dependencies by the package name.

This example shows how to use a local git projects local-master branch which even works without a composer.json inside
and a project in vcs with existing composer.json, which is not yet on packagist.

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