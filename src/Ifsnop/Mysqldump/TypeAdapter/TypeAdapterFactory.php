<?php

namespace Ifsnop\Mysqldump\TypeAdapter;

use Exception;
use PDO;

/**
 * TypeAdapter Factory.
 */
abstract class TypeAdapterFactory
{
    protected ?PDO $dbHandler = null;
    protected array $dumpSettings = [];

    /**
     * @param string $c Type of database factory to create (Mysql, Sqlite,...)
     * @param ?PDO $dbHandler
     * @param array $dumpSettings
     * @return mixed
     * @throws Exception
     */
    public static function create(string $c, ?PDO $dbHandler = null, array $dumpSettings = []): TypeAdapterInterface
    {
        $c = ucfirst(strtolower($c));

        if (!TypeAdapter::isValid($c)) {
            throw new Exception("Database type support for ($c) not yet available");
        }

        $adapterClass = __NAMESPACE__."\\"."TypeAdapter".$c;

        return new $adapterClass($dbHandler, $dumpSettings);
    }

    public function __construct(?PDO $dbHandler = null, array $dumpSettings = [])
    {
        $this->dbHandler = $dbHandler;
        $this->dumpSettings = $dumpSettings;
    }

    /**
     * function databases Add sql to create and use database
     * @todo make it do something with sqlite
     */
    public function databases(): string
    {
        return "";
    }

    public function show_create_table($tableName): string
    {
        return "SELECT tbl_name as 'Table', sql as 'Create Table' ".
            "FROM sqlite_master ".
            "WHERE type='table' AND tbl_name='$tableName'";
    }

    /**
     * function create_table Get table creation code from database
     * @todo make it do something with sqlite
     */
    public function create_table($row): string
    {
        return "";
    }

    public function show_create_view($viewName): string
    {
        return "SELECT tbl_name as 'View', sql as 'Create View' ".
            "FROM sqlite_master ".
            "WHERE type='view' AND tbl_name='$viewName'";
    }

    /**
     * function create_view Get view creation code from database
     * @todo make it do something with sqlite
     */
    public function create_view($row): string
    {
        return "";
    }

    /**
     * function show_create_trigger Get trigger creation code from database
     * @todo make it do something with sqlite
     */
    public function show_create_trigger($triggerName): string
    {
        return "";
    }

    /**
     * function create_trigger Modify trigger code, add delimiters, etc
     * @todo make it do something with sqlite
     */
    public function create_trigger($triggerName): string
    {
        return "";
    }

    /**
     * function create_procedure Modify procedure code, add delimiters, etc
     * @todo make it do something with sqlite
     */
    public function create_procedure($procedureName): string
    {
        return "";
    }

    /**
     * function create_function Modify function code, add delimiters, etc
     * @todo make it do something with sqlite
     */
    public function create_function($functionName): string
    {
        return "";
    }

    public function show_tables(): string
    {
        return "SELECT tbl_name FROM sqlite_master WHERE type='table'";
    }

    public function show_views(): string
    {
        return "SELECT tbl_name FROM sqlite_master WHERE type='view'";
    }

    public function show_triggers(): string
    {
        return "SELECT name FROM sqlite_master WHERE type='trigger'";
    }

    public function show_columns(): string
    {
        if (func_num_args() != 1) {
            return "";
        }

        $args = func_get_args();

        return "pragma table_info(${args[0]})";
    }

    public function show_procedures(): string
    {
        return "";
    }

    public function show_functions(): string
    {
        return "";
    }

    public function show_events(): string
    {
        return "";
    }

    public function setup_transaction(): string
    {
        return "";
    }

    public function start_transaction(): string
    {
        return "BEGIN EXCLUSIVE";
    }

    public function commit_transaction(): string
    {
        return "COMMIT";
    }

    public function lock_table(): string
    {
        return "";
    }

    public function unlock_table(): string
    {
        return "";
    }

    public function start_add_lock_table(): string
    {
        return PHP_EOL;
    }

    public function end_add_lock_table(): string
    {
        return PHP_EOL;
    }

    public function start_add_disable_keys(): string
    {
        return PHP_EOL;
    }

    public function end_add_disable_keys(): string
    {
        return PHP_EOL;
    }

    public function start_disable_foreign_keys_check(): string
    {
        return PHP_EOL;
    }

    public function end_disable_foreign_keys_check(): string
    {
        return PHP_EOL;
    }

    public function add_drop_database(): string
    {
        return PHP_EOL;
    }

    public function add_drop_trigger(): string
    {
        return PHP_EOL;
    }

    public function drop_table(): string
    {
        return PHP_EOL;
    }

    public function drop_view(): string
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

    public function backup_parameters(): string
    {
        return PHP_EOL;
    }

    public function restore_parameters(): string
    {
        return PHP_EOL;
    }
}
