<?php

declare(strict_types=1);

namespace SWMigrations\Components\Migrations;

use Exception;
use Shopware\Components\Migrations\AbstractMigration;
use Shopware\Components\Migrations\Manager as OriginalManager;

/**
 * Enables you to use custom migrations.
 *
 * @author blange <github@b3nl.de>
 * @package SWMigrations\Components\Migrations
 */
class Manager extends OriginalManager
{
    /**
     * Suffix for the schema table.
     *
     * @var string
     */
    protected $tableSuffix = '';

    /**
     * Applies given $migration to database
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @throws Exception
     *
     * @param AbstractMigration $migration
     * @param string $modus
     *
     * @return void
     */
    public function apply(AbstractMigration $migration, $modus = AbstractMigration::MODUS_INSTALL)
    {
        if (!$suffix = $this->getTableSuffix()) {
            return parent::apply($migration, $modus);
        } // if

        $sql = 'REPLACE `s_schema_version_' . $suffix . '` (version, start_date, name) VALUES (:version, :date, :name)';
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
        } catch (Exception $e) {
            $updateVersionSql = 'UPDATE `s_schema_version_' . $suffix . '` SET error_msg = :msg WHERE version = :version';
            $stmt = $this->connection->prepare($updateVersionSql);
            $stmt->execute([
                ':version' => $migration->getVersion(),
                ':msg' => $e->getMessage(),
            ]);
            throw new Exception('Could not apply migration: ' . $e->getMessage());
        }

        $sql = 'UPDATE `s_schema_version_' . $suffix . '` SET complete_date = :date WHERE version = :version';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':version' => $migration->getVersion(),
            ':date' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Creates schama version table if not exists.
     *
     * @return void
     */
    public function createSchemaTable()
    {
        if (!$suffix = $this->getTableSuffix()) {
            return parent::createSchemaTable();
        } // if

        $sql =
            "CREATE TABLE IF NOT EXISTS `s_schema_version_{$suffix}` (
            `version` int(11) NOT NULL,
            `start_date` datetime NOT NULL,
            `complete_date` datetime DEFAULT NULL,
            `name` VARCHAR( 255 ) NOT NULL,
            `error_msg` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->connection->exec($sql);
    }

    /**
     * Returns current schma version found in database
     *
     * @phpcsSuppress BestIt.TypeHints.ReturnTypeDeclaration.MissingReturnTypeHint
     *
     * @return int
     */
    public function getCurrentVersion()
    {
        if (!$suffix = $this->getTableSuffix()) {
            return parent::getCurrentVersion();
        } // if

        $sql = 'SELECT version FROM `s_schema_version_' . $suffix . '` 
                WHERE complete_date IS NOT NULL ORDER BY version DESC';

        $currentVersion = (int) $this->connection->query($sql)->fetchColumn();

        return $currentVersion;
    }

    /**
     * Returns the schema table suffix.
     *
     * @return string
     */
    public function getTableSuffix(): string
    {
        return $this->tableSuffix;
    }

    /**
     * Sets the schema table suffix.
     *
     * @param string $tableSuffix
     *
     * @return Manager
     */
    public function setTableSuffix(string $tableSuffix): self
    {
        $this->tableSuffix = $tableSuffix;

        return $this;
    }
}
