<?php

namespace Gomee\Tools\Office\Sheet;

// biến đổi model thành một object để tránh bị crack

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

use ReflectionClass;

abstract class Collection implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable
{

    private $isLock = false;
    protected $mask = '';

    /**
     * poarent
     *
     * @var Mask
     */
    protected $parent = null;

    /**
     * danh sach item
     *
     * @var Mask[]
     */
    protected $items = [];

    protected $itemMap = [];
    protected $total = 0;

    protected $paginator = null;

    protected $isPaginator = false;

    protected $accessAllowed = [
        'perPage', 'currentPage', 'lastPage', 'url', 'firstItem', 'linkCollection', 'nextPageUrl', 'path', 'previousPageUrl', 'lastItem'
    ];

    public function __construct($collection)
    {

        $this->items = $collection;
        $this->total = count($collection);
    }
    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }


    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    public function total()
    {
        return $this->total;
    }



    public function getItem($attr, $value = null)
    {
        if (!is_array($attr)) {
            if ($value === null) {
                if (array_key_exists($attr, $this->itemMap)) {
                    return $this->items[$this->itemMap[$attr]] ?? null;
                }
                return null;
            }
            foreach ($this->items as $item) {
                if ($item->{$attr} == $value) return $item;
            }

            return null;
        }
        if (count($attr)) {
            foreach ($this->items as $item) {
                $s = true;
                foreach ($attr as $key => $value) {
                    if ($item->{$key} != $value) $s = false;
                }
                if ($s) return $item;
            }
        }
        return null;
    }

    final public function getItems()
    {
        return $this->items;
    }
    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }


    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }

            return $value;
        }, $this->items);
    }



    public function toArrayData()
    {
        $data = [];
        if (count($this->items)) {
            foreach ($this->items as $key => $item) {
                $data[$key] = is_object($item) ? $item->toArray() : $item;
            }
        }
        return $data;
    }

    public function toArray()
    {
        return $this->toArrayData();
    }

    public function toDeepArray()
    {
        return array_map(function ($value) {
            if (is_a($value, static::class)) {
                return $value->toDeepArray();
            } elseif (is_object($value) && is_callable([$value, 'toDeepArray'])) {
                return $value->toArray();
            } elseif (is_object($value) && is_callable([$value, 'toArray'])) {
                return $value->toArray();
            }

            return $value;
        }, $this->toArray());
    }


    public function toJson($options = 0)
    {
        return json_encode(
            $this->toArray(),
            JSON_PRETTY_PRINT
        );
    }


    /**
     * set thuoc tinh cho toan bo item
     *
     * @param string|array $attr
     * @param mixed $value
     * @return $this
     */
    protected function set($attr, $value = null, $setEachModel = false)
    {
        if (is_array($attr)) $data = $attr;
        elseif (is_string($attr) || is_numeric($attr)) {
            $data = [$attr => $value];
        }
        array_map(function ($item) use ($data, $setEachModel) {
            $item->set($data, null, $setEachModel);
        }, $this->items);
        return $this;
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, $this->accessAllowed) && $this->isPaginator) {
            return call_user_func_array([$this->paginator, $name], $arguments);
        }
        return null;
    }
    public function __toString()
    {
        return $this->toJson();
    }
}
