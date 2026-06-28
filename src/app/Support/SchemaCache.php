<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SchemaCache
{
    public static function hasColumn(string $table, string $column): bool
    {
        return (bool) Cache::rememberForever("schema:column:{$table}:{$column}", fn (): bool => Schema::hasColumn($table, $column));
    }

    public static function hasTable(string $table): bool
    {
        return (bool) Cache::rememberForever("schema:table:{$table}", fn (): bool => Schema::hasTable($table));
    }
}
