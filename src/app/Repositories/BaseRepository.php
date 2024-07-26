<?php

/**
 * @author DoanLN
 * @copyright 2018-2019
 */

namespace Gomee\Repositories;

use BadMethodCallException;
use Gomee\Models\Model;
use Gomee\Models\MongoModel;
use Gomee\Models\SQLModel;
use Gomee\Services\Traits\Events;
use Gomee\Services\Traits\MagicMethods;

/**
 * danh sách method
 * @method $this select(...$columns) thêm các cột cần select
 * @method $this selectRaw($string) select dạng nguyen bản
 * @method $this from($table) 
 * @method $this fromRaw($string)
 * @method $this join(string $table, string $tableColumn, string $operator = '=', string $leftTableColumn) join vs 1 bang khac
 * @method $this leftJoin($table, $tableColumn, $operator, $leftTableColumn)
 * @method $this crossJoin($_ = null)
 * @method $this when(bool $condittion, \Clourse $callable) thêm điều kiện query
 * @method $this where($_ = null) truy vấn where
 * @method $this whereRaw($_ = null)
 * @method $this whereIn($column, $values = [])
 * @method $this whereNotIn($column, $values = [])
 * @method $this whereBetween($column, $values = [])
 * @method $this whereNotBetween($column, $values = [])
 * @method $this whereDay($_ = null)
 * @method $this whereMonth($_ = null)
 * @method $this whereYear($_ = null)
 * @method $this whereDate($_ = null)
 * @method $this whereTime($_ = null)
 * @method $this whereColumn($_ = null)
 * @method $this whereNull($column)
 * @method $this whereNotNull($column)
 * @method $this orWhere($_ = null)
 * @method $this orWhereRaw($_ = null)
 * @method $this orWhereIn($column, $values = [])
 * @method $this orWhereNotIn($column, $values = [])
 * @method $this orWhereBetween($column, $values = [])
 * @method $this orWhereNotBetween($column, $values = [])
 * @method $this orWhereDay($_ = null)
 * @method $this orWhereMonth($_ = null)
 * @method $this orWhereYear($_ = null)
 * @method $this orWhereDate($_ = null)
 * @method $this orWhereTime($_ = null)
 * @method $this orWhereColumn($leftColumn, $operator = '=', $rightColumn)
 * @method $this orWhereNull($column)
 * @method $this orWhereNotNull($column)
 * @method $this groupBy($column)
 * @method $this having($_ = null)
 * @method $this havingRaw($_ = null)
 * @method $this orderBy($_ = null)
 * @method $this orderByRaw($_ = null)
 * @method $this skip($_ = null)
 * @method $this take($_ = null)
 * @method $this with($_ = null)
 * @method $this withCount($_ = null)
 * @method $this load($_ = null)
 * @method $this distinct($_ = null)
 */

abstract class BaseRepository
{
    use BaseQuery, GettingAction, CRUDAction, FilterAction, OwnerAction, FileAction, DataAction, CacheAction, Events, MagicMethods;

    // tự động kiểm tra owner
    protected $checkOwner = true;

    protected $_primaryKeyName = MODEL_PRIMARY_KEY;
    /**
     * @var Model|SQLModel|MongoModel
     */
    protected $_model;

    /**
     * @var Model|SQLModel|MongoModel
     */
    static $__Model__;

    protected $modelType = 'default';

    /**
     * EloquentRepository constructor.
     */
    public function __construct()
    {
        $this->setModel();
        $this->_primaryKeyName = $this->_model->getKeyName();
        // $this->ownerInit();
        if ($this->required == MODEL_PRIMARY_KEY && $this->_primaryKeyName) {
            $this->required = $this->_primaryKeyName;
        }
        $this->modelType = $this->_model->__getModelType__();

        $this->ownerInit();
        $this->init();
        if (!$this->defaultValues) {
            $this->defaultValues = $this->_model->getDefaultValues();
        }
    }

    public function getKeyName()
    {
        return $this->_primaryKeyName;
    }




    /**
     * get model
     * @return string
     */
    abstract public function getModel();


    /**
     * chạy các lệnh thiết lập
     */
    protected function init()
    {
    }
    /**
     * Get one
     * @param int $id
     * @return \Gomee\Models\Model
     */
    final public function find($id)
    {
        $result = $this->_model->find($id);
        return $result;
    }

    /**
     * tạo một repository mới
     *
     * @return $this
     */
    public function mewRepo()
    {
        return new static();
    }

    /**
     * kiểm tra tồn tại
     *
     * @param string|int|float ...$args
     * @return bool
     */
    final public function exists(...$args)
    {
        $t = count($args);
        if ($t >= 2) {
            return $this->countBy(...$args) ? true : false;
        } elseif ($t == 1) {
            return $this->countBy($this->_primaryKeyName, $args[0]) ? true : false;
        }
        return false;
    }
    public static function checkExists($id)
    {
        return app(static::class)->exists($id);
    }



    /**
     * gọi hàm không dược khai báo từ trước
     *
     * @param string $method
     * @param array $params
     * @return static
     */
    public function __call($method, $params)
    {
        $f = array_key_exists($key = strtolower($method), $this->sqlclause) ? $this->sqlclause[$key] : null;
        if ($f) {
            if (!isset($this->actions) || !is_array($this->actions)) {
                $this->actions = [];
            }
            if ($f == 'groupby') {
                if (count($params) == 1 && is_string($params[0])) {
                    $params = array_map('trim', explode(',', $params[0]));
                }
                foreach ($params as $column) {
                    $this->actions[] = [
                        'method' => $method,
                        'params' => [$column]
                    ];
                }
            } else {
                $this->actions[] = compact('method', 'params');
            }

        } elseif (count($params)) {
            $value = $params[0];
            $fields = array_merge([$this->required], $this->getFields());

            // lấy theo tham số request (set where)
            if ($this->whereable && is_array($this->whereable) && (isset($this->whereable[$key]) || in_array($key, $this->whereable))) {
                if (isset($this->whereable[$key])) {
                    $this->where($this->whereable[$key], $value);
                } else {
                    $this->where($key, $value);
                }
            }
            // elseif($this->searchable && is_array($this->searchable) && (isset($this->searchable[$f]) || in_array($f, $this->searchable))){
            //     if(isset($this->searchable[$f])){
            //         $this->where($this->searchable[$f], $value);
            //     }else{
            //         $this->where($f, $value);
            //     }
            // }
            elseif (in_array($key, $fields)) {
                $this->where($key, $value);
                
            }
            elseif($this->_funcExists($method)){
                $this->_nonStaticCall($method, $params);
            }
            elseif (substr($method, 0, 2) == 'on' && strlen($event = substr($method, 2)) > 0 && ctype_upper(substr($event, 0, 1)) && count($params) && is_callable($params[0])) {
    
                $this->addEvent($event, $params[0]);
            }
        }elseif($this->_funcExists($method)){
            $this->_nonStaticCall($method, $params);
        }
        elseif (substr($method, 0, 2) == 'on' && strlen($event = substr($method, 2)) > 0 && ctype_upper(substr($event, 0, 1)) && count($params) && is_callable($params[0])) {

            $this->addEvent($event, $params[0]);
        }
        return $this;
    }
    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        return static::_staticCall($method, $parameters);
    }


}

BaseRepository::globalStaticFunc('on', '_on');
BaseRepository::globalFunc('on', 'addEvent');

