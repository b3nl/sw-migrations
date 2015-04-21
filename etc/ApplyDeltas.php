#!/usr/bin/env php
<?php
/*
 * ./ApplyDeltas.php --username="root" --password="example" --host="localhost" --dbname="example-db" \
 *  --tablesuffix="deployment" --migrationpath=""
 */

date_default_timezone_set('UTC');

$longopts  = array(
    "username:",
    "password:",
    "host:",
    "dbname:",
    'tablesuffix:',
    'migrationpath:',
    'shoppath:'
);

$shoppath = $dbConfig['shoppath'];
$dbConfig = getopt('', $longopts);

if (empty($dbConfig)) {
    if (file_exists($shoppath . '/config.php')) {
        $config = require $shoppath . '/config.php';
    } else {
        die('Could not find shopware config');
    }

    $dbConfig = $config['db'];
}

if (!isset($dbConfig['host']) || empty($dbConfig['host'])) {
    $dbConfig['host'] = 'localhost';
}

$password = isset($dbConfig['password']) ? $dbConfig['password'] : '';

$connectionSettings = array(
    'host=' . $dbConfig['host'],
    'dbname=' . $dbConfig['dbname'],
);

if (!empty($dbConfig['socket'])) {
    $connectionSettings[] = 'unix_socket=' . $dbConfig['socket'];
}

if (!empty($dbConfig['port'])) {
    $connectionSettings[] = 'port=' . $dbConfig['port'];
}

$connectionString = implode(';', $connectionSettings);

try {
    $conn = new PDO(
        'mysql:' . $connectionString,
        $dbConfig['username'],
        $password,
        [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"]
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Reset sql_mode "STRICT_TRANS_TABLES" that will be default in MySQL 5.6
    $conn->exec('SET @@session.sql_mode = ""');

} catch (PDOException $e) {
    echo 'Could not connect to database: ' . $e->getMessage();
    exit(1);
}

require $shoppath . '/engine/Shopware/Components/Migrations/AbstractMigration.php';
require $shoppath . '/engine/Shopware/Components/Migrations/Manager.php';

$migrationManger = new Shopware\Components\Migrations\Manager($conn, $dbConfig["migrationpath"]);

if ($suffix = $dbConfig["tablesuffix"]) {
    $migrationManger->settablesuffix($suffix);
} // if

$migrationManger->run(\Shopware\Components\Migrations\AbstractMigration::MODUS_INSTALL);

exit(0);
