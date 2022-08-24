<?php

namespace Druidfi\Mysqldump;

use Druidfi\Mysqldump\Compress\CompressManagerFactory;
use Exception;

class DumpSettings
{
    // List of available connection strings.
    const UTF8    = 'utf8';
    const UTF8MB4 = 'utf8mb4';

    private static array $defaults = [
        'include-tables' => [],
        'exclude-tables' => [],
        'include-views' => [],
        'compress' => 'None',
        'init_commands' => [],
        'no-data' => [],
        'if-not-exists' => false,
        'reset-auto-increment' => false,
        'add-drop-database' => false,
        'add-drop-table' => false,
        'add-drop-trigger' => true,
        'add-locks' => true,
        'complete-insert' => false,
        'databases' => false,
        'default-character-set' => self::UTF8,
        'disable-keys' => true,
        'extended-insert' => true,
        'events' => false,
        'hex-blob' => true, /* faster than escaped content */
        'insert-ignore' => false,
        'net_buffer_length' => 1000000,
        'no-autocommit' => true,
        'no-create-info' => false,
        'lock-tables' => true,
        'routines' => false,
        'single-transaction' => true,
        'skip-triggers' => false,
        'skip-tz-utc' => false,
        'skip-comments' => false,
        'skip-dump-date' => false,
        'skip-definer' => false,
        'where' => '',
        /* deprecated */
        'disable-foreign-keys-check' => true
    ];
    private array $settings;

    /**
     * @throws Exception
     */
    public function __construct(array $settings)
    {
        $this->settings = array_replace_recursive(self::$defaults, $settings);

        $this->settings['init_commands'][] = "SET NAMES " . $this->get('default-character-set');

        if (false === $this->settings['skip-tz-utc']) {
            $this->settings['init_commands'][] = "SET TIME_ZONE='+00:00'";
        }

        $diff = array_diff(array_keys($this->settings), array_keys(self::$defaults));

        if (count($diff) > 0) {
            throw new Exception("Unexpected value in dumpSettings: (" . implode(",", $diff) . ")");
        }

        if (!is_array($this->settings['include-tables']) || !is_array($this->settings['exclude-tables'])) {
            throw new Exception('Include-tables and exclude-tables should be arrays');
        }

        // If no include-views is passed in, dump the same views as tables, mimic mysqldump behaviour.
        if (!isset($settings['include-views'])) {
            $this->settings['include-views'] = $this->settings['include-tables'];
        }
    }

    public function getCompressMethod(): string
    {
        return $this->settings['compress'] ?? CompressManagerFactory::NONE;
    }

    public function getDefaultCharacterSet(): string
    {
        return $this->settings['default-character-set'];
    }

    public static function getDefaults(): array
    {
        return self::$defaults;
    }

    public function getExcludedTables(): array
    {
        return $this->settings['exclude-tables'] ?? [];
    }

    public function getIncludedTables(): array
    {
        return $this->settings['include-tables'] ?? [];
    }

    public function setIncludedTables(array $tables): void
    {
        $this->settings['include-tables'] = $tables;
    }

    public function getIncludedViews(): array
    {
        return $this->settings['include-views'] ?? [];
    }

    public function getInitCommands(): array
    {
        return $this->settings['init_commands'] ?? [];
    }

    public function getNetBufferLength(): int
    {
        return $this->settings['net_buffer_length'];
    }

    public function getNoData(): array
    {
        return $this->settings['no-data'] ?? [];
    }

    public function isEnabled(string $option): bool
    {
        return isset($this->settings[$option]) && $this->settings[$option] === true;
    }

    public function setCompleteInsert(bool $value = true)
    {
        $this->settings['complete-insert'] = $value;
    }

    public function skipComments(): bool
    {
        return $this->isEnabled('skip-comments');
    }

    public function skipDefiner(): bool
    {
        return $this->isEnabled('skip-definer');
    }

    public function skipDumpDate(): bool
    {
        return $this->isEnabled('skip-dump-date');
    }

    public function skipTriggers(): bool
    {
        return $this->isEnabled('skip-triggers');
    }

    public function skipTzUtc(): bool
    {
        return $this->isEnabled('skip-tz-utc');
    }

    public function get(string $option): string
    {
        return (string) $this->settings[$option];
    }
}
