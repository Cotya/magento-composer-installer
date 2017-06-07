# Dev mode

Composer supports two modes `--dev` (default) and `--no-dev` (production).
Overwrite any `extra.*` parameter by adding `-dev` to it.

In dev mode any extra `-dev` variant will overwrite the original.

The next example:
- will use copy strategy on production and symlink locally
- will only append to .gitignore on dev machines
- will only force on production

```json
{
    "extra":{
        "magento-root-dir": "htdocs/",
        
        "magento-deploystrategy": "copy",
        "magento-deploystrategy-dev": "symlink",
        
        "auto-append-gitignore-dev": true,
        
        "magento-force": true,
        "magento-force-dev": false
    }
}
```

In production run `composer install --no-dev` and all `*-dev` will be ignored. 
