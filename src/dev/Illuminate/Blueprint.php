<?php

namespace Illuminate\Database\Schema;

use Arr;

class BlueprintDataConfig
{
    public $data = [];

    protected $indexes = [];
    protected $column = '';
    /**
     * table
     *
     * @var Blueprint
     */
    protected $table = null;
    public function __construct($table, $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function getData()
    {
        return $this->data;
    }


    public function __call($name, $params)
    {
        $sk = strtolower($name);
        if($sk == 'index'){
            if(count($params)){
                if(is_array($params[0])){
                    $this->indexes = array_merge($this->indexes, $params[0]);
                }else{
                    $this->indexes = array_merge($this->indexes, $params);   
                }

            }
            
            return $this;
        }
        elseif(in_array($name, ['primary', 'foreign', 'references', 'on', 'onUpdate', 'onDelete'])) return $this;
        $this->data[$name] = $params[0]??true;
        $this->table->config[$this->column][$name] = $params[0]??true;
        return $this;
    }

}


class Blueprint
{
    public $data = [];

    public $config = [];

    
    public function __construct()
    {
        # code...
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * lấy data config
     *
     * @param boolean $toArrObject
     * @return array<string, Arr>
     */
    public function getConfig($toArrObject = false)
    {
        return $toArrObject?array_map(function($value){
            return new Arr($value);
        }, $this->config):  $this->config;
    }

    public function getColumns()
    {
        return array_keys($this->data);
    }

    public function __call($name, $params)
    {
        if (isset($params[0]) && $params[0] && !in_array($name, ['increment', 'bigIncrements', 'foreign', 'index', 'primary', 'foreign', 'references', 'on', 'onUpdate', 'onDelete'])) {
            if ($name == 'decimal') $name = 'float';
            elseif ($name == 'json') $name = 'array';
            elseif ($name == 'text' || $name == 'longText' || $name == 'tinyText' || $name == 'uuid' || $name == 'timestamp' || $name == 'date' || $name == 'datetime' || $name == 'time') $name = 'string';
            elseif ($name == 'bigInteger' || $name == 'tinyInteger') $name = 'integer';

            $this->data[$params[0]] = $name;
            if(!array_key_exists($params[0], $this->config)){
                $this->config[$params[0]] = [
                    'name' => $params[0],
                    'type' => $name
                ];
            }else{
                $this->config[$params[0]]['type'] = $name;
            }
            return new BlueprintDataConfig($this, $params[0]);
        }

        return (new static());
    }
    public function __toString()
    {
        // haha
        return "[\n    '" . implode("',\n    '", $this->data) . "'\n]";
    }
}
