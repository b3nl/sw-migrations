#!/usr/bin/env php
<?php
/*
 * ./ApplyDeltas.php --username="root" --password="example" --host="localhost" --dbname="example-db" \
 *  --tablesuffix="deployment" --migrationpath="custom/migrations" --shoppath="./shopware" [ --mode=(install|update) ]
 */

date_default_timezone_set('UTC');

$longopts = [
    "username:",
    "password:",
    "host:",
    "dbname:"
];

$deployConfig = getopt('', ['tablesuffix::', 'shoppath:', 'migrationpath:']);
$dbConfig = getopt('', $longopts);
$shopPath = $deployConfig['shoppath'];

if (empty($dbConfig)) {
    if (file_exists($shopPath . '/config.php')) {
        $config = require $shopPath . '/config.php';
    } else {
        die('Could not find shopware config');
    }

    $dbConfig = $config['db'];
}

if (!isset($dbConfig['host']) || empty($dbConfig['host'])) {
    $dbConfig['host'] = 'localhost';
}

$password = isset($dbConfig['password']) ? $dbConfig['password'] : '';

$connectionSettings = [
    'host=' . $dbConfig['host'],
    'dbname=' . $dbConfig['dbname'],
];

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

require $shopPath . '/engine/Shopware/Components/Migrations/AbstractMigration.php';
require $shopPath . '/engine/Shopware/Components/Migrations/Manager.php';
require __DIR__ . '/../src/Components/Migrations/Manager.php';

$modeArg = getopt('', array('mode:'));
if (!isset($modeArg['mode']) || $modeArg['mode'] == 'install') {
    $mode = \Shopware\Components\Migrations\AbstractMigration::MODUS_INSTALL;
} else {
    $mode = \Shopware\Components\Migrations\AbstractMigration::MODUS_UPDATE;
}

$migrationManger = new SWMigrations\Components\Migrations\Manager($conn, $deployConfig["migrationpath"]);

if ($suffix = $deployConfig["tablesuffix"]) {
    $migrationManger->setTableSuffix($suffix);
} // if

$migrationManger->run($mode);

exit(0);
