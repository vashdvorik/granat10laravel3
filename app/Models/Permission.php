<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $guarded = [];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public static function generateFor(string $tableName): void
    {
        foreach (['browse', 'read', 'edit', 'add', 'delete'] as $action) {
            self::firstOrCreate([
                'key' => "{$action}_{$tableName}",
                'table_name' => $tableName,
            ]);
        }
    }

    public static function removeFrom(string $tableName): void
    {
        self::query()->where('table_name', $tableName)->delete();
    }
}
