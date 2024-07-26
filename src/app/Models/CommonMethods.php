<?php

namespace Gomee\Models;

use Carbon\Carbon;
use Gomee\Engines\Helper;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait CommonMethods
{

    protected $_meta = [];


    /**
     * @var array $jsonFields các cột dùng kiểu json
     */
    protected $jsonFields = [];

    /**
     * bỏ qua các cột khi parse data
     *
     * @var array
     */
    protected $ignoreParseData = [];
    protected function getIdAttribute() {
        return $this->attributes['id']??($this->original['id']??($this->attributes['uuid']??($this->original['uuid']??null)));
    }

    public function getIgnoreParse(){
        return $this->ignoreParseData && is_array($this->ignoreParseData)? $this->ignoreParseData : [];
    }

    /**
     * lấy các cột json
     *
     * @return array
     */
    public function getJsonFields()
    {
        return $this->jsonFields;
    }


    /**
     * lấy thời gian được định dạng
     *
     * @param string $format
     * @return string
     */
    public function dateFormat($format = null)
    {
        if (!$format) $format = 'H:i - d/m/Y';
        return $this->created_at?$this->created_at->format($format):'';
    }

    /**
     * lấy thời gian được định dạng
     * @param string $column
     * @param string $format
     * @return string
     */
    public function getDatetime($column = 'created_at', $format = null)
    {
        if (!$format) $format = 'Y-m-d H:i:s';
        return is_object($this->{$column})?$this->{$column}->format($format): ($this->{$column}?date($format, strtotime($this->{$column})):($this->created_at?$this->created_at->format($format):''));
    }


    /**
     * lấy thời gian cập nhật bản ghi
     *
     * @param string $format
     * @return string
     */
    public function updateTimeFormat($format = null)
    {
        if (!$format) $format = 'H:i - d/m/Y';
        return $this->updated_at?$this->updated_at->format($format):'';
    }

    /**
     * lay ten bang
     */
    public function __get_table()
    {
        return $this->table;
    }

    /**
     * lay danh sach cot
     */
    public function __get_fields()
    {
        return $this->fillable ? $this->fillable : [];
    }



    /**
     * kiem tra va set meta cho user
     * @return boolean
     */
    public function checkMeta()
    {
        if (!$this->_meta) {
            if ($this->metadatas && count($this->metadatas)) {
                $meta = [];
                foreach ($this->metadatas as $m) {
                    if (in_array($m->name, $this->jsonFields)) {
                        $value = json_decode($m->value, true);
                    } else {
                        $value = $m->value;
                    }

                    $meta[$m->name] = $value;
                }
                $this->_meta = $meta;
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * gán dự liệu meta cho dynamic
     * @return void
     */
    public function applyMeta()
    {
        $this->checkMeta();
        if ($this->_meta) {
            foreach ($this->_meta as $key => $value) {
                $val = $value;
                if (!is_array($val)) {
                    if (($id = str_replace('@mediaid:', '', $value)) != $value) {
                        if ($file = Helper::get_media_file([MODEL_PRIMARY_KEY => $id])) {
                            $val = $file->source;
                        } else {
                            $val = null;
                        }
                    }
                }
                $this->{$key} = $val;
            }
        }
    }
    /**
     * gán dự liệu meta cho dynamic
     * @return void
     */
    public function applyFormMeta()
    {
        $this->checkMeta();
        if ($this->_meta) {
            foreach ($this->_meta as $key => $value) {
                $val = $value;
                if (!is_array($val)) {
                    // if (($id = str_replace('@mediaid:', '', $value)) != $value) {
                    //     if ($file = Helper::get_media_file([MODEL_PRIMARY_KEY => $id])) {
                    //         $val = $file->source;
                    //     } else {
                    //         $val = null;
                    //     }
                    // }
                }
                $this->{$key} = $val;
            }
        }
    }

    public function getRela($rela)
    {
        $relations = $this->getRelations();
        return isset($relations[$rela]) ? $relations[$rela] : [];
    }


    /**
     * lay ra 1 hoac tat ca cac Thông tin trong bang user_meta
     * @param  string $meta_name ten cua meta can lay Thông tin
     * @return mixed             du lieu trong bang meta
     */
    public function meta($meta_name = null)
    {
        if (!$this->checkMeta()) return null;
        if (is_null($meta_name)) return $this->_meta;
        if (array_key_exists($meta_name, $this->_meta)) return $this->_meta[$meta_name];
        return null;
    }

    /**
     * lấy thuộc tính và trả về giá trị của tham số mặc định nếu ko tồn tại
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttr($name, $default = null)
    {
        if (!is_null($this->{$name})) return $this->{$name};
        return $default;
    }


    /**
     * lay du lieu de truyen toi form
     * @return array
     */
    public function toFormData()
    {
        $data = $this->toArray();

        return $data;
    }


    public function entityArray($array = [])
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->entityArray($value);
                } elseif (is_numeric($value)) {
                } elseif (is_string($value)) {
                    $array[$key] = htmlentities($value);
                }
            }
        }
        return $array;
    }

    public function toColumnData()
    {
        $data = $this->toArray();
        $data = $this->entityArray($data);
        return $data;
    }

    /**
     * lấy ra tất cả các thuộc tính dưới dạng mãng
     *
     * @return array
     */
    public function getAttrData()
    {
        return $this->attributesToArray();
    }





    //
    public function getShortDesc($length = null, $after = '...')
    {
        $desc = null;
        $trim = true;;
        if (isset($this->description) && $this->description) {
            $desc = $this->description;
        } elseif (isset($this->short_desc) && $this->short_desc) {
            return $this->short_desc;
        } elseif (isset($this->content) && $this->content) {
            $desc = $this->content;
        } elseif (isset($this->detail) && $this->detail) {
            $desc = $this->detail;
        }
        if ($trim) {
            if (!$length) $length = 120;
            $cnt = strip_tags(html_entity_decode($desc));
            if ($length < strlen($cnt)) {
                $a = explode(' ', str_limit(strip_tags($cnt), $length));
                $b = array_pop($a);
                return implode(' ', $a) . $after;
            } else {
                return strip_tags($desc);
            }
        }
        return $desc;
    }


    public function sub($column = null, $length = 0, $after = '')
    {
        if (is_string($column) && is_string($a = $this->{$column})) {
            $a = strip_tags($a);
            if (!$length || $length >= strlen($a)) return $a;
            $b = substr($a, 0, $length);
            $c = explode(' ', $b);
            $d = array_pop($c);
            $e = implode(' ', $c);
            $f = $e . $after;
        } else {
            $f = null;
        }
        return $f;
    }

    public function shortContent($length = null, $after = '...')
    {
        $desc = null;
        $trim = true;;
        if (isset($this->content) && $this->content) {
            $desc = $this->content;
        }
        if ($trim) {
            if (!$length) $length = 120;

            $cnt = strip_tags(html_entity_decode($desc));
            if ($length < strlen($cnt)) {
                $a = explode(' ', str_limit(strip_tags($cnt), $length));
                $b = array_pop($a);
                return implode(' ', $a) . $after;
            } else {
                return strip_tags($desc);
            }
        }
        return $desc;
    }



    /**
     * tinh thoi gian
     * toi uu sau
     */
    public function calculatorTime($date1 = null, $date2 = null)
    {
        if (!$date1) $date1 = 'created_at';
        $date = time();
        if ($this->{$date1}) {
            $date = $this->{$date1};
        } else {
            $date = Carbon::parse($date1);
        }
        if($date1 && !is_a($date2, Carbon::class)) $date2 = Carbon::parse($date2);
        if (!$date2) $date2 = Carbon::now();
        return $date->locale(config('app.locale'))->diffForHumans($date2); //1 giờ trước
    }
    public function calculator_time($date1 = null, $date2 = null)
    {
        return $this->calculatorTime($date1, $date2);
    }
    public function timeAgo($date1 = null, $date2 = null)
    {
        return $this->calculatorTime($date1, $date2);
    }

    public function getTimeAgo($unit = 'minute', $date1 = null, $date2 = null)
    {
        if (!is_string($unit) || !in_array($u = strtolower($unit), ['second', 'minute', 'hour', 'day', 'month', 'year'])) return 0;
        if (!$date1) $date1 = 'created_at';
        $date = time();
        if ($this->{$date1}) {
            $date = strtotime($this->{$date1});
        } else {
            $date = strtotime($date1);
        }
        if (!$date2) $date2 = Carbon::now()->toDateTimeString();
        $s = 1;
        $i = $s * 60;
        $h = $i * 60;
        $d = $h * 24;
        $m = $d * 30;
        $y = $d * 365;

        $diff = abs(strtotime($date2) - $date);
        $years = floor($diff / $y);
        if ($u == 'year')
            return $years;
        $months = floor(($diff - $years * $y) / ($m));
        if ($u == 'month')
            return $months;
        $days = floor(($diff - $years * $y - $months * $m) / $d);
        if ($u == 'day')
            return $days;
        $hours = floor(($diff - $years * $y - $months * $m - $days * $d) / $h);
        if ($u == 'hour')
            return $hours;
        $minutes = floor(($diff - $years * $y - $months * $m - $days * $d - $hours * $h) / $i);
        if ($u == 'minute')
            return $minutes;
        $seconds = floor(($diff - $years * $y - $months * $m - $days * $d - $hours * $h - $minutes * $i));
        return $seconds;
    }
}
