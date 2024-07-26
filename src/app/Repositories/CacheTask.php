<?php

namespace Gomee\Repositories;

use Gomee\Engines\CacheEngine;
use Gomee\Masks\Mask;
use Gomee\Masks\MaskCollection;
use Gomee\Models\Model;

/**
 * danh sách method
 * @method $this select(...$columns)
 * @method $this selectRaw($string)
 * @method $this from($table)
 * @method $this fromRaw($string)
 * @method $this join(\Cloure $callback)
 * @method $this join(string $table, string $tableColumn, string $operator, string $leftTableColumn)
 * @method $this leftJoin($table, $tableColumn, $operator, $leftTableColumn)
 * @method $this crossJoin($_ = null)
 * @method $this when($_ = null)
 * @method $this where($_ = null)
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
 * @method $this groupBy($column)
 * @method $this having($_ = null)
 * @method $this havingRaw($_ = null)
 * @method $this orderBy($_ = null)
 * @method $this orderByRaw($_ = null)
 * @method $this skip($_ = null)
 * @method $this take($_ = null)
 * @method $this with($_ = null)
 * @method $this load($_ = null)
 * @method $this union($_ = null)
 * @method $this unionAll($_ = null)
 * @method MaskCollection filter(\Illuminate\Http\Request $request, array $args)
 * @method Mask detail(\Illuminate\Http\Request $request, array $args)
 * @method Model[] get(array $args)
 * @method Model[] getBy(array $args)
 * @method Model find($id)
 * @method Model findBy(string $column, mixed $value)
 * @method Model first(array $args)
 * @method int count(array $args)
 * @method $this trashed(boolean|numeric $status) set trang thai lay du lieu
 * @method $this notTrashed() set trang thai lay du lieu chua xoa
 */

class CacheTask
{
    /**
     * doi tuong repository
     *
     * @var static
     */
    protected $repository;

    /**
     * khóa để truy cập cache
     *
     * @var string
     */
    protected $key = null;
    /**
     * tham số
     *
     * @var array
     */
    protected $params = [];
    /**
     * expired time (minute)
     *
     * @var integer
     */
    protected $time = 0;

    /**
     * các phương thúc lấy dữ liệu
     *
     * @var array
     */
    protected static $getDataMethods = [
        'get' => 'get', 'getby' => 'getBy', 'findby' => 'findBy', 'first' => 'first', 'count' => 'count',
        'countby' => 'coumtBy', 'getresults' => 'getResults', 'detail' => 'detail', 'getdata' => 'getData'
    ];


    /**
     * khoi tạo task
     *
     * @param static|ApiRepository $repository
     * @param string $key
     * @param integer $time
     * @param array $params
     */
    public function __construct($repository, $key = null, $time = 0, $params = [])
    {
        $this->repository = $repository;
        $this->key = $key;
        $this->time = $time;
        $this->params = $params;
    }

    /**
     * lấy key đúng chuẩn
     *
     * @return string
     */
    protected function getKey()
    {
        return 'repository-' . (static::class). '-'. $this->repository->getTable() . '-' . $this->key;
    }
    /**
     * truy cập phần tử trong repository
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->repository->{$name};
    }


    /**
     * thêm tham số cho repository
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->repository->{$name} = $value;
    }

    /**
     * gọi các phương thức get data hoặc repository
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $dataMethods = $this->repository->getCacheMethods();
        // nếu tên phương thức trùng với một giá trị nào đó trong mảng các phương thúc lấy dữ liệu
        // của repository hiện tại thì gọi phương thức lấy dữ liệu cache
        if(in_array($name, $dataMethods)){
            return $this->getCache($name, $arguments);
        }
        // tương tự diều kiệm trên nhưng kiểm tra key, nhằm giảm độ khó trong việc viết hoa viết thường
        elseif(array_key_exists($key = strtolower($name), $dataMethods)){
            return $this->getCache($dataMethods[$key], $arguments);
        }
        // nếu tên phương thức trùng với một giá trị nào đó trong mảng các phương thúc lấy dữ liệu
        // của base repository mặc định thì gọi phương thức lấy dữ liệu cache
        elseif(in_array($name, static::$getDataMethods)){
            return $this->getCache($name, $arguments);
        }
        // tương tự diều kiệm trên nhưng kiểm tra key, nhằm giảm độ khó trong việc viết hoa viết thường
        elseif(array_key_exists($key = strtolower($name), static::$getDataMethods)){
            return $this->getCache(static::$getDataMethods[$key], $arguments);
        }
        // nếu không thuộc 2 trường hợp trên thì gọi đến các phương thức trong repository
        call_user_func_array([$this->repository, $name], $arguments);
        return $this;
        
    }
    /**
     * lấy cache hoặc dử liệu mới
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function getCache($method, array $arguments=[])
    {
        $time = $this->time ? $this->time : 0;
        // dump(system_setting());
        if(!$time){
            return call_user_func_array([$this->repository, $method], $arguments);
        }
        $key = $this->getKey();
        if(!($data = CacheEngine::get($key, $params = array_merge($this->params, $arguments)))){
            $data = call_user_func_array([$this->repository, $method], $arguments);
            CacheEngine::set($key, $data, $time, $params);
        }
        return $data;
    }
    
}
