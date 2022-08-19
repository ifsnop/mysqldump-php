<?php

namespace Ifsnop\Mysqldump\TypeAdapter;

interface TypeAdapterInterface
{
    public function add_drop_database(): string;
    public function add_drop_trigger(): string;
    public function backup_parameters(): string;
    public function commit_transaction(): string;
    public function create_function(string $functionName): string;
    public function create_procedure(string $procedureName): string;
    public function create_trigger(string $triggerName): string;
    public function create_view($row): string;
    public function databases(): string;
    public function drop_table(): string;
    public function drop_view(): string;
    public function end_add_disable_keys(): string;
    public function end_add_lock_table(): string;
    public function lock_table(): string;
    public function parseColumnType(array $colType): array;
    public function restore_parameters(): string;
    public function setup_transaction(): string;
    public function show_columns(): string;
    public function show_create_table(string $tableName): string;
    public function show_create_trigger(string $triggerName): string;
    public function show_create_view(string $viewName): string;
    public function show_events(): string;
    public function show_functions(): string;
    public function show_procedures(): string;
    public function show_tables(): string;
    public function show_triggers(): string;
    public function show_views(): string;
    public function start_add_disable_keys(): string;
    public function start_add_lock_table(): string;
    public function start_transaction(): string;
    public function unlock_table(): string;
}
