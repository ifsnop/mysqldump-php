<?php

namespace Druidfi\Mysqldump\TypeAdapter;

use PDO;

class TypeAdapterSqlite implements TypeAdapterInterface
{
    protected ?PDO $db = null;
    protected array $dumpSettings = [];

    public function __construct(?PDO $db = null, array $dumpSettings = [])
    {
        $this->db = $db;
        $this->dumpSettings = $dumpSettings;
    }

    /**
     * Add sql to create and use database
     */
    public function databases(string $databaseName): string
    {
        return "";
    }

    public function showCreateTable(string $tableName): string
    {
        return sprintf(
            "SELECT tbl_name as 'Table', sql as 'Create Table' ".
            "FROM sqlite_master ".
            "WHERE type='table' AND tbl_name='%s'",
            $tableName
        );
    }

    /**
     * Get table creation code from database
     */
    public function createTable($row): string
    {
        return "";
    }

    public function showCreateView(string $viewName): string
    {
        return sprintf(
            "SELECT tbl_name as 'View', sql as 'Create View' ".
            "FROM sqlite_master ".
            "WHERE type='view' AND tbl_name='%s'",
            $viewName
        );
    }

    /**
     * Get view creation code from database
     */
    public function createView($row): string
    {
        return "";
    }

    /**
     * Get trigger creation code from database
     */
    public function showCreateTrigger(string $triggerName): string
    {
        return "";
    }

    /**
     * Modify trigger code, add delimiters, etc
     */
    public function createTrigger(array $row): string
    {
        return "";
    }

    /**
     * Modify procedure code, add delimiters, etc
     */
    public function createProcedure(array $row): string
    {
        return "";
    }

    /**
     * Modify function code, add delimiters, etc
     */
    public function createFunction(array $row): string
    {
        return "";
    }

    public function showTables(string $databaseName): string
    {
        return "SELECT tbl_name FROM sqlite_master WHERE type='table'";
    }

    public function showViews(string $databaseName): string
    {
        return "SELECT tbl_name FROM sqlite_master WHERE type='view'";
    }

    public function showTriggers(string $databaseName): string
    {
        return "SELECT name FROM sqlite_master WHERE type='trigger'";
    }

    public function showColumns(string $tableName): string
    {
        return "pragma table_info($tableName)";
    }

    public function showProcedures(string $databaseName): string
    {
        return "";
    }

    public function showFunctions(string $databaseName): string
    {
        return "";
    }

    public function showEvents(string $databaseName): string
    {
        return "";
    }

    public function setupTransaction(): string
    {
        return "";
    }

    public function startTransaction(): string
    {
        return "BEGIN EXCLUSIVE";
    }

    public function commitTransaction(): string
    {
        return "COMMIT";
    }

    public function lockTable(string $tableName): string
    {
        return "";
    }

    public function unlockTable(string $tableName): string
    {
        return "";
    }

    public function startAddLockTable(string $tableName): string
    {
        return PHP_EOL;
    }

    public function endAddLockTable(string $tableName): string
    {
        return PHP_EOL;
    }

    public function startAddDisableKeys(string $tableName): string
    {
        return PHP_EOL;
    }

    public function endAddDisableKeys(string $tableName): string
    {
        return PHP_EOL;
    }

    public function addDropDatabase(string $databaseName): string
    {
        return PHP_EOL;
    }

    public function addDropTrigger(string $triggerName): string
    {
        return PHP_EOL;
    }

    public function dropTable(string $tableName): string
    {
        return PHP_EOL;
    }

    public function dropView(string $viewName): string
    {
        return PHP_EOL;
    }

    /**
     * Decode column metadata and fill info structure.
     * type, is_numeric and is_blob will always be available.
     *
     * @param array $colType Array returned from "SHOW COLUMNS FROM tableName"
     * @return array
     */
    public function parseColumnType(array $colType): array
    {
        return [];
    }

    public function backupParameters(): string
    {
        return PHP_EOL;
    }

    public function restoreParameters(): string
    {
        return PHP_EOL;
    }

    public function getDatabaseHeader(string $databaseName): string
    {
        return PHP_EOL;
    }
}
