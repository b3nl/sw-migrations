# sw-migrations

This package was inspired by the original database deployment of shopware. _$SHOPWARE_ROOT./build/ApplyDeltas.php_ iterates through _$SHOPWARE_ROOT/_sql/migrations_ and executes every migration file in this directory. Every migration file contains a class of type Shopware\Components\Migrations\AbstractMigration.

ApplyDeltas.php is a shell script which can be called like:

```shell
php ./ApplyDeltas.php --username="root" --password="example" --host="localhost" --dbname="example-db" [ --mode=(install|update) ]
```

Shopware saves (in table s_schema_version) which migration files were executes and executes only new ones in following calls.

The general process stays the same with this package, but we add extended functionality with a custom 

```shell
vendor/b3nl/sw-migrations/build/ApplyDeltas.php
```

This shell scripts provides additional parameters for iterating through custom migrations. It adds implements additional parameters to do so:

* shoppath: Your shopware root directory, because we could install this package in any directory we want
* migrationpath: The path to your custom migration folder
* tablesuffix: This suffix is used to create a custom version table for your specified migration path. **If you do not provide this value, your custom migration history is merged to the standard history. We suggest, that you provide this value in any case!**

This script could even be used to call the shopware standard migrations like so:

```shell
php vendor/b3nl/sw-migrations/build/ApplyDeltas.php --username="root" --password="example" --host="localhost" --dbname="example-db" [ --shoppath=$SHOPWARE_ROOT --migrationpath=$SHOPWARE_ROOT/_sql/migrations --mode=(install|update) ]
```

**German blog post about it: http://ecommerce-developer.de/shopware-migrationen-fuer-eigene-deployments-nutzen/**
