<?php
namespace SWMigrations\Components\Migrations;

use Shopware\Components\Migrations\Manager as OriginalManager;
use Shopware\Components\Migrations\AbstractMigration;

/**
 * Enables you to use custom migrations.
 * @author blange <github@b3nl.de>
 * @package SWMigrations
 * @subpackage Components\Migrations
 * @version $id$
 */
class Manager extends OriginalManager
{
    /**
     * Suffix for the schema table.
     * @var string
     */
    protected $tableSuffix = '';

    /**
     * Applies given $migration to database
     *
     * @param AbstractMigration $migration
     * @param string $modus
     * @throws \Exception
     */
    public function apply(AbstractMigration $migration, $modus = AbstractMigration::MODUS_INSTALL)
    {
        if (!$suffix = $this->getTableSuffix()) {
            return parent::apply($migration, $modus);
        } // if

        $sql = 'REPLACE s_schema_version_' . $suffix . ' (version, start_date, name) VALUES (:version, :date, :name)';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':version' => $migration->getVersion(),
            ':date' => date('Y-m-d H:i:s'),
            ':name' => $migration->getLabel(),
        ]);

        try {
            $migration->up($modus);
            $sqls = $migration->getSql();

            foreach ($sqls as $sql) {
                $this->connection->exec($sql);
            }
        } catch (\Exception $e) {
            $updateVersionSql = 'UPDATE s_schema_version_' . $suffix . ' SET error_msg = :msg WHERE version = :version';
            $stmt = $this->connection->prepare($updateVersionSql);
            $stmt->execute([
                ':version' => $migration->getVersion(),
                ':msg' => $e->getMessage(),
            ]);
            throw new \Exception("Could not apply migration: " . $e->getMessage());
        }

        $sql = 'UPDATE s_schema_version_' . $suffix . ' SET complete_date = :date WHERE version = :version';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':version' => $migration->getVersion(),
            ':date' => date('Y-m-d H:i:s')
        ]);
    } // function

    /**
     * Creates schama version table if not exists
     */
    public function createSchemaTable()
    {
        if (!$suffix = $this->getTableSuffix()) {
            return parent::createSchemaTable();
        } // if

        $sql = "
            CREATE TABLE IF NOT EXISTS `s_schema_version_{$suffix}` (
            `version` int(11) NOT NULL,
            `start_date` datetime NOT NULL,
            `complete_date` datetime DEFAULT NULL,
            `name` VARCHAR( 255 ) NOT NULL,
            `error_msg` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ";
        $this->connection->exec($sql);
    } // function

    /**
     * Returns current schma version found in database
     *
     * @return int
     */
    public function getCurrentVersion()
    {
        if (!$suffix = $this->getTableSuffix()) {
            return parent::getCurrentVersion();
        } // if

        $sql = 'SELECT version FROM s_schema_version_' . $suffix .
            ' WHERE complete_date IS NOT NULL ORDER BY version DESC';

        $currentVersion = (int)$this->connection->query($sql)->fetchColumn();

        return $currentVersion;
    } // function

    /**
     * Returns the schema table suffix.
     * @return string
     */
    public function getTableSuffix()
    {
        return $this->tableSuffix;
    } // function

    /**
     * Sets the schema table suffix.
     * @param string $tableSuffix
     * @return Manager
     */
    public function setTableSuffix($tableSuffix)
    {
        $this->tableSuffix = $tableSuffix;

        return $this;
    } // function
}
