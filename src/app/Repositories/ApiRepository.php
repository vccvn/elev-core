<?php
namespace Gomee\Repositories;

use Gomee\Services\Traits\Events;
use Gomee\Services\Traits\MagicMethods;

/**
 * @author DoanLN
 * @copyright 2018-2019
 */
abstract class ApiRepository
{
    use EloquentQuery, CRUDAction, FilterAction, FileAction, CacheAction, Events, MagicMethods;
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $_model;

    /**
     * EloquentRepository constructor.
     */
    public function __construct()
    {
        $this->setModel();
        
        $this->init();
    }


    
    /**
     * get model
     * @return string
     */
    abstract public function getModel();

    
    /**
     * chạy các lệnh thiết lập;
     */
    protected function init()
    {
        
    }
    /**
     * Get one
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        $result = $this->_model->find($id);
        return $result;
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

ApiRepository::globalStaticFunc('on', '_on');
ApiRepository::globalFunc('on', 'addEvent');

