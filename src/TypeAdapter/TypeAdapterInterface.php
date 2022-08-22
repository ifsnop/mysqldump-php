<?php

namespace Druidfi\Mysqldump\TypeAdapter;

interface TypeAdapterInterface
{
    public function addDropDatabase(string $databaseName): string;
    public function addDropTrigger(string $triggerName): string;
    public function backupParameters(): string;
    public function commitTransaction(): string;
    public function createEvent(array $row): string;
    public function createFunction(array $row): string;
    public function createProcedure(array $row): string;
    public function createTable(array $row): string;
    public function createTrigger(array $row): string;
    public function createView(array $row): string;
    public function databases(string $databaseName): string;
    public function dropTable(string $tableName): string;
    public function dropView(string $viewName): string;
    public function endAddDisableKeys(string $tableName): string;
    public function endAddLockTable(string $tableName): string;
    public function endDisableAutocommit(): string;
    public function getDatabaseHeader(string $databaseName): string;
    public function getVersion(): string;
    public function lockTable(string $tableName): string;
    public function parseColumnType(array $colType): array;
    public function restoreParameters(): string;
    public function setupTransaction(): string;
    public function showColumns(string $tableName): string;
    public function showCreateEvent(string $eventName): string;
    public function showCreateFunction(string $functionName): string;
    public function showCreateProcedure(string $procedureName): string;
    public function showCreateTable(string $tableName): string;
    public function showCreateTrigger(string $triggerName): string;
    public function showCreateView(string $viewName): string;
    public function showEvents(string $databaseName): string;
    public function showFunctions(string $databaseName): string;
    public function showProcedures(string $databaseName): string;
    public function showTables(string $databaseName): string;
    public function showTriggers(string $databaseName): string;
    public function showViews(string $databaseName): string;
    public function startAddDisableKeys(string $tableName): string;
    public function startAddLockTable(string $tableName): string;
    public function startDisableAutocommit(): string;
    public function startTransaction(): string;
    public function unlockTable(string $tableName): string;
}
