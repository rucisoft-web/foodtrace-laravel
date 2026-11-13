<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    private static array $cache = [];

    public static function get(string $key, $default = null)
    {
        if (! array_key_exists($key, self::$cache)) {
            try {
                self::$cache[$key] = static::find($key)?->value;
            } catch (\Throwable) {
                self::$cache[$key] = null;
            }
        }

        return self::$cache[$key] ?? $default;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache[$key] = $value;
    }
}
