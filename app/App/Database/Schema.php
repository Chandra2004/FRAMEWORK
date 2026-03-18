<?php

namespace TheFramework\App\Database;

use Closure;

/**
 * Schema builder — entry point for creating and modifying tables.
 */
class Schema
{
    /**
     * Create a new table on the schema.
     */
    public static function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        $sql = $blueprint->toSql();
        
        Database::getInstance()->unprepared($sql);
    }

    /**
     * Drop a table from the schema.
     */
    public static function drop(string $table): void
    {
        Database::getInstance()->unprepared("DROP TABLE IF EXISTS `{$table}`");
    }

    /**
     * Drop a table if it exists.
     */
    public static function dropIfExists(string $table): void
    {
        static::drop($table);
    }

    /**
     * Rename a table on the schema.
     */
    public static function rename(string $from, string $to): void
    {
        Database::getInstance()->unprepared("RENAME TABLE `{$from}` TO `{$to}`");
    }

    /**
     * Table presence check.
     */
    public static function hasTable(string $table): bool
    {
        return Database::getInstance()->tableExists($table);
    }
}
