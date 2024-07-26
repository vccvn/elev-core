<?php

namespace Illuminate\Support\Facades;

use Illuminate\Database\Schema\Blueprint;
class Schema_{
    function create($table, $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        Schema::$tables[$table] = $blueprint;
    }
}
class Schema {
    public static $tables = [];

    public static function create($table, $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        static::$tables[$table] = $blueprint;
    }

    public static function table($table, $callback)
    {
        if (array_key_exists($table, static::$tables)) {
            $blueprint = new Blueprint($table);
            $callback($blueprint);
            $a = $blueprint->getConfig(true);
            static::$tables[$table]->data = array_merge(static::$tables[$table]->data, $blueprint->data);
            static::$tables[$table]->config = array_merge(static::$tables[$table]->config, $blueprint->config);
            
        }
    }

    public static function connection(...$args){
        return new Schema_(...$args);
    }

    /**
     * xóa bảng
     *
     * @param string $table
     * @return void
     */
    public static function dropIfExists($table)
    {
        # code...
    }

    public static function hasTable($table)
    {
        return array_key_exists($table, static::$tables);
    }

    /**
     * get schema
     *
     * @param string $table
     * @return Blueprint
     */
    public static function get($table)
    {
        return array_key_exists($table, static::$tables) ? static::$tables[$table] : (new Blueprint($table));
    }
}