# Dev mode

You can run composer in two modes `--dev` (default) and `--no-dev` (production).
There is built-in support for this for the config parameters.

In dev mode any extra parameter can also be overwritten with a `-dev` variant.

For example:


```json
{
    "extra":{
        "magento-root-dir": "htdocs/",
        "magento-deploystrategy": "copy",
        "magento-deploystrategy-dev": "symlink"
    }
}
```

In production(`composer install --no-dev`) the copy strategy will be used and on your development symlinks will be used instead.
