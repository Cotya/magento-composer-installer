MY_PATH="`dirname \"$0\"`"              # relative
MY_PATH="`( cd \"$MY_PATH/FullStackTest" && pwd )`"  # absolutized and normalized
if [ -z "$MY_PATH" ] ; then
  # error; for some reason, the path is not accessible
  # to the script (e.g. permissions re-evaled after suid)
  exit 1  # fail
fi

echo "enter $MY_PATH"
cd "$MY_PATH"

rm -rf ./htdocs
mkdir ./htdocs

rm -rf ./magento/vendor
rm -rf ./magento/composer.lock
rm -rf ./magento-modules/vendor
rm -rf ./magento-modules/composer.lock

composer.phar install --prefer-dist --no-dev --no-progress --no-interaction --profile --working-dir="./magento"

cp -f ./magento-modules/composer_1.json ./magento-modules/composer.json 
composer.phar install --prefer-dist --no-dev --no-progress --no-interaction --profile --optimize-autoloader --working-dir="./magento-modules"

cp -f ./magento-modules/composer_2.json ./magento-modules/composer.json 
composer.phar update --prefer-dist --no-dev --no-progress --no-interaction --profile --optimize-autoloader --working-dir="./magento-modules"

cp -f ./magento-modules/composer_1.json ./magento-modules/composer.json 
composer.phar update --prefer-dist --no-dev --no-progress --no-interaction --profile --optimize-autoloader --working-dir="./magento-modules"

