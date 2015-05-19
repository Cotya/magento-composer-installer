### Adding Composer's autoloading ability to Magento

The main "switch" setting is `with-bootstrap-patch`, in `extra`.
This is boolean and can control if this plugin will apply the patch or not.
By default, this is `true`. If `false`, this entire process is skipped.

The patching process is idempotent, meaning that multiple applies do not result in
multiple changes, as the patch is checked for its existence before it's applied.
It's safe to run it multiple times: the change will be made _once_.

After some consideration, it was decided that the most reliable place to push Composer's
autoloader into Magento was `app/Mage.php`, effectively _changing_ it, including the necessary
file just before the `Mage` class declaration. This guarantees that access to 3rd party packages
is available in the web app, API calls, crons and shell scripts.   
The "first Magento event dispatch" strategy was also considered, but dismissed as not so reliable.

The change needs to be safe and minimal and it's more or less equivalent to
`require 'vendor/autoloader.php'`, with the required path being relative to Mage's root.

Note that the `vendor` folder is assumed either as a _sibling_ of Mage root or as its _child_.
Hence the following folder layouts are supported:
```
├── mage_root
│   └── app
│       └── Mage.php
└── vendor
```
and
```
├── app
│   └── Mage.php
└── vendor
```
It's recommended to use the first layout with Mage root set as _web_ root, 
so no access to other files except Magento's is granted. This also keeps Mage root clean. 
