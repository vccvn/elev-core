<?php

namespace Gomee\Tools\Office\Sheet;

// biến đổi model thành một object để tránh bị crack

use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

use ReflectionClass;

/**
 * Row of sheet
 * @property mixed $A
 * @property mixed $B
 * @property mixed $C
 * @property mixed $D
 * @property mixed $E
 * @property mixed $F
 * @property mixed $G
 * @property mixed $H
 * @property mixed $I
 * @property mixed $J
 * @property mixed $K
 * @property mixed $L
 * @property mixed $M
 * @property mixed $N
 * @property mixed $O
 * @property mixed $P
 * @property mixed $Q
 * @property mixed $R
 * @property mixed $S
 * @property mixed $T
 * @property mixed $U
 * @property mixed $V
 * @property mixed $W
 * @property mixed $X
 * @property mixed $Y
 * @property mixed $Z
 */
abstract class Row implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable
{

    protected $total = 0;

    protected $data = [];

    protected $keyMap = [];
    protected $charMap = [];

    public function __construct($data)
    {
        $this->setup($data);
    }
    /**
     * Undocumented function
     *
     * @param array $data
     * @param array $map
     * @return void
     */
    public function setup($data = [])
    {

        if (!is_array($data))
            return;
        $this->data = $data;
        $this->total = count($data);
        if ($data) {
            $i = 0;
            $list = explode(' ', 'A B C D E F G H I J K L M N O P R S T U V W X Y Z');
            foreach ($data as $key => $value) {

                $this->keyMap[$list[$i]] = $key;
                $i++;
            }
        }
    }
    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }


    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    public function total()
    {
        return $this->total;
    }


    protected function parseKey($key): string
    {
        if (in_array($key, $this->keyMap))
            return $key;
        if (array_key_exists($k = strtoupper($key), $this->keyMap))
            return $this->keyMao[$k];
        return false;
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        if (!($key = $this->parseKey($key))) return false;
        return array_key_exists($key, $this->data);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        if (!($key = $this->parseKey($key))) return null;
        return $this->data[$key];
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
            $this->data[] = $value;
        } else {
            if (($key = $this->parseKey($key)))
                $this->data[$key] = $value;
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
        if ($key = $this->parseKey($key))
            unset($this->data[$key]);
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
        if (count($this->data)) {
            foreach ($this->data as $key => $item) {
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

    public function __get($key){
        return $this->offsetGet($key);
    }

    public function __call($name, $arguments)
    {

        return null;
    }
    public function __toString()
    {
        return $this->toJson();
    }
}
