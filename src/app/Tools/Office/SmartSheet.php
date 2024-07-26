<?php

namespace Gomee\Tools\Office;

use Exception;
use Gomee\Files\Filemanager;
use Gomee\Helpers\Arr;
use Gomee\Services\Traits\Events;
use Gomee\Tools\Office\Sheet\Collection;
use Gomee\Tools\Office\Sheet\Row;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ReflectionClass;

class SmartSheet
{
    use Events;

    /**
     * class or callable
     *
     * @var string<Arr::class>|\Closure|string
     */
    protected $rowClass = Row::class;
    /**
     * class or callable
     *
     * @var string<Arr::class>|\Closure|string
     */
    protected $sheetClass = Collection::class;
    

    protected $returnDataType = 'array';
    protected $returnSheetDataType = 'array';


    /**
     * quản lý file
     *
     * @var Filemanager
     */
    protected $filemanager;

    protected $excel;

    /**
     * sheet
     *
     * @var Spreadsheet
     */
    protected $spreadsheet = null;

    public $keys = [];

    protected  $sheetActiveIndex = 0;


    protected $sheetOptions = [];

    // protected $keyMap = [];
    // protected $keyLabels = [];

    protected $tempOptions = [];

    protected $mode = 'object';

    public $filename = null;

    // public $template = null;

    public $exists  = null;

    protected $currentValueKeys = [
        'keys' => [],
        'chars' => []
    ];


    protected $originOptions = [];

    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function setCurrentFlipKeys($keys = [])
    {
        $this->currentValueKeys = [
            'keys' => [],
            'chars' => []
        ];

        foreach ($keys as $char => $slug) {
            $this->currentValueKeys['keys'][] = '{' . $slug . '}';
            $this->currentValueKeys['chars'][] = $char;
        }
        return $this;
    }

    public function init()
    {
        $this->filemanager = new Filemanager(storage_path('excel'));
        $this->keys = explode(' ', 'A B C D E F G H I J K L M N O P Q R S T U V W X Y Z');
        return $this;
    }


    /**
     * tao doi tượng excel
     *
     * @param string $filename
     * @param array|bool $options
     * @param bool $createIfNotExists
     */
    public function __construct($filename = null, $options = null, $createIfNotExists = false)
    {
        $this->setup($filename, $options, $createIfNotExists);
    }

    function setup($filename = null, $options = null, $createIfNotExists = false): static
    {
        $this->init();
        // nếu yêu cầu đọc file
        if (is_string($filename)) {
            // doc file
            $this->filename = $filename;
            $this->setWorkSheetFile($filename, $createIfNotExists ? $createIfNotExists : (is_bool($options) ? $options : false));

            if (is_array($options) && count($options) && isset($options[0]) && is_array($options[0])) {
                if ($this->mode == 'file' && $this->exists == 'created') {
                    $this->setMultiSheetOptions($options, $createIfNotExists);
                } else {
                    $this->setMultiSheetOptions($options);
                }
            } elseif (is_array($createIfNotExists) && count($createIfNotExists) && is_array($createIfNotExists[0])) {
                if ($this->mode == 'file' && $this->exists == 'created') {
                    $this->setMultiSheetOptions($createIfNotExists, is_bool($options) ? $options : false);
                } else {

                    $this->setMultiSheetOptions($createIfNotExists);
                }
            }
        } elseif (is_array($filename)) {
            $this->spreadsheet = new Spreadsheet();
            $this->setMultiSheetOptions($filename, $options);
        } else {
            $this->spreadsheet = new Spreadsheet();
        }

        if ($this->mode == 'file' && $this->exists == 'created') {
            $this->save($filename);
        }
        return $this;
    }

    public function setReturnRowDataType($classOrType = null)
    {
        if ($classOrType) {
            if (is_string($classOrType) && class_exists($classOrType)) {
                $this->rowClass = $classOrType;
                $this->returnDataType = 'object';
            } elseif (is_callable($classOrType)) {
                $this->rowClass = $classOrType;
                $this->returnDataType = 'callable';
            } elseif ($classOrType == 'xml') {
                $this->returnDataType = 'xml';
            } elseif ($classOrType == 'array') {
                $this->returnDataType = $classOrType;
            }
        }
        return $this;
    }


    public function setReturnSheetDataType($classOrType = null)
    {
        if ($classOrType) {
            if (is_string($classOrType) && class_exists($classOrType)) {
                $this->sheetClass = $classOrType;
                $this->returnSheetDataType = 'object';
            } elseif (is_callable($classOrType)) {
                $this->sheetClass = $classOrType;
                $this->returnSheetDataType = 'callable';
            } elseif ($classOrType == 'xml') {
                $this->returnSheetDataType = 'xml';
            } elseif ($classOrType == 'array') {
                $this->returnSheetDataType = $classOrType;
            }
        }
        return $this;
    }




    /**
     * thiệt lập thuộc tính cho sheet
     *
     * @param integer $sheetIndex
     * @param array $sheetOptions
     * @return bool
     */
    public function setSheetOptions($sheetIndex = null, $sheetOptions = [])
    {
        if ($sheetOptions) {
            if (is_null($sheetIndex)) $sheetIndex = $this->sheetActiveIndex;
            if (!($sheet = $this->getSheet($sheetIndex))) return false;
            if (!isset($this->sheetOptions[$sheetIndex])) $this->sheetOptions[$sheetIndex] = [];

            foreach ($sheetOptions as $key => $value) {
                if ($key == 'columns') {
                    $this->setSheetColumnKeyMap($sheetIndex, $value);
                } else {
                    if ($key == 'title') {
                        $sheet->setTitle($value);
                    }

                    $this->sheetOptions[$sheetIndex][$key] = $value;
                }
            }

            if ($this->mode != 'file' || ($this->mode == 'file' && $this->exists == 'created')) {

                $this->setHeader($sheetIndex);
            } elseif ($this->mode == 'file' && $this->exists != 'created' && isset($sheetOptions['key_row'])) {

                $this->setKeyMapFromSheet($sheetIndex, $sheetOptions['key_row']);
            }
            if (isset($sheetOptions['data'])) {

                $this->setSheetData($sheetIndex, $sheetOptions['data']);
            }
            return true;
        }
        return false;
    }

    /**
     * set option cho nhiều sheet
     *
     * @param array $options
     * @param bool $createIfNotExists
     * @return void
     */
    public function setMultiSheetOptions($options = [], $createIfNotExists = false)
    {
        if (is_array($options) && count($options)) {
            $this->originOptions = $options;
            if (is_array($options[0])) {
                foreach ($options as $sheetIndex => $optionData) {
                    if ($sheetIndex >= $this->spreadsheet->getSheetCount()) {
                        if (!$createIfNotExists) return $this;
                        $this->spreadsheet->createSheet($sheetIndex);
                    }
                    $this->setSheetOptions($sheetIndex, $optionData);
                }
            } else {
                $this->setSheetOptions(null, $options);
            }
        }
        return $this;
    }

    /**
     * set options cho nhiều sheet
     *
     * @param array $options
     * @param boolean $createIfNotExists
     * @return static
     */
    public function setOptions($options = [], $createIfNotExists = false)
    {
        return $this->setMultiSheetOptions($options, $createIfNotExists);
    }

    /**
     * set sheet tu file
     *
     * @param string $filename
     * @param bool $createIfNotExists
     * @return static
     */
    public function setWorkSheetFile($filename = null, $createIfNotExists = false)
    {
        $file = file_exists($filename) ? $filename : $this->filemanager->parseFilenameByType($filename, 'xlsx');
        $this->filename = $filename;
        if (file_exists($file)) {
            $spreadsheet = IOFactory::load($file);
            $this->spreadsheet = $spreadsheet;
            $this->mode = 'file';
        } elseif ($createIfNotExists) {
            $this->spreadsheet = new Spreadsheet();
            $this->mode = 'file';
            $this->exists = 'created';
        } else {
            $this->spreadsheet = new Spreadsheet();
        }
        return $this;
    }

    /**
     * lay noi dung file
     *
     * @param string $filename
     * @return Spreadsheet|null
     */
    public function open($filename)
    {
        $file = file_exists($filename) ? $filename : $this->filemanager->parseFilenameByType($filename, 'xlsx');
        if (file_exists($file)) {
            // die($file);
            $spreadsheet = IOFactory::load($file);
            return $spreadsheet;
        }
        return null;
    }
    /**
     * lay noi dung file
     *
     * @param string $filename
     * @return bool
     */
    public function load($filename = null, $options = [])
    {
        if (!$filename) $filename = $this->filename;

        if ($spreadsheet = $this->open($filename)) {
            $this->spreadsheet = $spreadsheet;
            if ($options) {
                $this->setOptions($options);
            }
            return true;
        }
        return false;
    }


    /**
     * chẹn dữ liệu vào sheet
     *
     * @param int $sheetIndex
     * @param array $data
     * @param array $options
     * @return void
     */
    public function setSheetData($sheetIndex = null, $data = [], $options = [])
    {
        if (!($workSheet = $this->getSheet($sheetIndex))) return false;
        // $this->setHeader($options);
        $this->addRows($sheetIndex, $data);
        return $this->spreadsheet;
    }


    /**
     * trả về giá trị công thức liên quan các ô
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    public function parseFuncValue($row, $value = null)
    {
        $a = str_replace($this->currentValueKeys['keys'], $this->currentValueKeys['chars'], $value);
        return str_replace(['{row}', '{before}', '{after}'], [$row, $row - 1, $row + 1], $a);
    }

    /**
     * chẹn dữ liệu vào sheet
     *
     * @param int $sheetIndex
     * @param array $data
     * @param array $options
     * @return void
     */
    public function appendSheetData($sheetIndex = null, $data = [], $options = [])
    {
        if (!($workSheet = $this->getSheet($sheetIndex))) return false;
        $start = $workSheet->getHighestDataRow() + 1;
        $this->addRows($sheetIndex, $data, [], $start);
        return $this->spreadsheet;
    }


    /**
     * thêm dữ liệu cho bảng
     *
     * @param int $sheetIndex
     * @param array $data
     * @param array $keys
     * @param int $start
     * @return bool
     */
    public function addRows($sheetIndex = null, $data = [], $keys = [], $start = null)
    {
        if (!($workSheet = $this->getSheet($sheetIndex))) return false;
        $sheetIndex = $this->parseIndex($sheetIndex);
        $options = $this->getSheetOptions($sheetIndex);
        if (!$keys) {
            // lấy ra mng3 key và vị trí bắt đầu
            if (isset($this->tempOptions[$sheetIndex])) {
                $keys = $this->tempOptions[$sheetIndex]['keys'];
                if (is_null($start)) {
                    $start = $this->tempOptions[$sheetIndex]['start'];
                }
            }
            if (!$keys) {
                $keys = $options['key_map'] ?? [];
                $key_row = $options['key_row'] ?? 0;
            }
        }
        if (is_null($start)) {
            $start = isset($options['data_row_start']) ? $options['data_row_start'] : $key_row + 1;
        }
        // nếu có data và là mảng hoạc onject
        if ($data && (is_array($data) || is_callable($data)) || is_object($data)) {
            if (is_callable($data)) {
                if (!($d = $data())) {
                    return false;
                }
                $data = $d;
            }
            if (isset($data[0])) {
                $dataType = 'string';
                if (is_object($data[0])) $dataType = 'object';
                elseif (is_array($data[0])) $dataType = 'array';
            } else $dataType = 'key';
            if ($dataType == 'key') {
                if ($keys) {
                    $this->setCurrentFlipKeys($keys);
                    foreach ($keys as $pos => $key) {
                        $workSheet->setCellValue($pos . $start, $this->parseFuncValue($start, $data[$key] ?? null));
                    }
                } else {
                    $i = 0;
                    foreach ($data as $key => $value) {
                        $k = $this->getCharKey($i);
                        $workSheet->setCellValue($k . $start, $value);
                        $i++;
                    }
                }
            } elseif ($dataType == 'string') {

                $i = 0;
                foreach ($data as $key => $value) {
                    $k = $this->getCharKey($i);
                    $workSheet->setCellValue($k . $start, $value);
                    $i++;
                }
            } else {
                $row = $start;
                if ($keys) {
                    $this->setCurrentFlipKeys($keys);
                    foreach ($data as $index => $d) {
                        if (method_exists($this, 'beforeSetRowData')) {
                            if (is_array($r = $this->beforeSetRowData($sheetIndex, $d, $row))) {
                                foreach ($keys as $pos => $key) {
                                    $workSheet->setCellValue(
                                        $pos . $row,
                                        $this->parseFuncValue($row, $r[$key] ?? null)
                                    );
                                }
                            }
                        } else {
                            foreach ($keys as $pos => $key) {
                                $workSheet->setCellValue(
                                    $pos . $row,
                                    $this->parseFuncValue($row, $dataType == 'object' ? ($d->{$key} ?? null) : ($d[$key] ?? null))

                                );
                            }
                        }



                        $row++;
                    }
                } else {
                    foreach ($data as $index => $ob) {

                        if (method_exists($this, 'beforeSetRowData')) {
                            if (is_array($r = $this->beforeSetRowData($sheetIndex, $ob, $row))) {
                                $i = 0;
                                foreach ($r as $key => $value) {
                                    $k = $this->getCharKey($i);
                                    $workSheet->setCellValue($k . $row, $value);
                                    $i++;
                                }
                            }
                        } else {
                            $i = 0;
                            foreach ($ob as $key => $value) {
                                $k = $this->getCharKey($i);
                                $workSheet->setCellValue($k . $row, $value);
                                $i++;
                            }
                        }


                        $row++;
                    }
                }
            }
        }
        return true;
    }

    /**
     * set value cho cell cua mot sheet
     *
     * @param mixed $sheetIndex
     * @param string $sheetPosition
     * @param mixed $value
     * @return static
     */
    public function setCellValue($sheetIndex = null, $sheetPosition = 'A1', $value = null)
    {
        if (!($workSheet = $this->getSheet($sheetIndex))) return $this;
        $workSheet->setCellValue($sheetPosition, $value);
        return $this;
    }

    public function updateSheetData($sheetIndex = null, $where = [], $rowData = [])
    {
        if (!($workSheet = $this->getSheet($sheetIndex))) return false;
        $start = null;
        $options = $this->getSheetOptions($sheetIndex);
        if (isset($this->tempOptions[$sheetIndex])) {
            $keys = $this->tempOptions[$sheetIndex]['keys'];
            if (is_null($start)) {
                $start = $this->tempOptions[$sheetIndex]['start'];
            }
        }
        if (!$keys) {
            $keys = $options['key_map'] ?? [];
            $key_row = $options['key_row'] ?? 0;
        }
        if (is_null($start)) {
            $start = isset($options['data_row_start']) ? $options['data_row_start'] : $key_row + 1;
        }
        $flipKeys = array_flip($keys);
        foreach ($where as $key => $value) {
            if (!isset($flipKeys[$key])) return false;
        }
        $sheetData = $workSheet->toArray(null, true, true, true);
        $length = count($sheetData);
        for ($i = 0; $i <= $length; $i++) {
            $row = $sheetData[$i];
            $s = true;
            $n = 0;
            foreach ($where as $key => $value) {
                $n++;
                $k = $flipKeys[$key];
                if (!isset($row[$k])) {
                    $s = false;
                    continue 2;
                } else {
                    $v = $row[$k];
                    if (is_array($value)) {
                        if (!in_array($v, $value)) {
                            $s = false;
                            continue 2;
                        }
                    } elseif ($v != $value) {
                        $s = false;
                        continue 2;
                    }
                }
            }
            if ($s && $n) {
                if (method_exists($this, 'beforeSetRowData')) {
                    if (is_array($row = $this->beforeSetRowData($sheetIndex, $rowData))) {
                        foreach ($row as $key => $value) {
                            if (isset($flipKeys[$key])) continue;
                            $workSheet->setCellValue($flipKeys[$key] . $i, $value);
                        }
                    }
                } else {
                    foreach ($rowData as $key => $value) {
                        if (isset($flipKeys[$key])) continue;
                        $workSheet->setCellValue($flipKeys[$key] . $i, $value);
                    }
                }
            }
        }
        return true;
    }

    /**
     * thiết lập header
     *
     * @param int $sheetIndex
     * @param array $options
     * @return bool
     */
    public function setHeader($sheetIndex = null, $options = [])
    {
        if (!($workSheet = $this->getSheet($sheetIndex))) return false;
        $sheetIndex = $this->parseIndex($sheetIndex);
        $options = array_merge($this->getSheetOptions($sheetIndex), $options);
        $option = new Arr($options);
        $i = 0;
        $keys = [];
        $labels = [];
        $start = 1;
        // nếu dược set header
        if (($option->columns && is_array($option->columns)) || ($option->key_map && is_array($option->key_map)) || ($option->header && is_array($option->header))) {
            $type = $option->header_type;

            // duyệt qua lấy mảng key values
            if ($option->header) {
                $header = $option->header;
                if (isset($header['columns']) && is_array($header['columns'])) {
                    $columns = $header['columns'];
                } else {
                    $columns = [];
                }
                if (isset($header['type'])) {
                    $type = $header['type'];
                }
            } elseif ($option->columns) {
                $columns = $option->columns;
                // $type = $option->header_type;
            } else {
                $keys = $option->key_map;
                if ($option->labels) {
                    $labels = $option->labels;
                }
            }
            // nếu có columns và chưa có key
            if (!$keys && $columns) {
                $j = 0;
                foreach ($columns as $key => $label) {
                    $k = $this->getCharKey($j);
                    $keys[$k] = $key;
                    $labels[$k] = $label;
                    $j++;
                }
            }

            if ($keys && $type && in_array($type, ['key', 'label', 'both', 0, 1, 2])) {
                $j = 0;
                $starts = [
                    'key' => 2,
                    'label' => 2,
                    'both' => 3,
                    0 => 2,
                    1 => 2,
                    2 => 3
                ];
                $start = $starts[$type];
                $kk = 2;
                $kl = 1;
                foreach ($keys as $char => $key) {
                    if (in_array($type, ['label', 1, 'both', 2]) && isset($labels[$char])) {
                        $workSheet->setCellValue($char . '1', $labels[$char]);
                        if (in_array($type, ['both', 2])) {
                            $workSheet->setCellValue($char . '2', $key);
                        }
                    } elseif (in_array($type, ['key', 0])) {
                        $workSheet->setCellValue($char . '1', $key);
                    }
                }
            }
        }
        $this->tempOptions[$sheetIndex] = compact('start', 'keys', 'labels');
        return true;
    }


    /**
     * lưu file
     *
     * @param string $filename
     * @param string $ext
     * @return string
     */
    public function save($filename = null, $ext =  'xlsx')
    {
        if (!$filename && $this->mode == 'file') $filename = $this->filename;

        $xlsx = new Xlsx($this->spreadsheet);
        // $op = array_merge((array) $options, ['data' => $data]);
        $file = $this->filemanager->parseFilenameByType($filename, $ext);
        try {
            $xlsx->save($file);
            return $file;
        } catch (Exception $error) {
        }
        return null;
    }

    /**
     * thiết lập keymap
     *
     * @param integer $sheetIndex
     * @param array $keyMap
     * @return static
     */
    public function setSheetColumnKeyMap($sheetIndex = 0, $keyMap = [])
    {
        $sheetIndex = $this->parseIndex($sheetIndex);
        if (is_array($keyMap)) {
            $map = [];
            $keyLabels = [];
            $n = 0;
            foreach ($keyMap as $key => $value) {
                if (is_numeric($key)) {
                    $map[$this->getCharKey($key)] = $value;
                } else {
                    $k = $this->getCharKey($n);
                    $map[$k] = $key;
                    $keyLabels[$k] = $value;
                }
                $n++;
            }

            if (!isset($this->sheetOptions[$sheetIndex])) {
                $this->sheetOptions[$sheetIndex] = [];
            }
            $this->sheetOptions[$sheetIndex]['key_map'] = $map;
            if ($keyLabels) {
                $this->sheetOptions[$sheetIndex]['labels'] = $keyLabels;
                // $this->sheetOptions[$sheetIndex]['key_row'] = 2;
            }
            if (!isset($this->sheetOptions[$sheetIndex]['labels'])) {
                // $this->sheetOptions[$sheetIndex]['key_row'] = 1;
            }
        }
        return $this;
    }


    /**
     * thiết lập keymap
     *
     * @param integer $sheetIndex
     * @param array $keyMap
     * @return static
     */
    public function setSheetKeyMap($sheetIndex = 0, $keyMap = [])
    {
        if (is_array($keyMap)) {
            $map = [];
            $keyLabels = [];
            $n = 0;
            foreach ($keyMap as $key => $value) {
                if (is_numeric($key)) {
                    $map[$this->getCharKey($key)] = $value;
                } elseif (in_array($k = strtoupper($key), $this->keys)) {

                    $k = $this->getCharKey($n);
                    $map[$k] = $key;
                    $keyLabels[$k] = $value;
                }
                $n++;
            }

            if (!isset($this->sheetOptions[$sheetIndex])) {
                $this->sheetOptions[$sheetIndex] = [];
            }
            $this->sheetOptions[$sheetIndex]['key_map'] = $map;
            if ($keyLabels) {
                $this->sheetOptions[$sheetIndex]['labels'] = $keyLabels;
            }
            if (!isset($this->sheetOptions[$sheetIndex]['labels'])) {
                $this->sheetOptions[$sheetIndex]['key_row'] = 1;
            }
        }
        return $this;
    }


    /**
     * thiết lập option cho sheet
     *
     * @param int $sheetIndex
     * @return array
     */
    public function getSheetOptions($sheetIndex = null)
    {
        if (is_string($sheetIndex) && !is_numeric($sheetIndex)) $sheetIndex = $this->getIndexByName($sheetIndex);
        if (is_null($sheetIndex)) $sheetIndex = $this->sheetActiveIndex;
        return isset($this->sheetOptions[$sheetIndex]) ? $this->sheetOptions[$sheetIndex] : [];
    }
    /**
     * thiết lập key map từ sheet
     *
     * @param int $sheetIndex
     * @param integer $key_row
     * @return bool
     */
    public function setKeyMapFromSheet($sheetIndex = null, $key_row = 1)
    {
        if (!($workSheet = $this->getSheet($sheetIndex))) return false;
        $sheetIndex = $this->getIndexByName($workSheet->getTitle());
        $data = $workSheet->toArray(null, true, true, true);
        if (isset($data[$key_row])) {
            if (!isset($this->sheetOptions[$sheetIndex])) $this->sheetOptions[$sheetIndex] = [];
            $this->sheetOptions[$sheetIndex]['key_map'] = $data[$key_row];
            $this->sheetOptions[$sheetIndex]['key_row'] = $key_row;
            return true;
        }
        return false;
    }


    /**
     * get sheet
     *
     * @param int $sheetIndex
     * @return WorkSheet|null|false
     */
    public function getSheet($sheetIndex = null)
    {
        if (is_object($sheetIndex) && is_a($sheetIndex, Worksheet::class)) return $sheetIndex;
        $sheetIndex = $this->parseIndex($sheetIndex);
        $a = $this->spreadsheet->getSheetCount();
        if ($sheetIndex > $a - 1) return false;
        $workSheet = $this->spreadsheet->getSheet($sheetIndex);
        return $workSheet;
    }

    public function getIndexByName($name)
    {
        $list = $this->spreadsheet->getAllSheets();
        $worksheetCount = count($list);
        for ($i = 0; $i < $worksheetCount; ++$i) {
            if ($list[$i]->getTitle() === trim($name, "'")) {
                return $i;
            }
        }
        return null;
    }

    /**
     * lấy vè index sheet
     *
     * @param string|int $sheetIndex
     * @return int
     */
    public function parseIndex($sheetIndex = null)
    {
        if (!is_numeric($sheetIndex)) {
            if (is_null($sheetIndex)) $sheetIndex = $this->sheetActiveIndex;
            elseif (is_string($sheetIndex)) $sheetIndex = $this->getIndexByName($sheetIndex);
            else $sheetIndex = $this->spreadsheet->getSheetCount() + 1;
        }
        return $sheetIndex;
    }

    /**
     * set active
     *
     * @param int $sheetIndex
     * @return static
     */
    public function setActiveSheet($sheetIndex = 0)
    {
        $sheetIndex = $this->parseIndex($sheetIndex);
        if ($sheetIndex > $this->spreadsheet->getSheetCount() - 1) return false;
        // $workSheet = $this->spreadsheet->getSheet($sheetIndex);
        $this->spreadsheet->setActiveSheetIndex($sheetIndex);
        $this->sheetActiveIndex = $sheetIndex;
        return $this;
    }

    public function setTitle($sheetIndex = null, $title = null)
    {
        if (!$title) {
            if (is_string($sheetIndex)) {
                $sheet = $this->getSheet();
                $sheet->setTitle($sheetIndex);
                return true;
            }
        }
        if (!($sheet = $this->getSheet($sheetIndex))) return false;
        $sheet->setTitle($title);
        return true;
    }


    public function resetSheet($sheetIndex = null)
    {
        $sheetIndex = $this->parseIndex($sheetIndex);
        $this->spreadsheet->removeSheetByIndex($sheetIndex);
        $workSheet = $this->spreadsheet->createSheet($sheetIndex);
        if (isset($this->sheetOptions[$sheetIndex])) {
            $this->setSheetOptions($sheetIndex, $this->sheetOptions[$sheetIndex]);
            $this->setHeader($sheetIndex);
            if (isset($this->sheetOptions[$sheetIndex]['title'])) {
                $workSheet->setTitle($this->sheetOptions[$sheetIndex]['title']);
            }
        }
    }
    /**
     * lấy vị dòng bắt đầu đọc dữ liệu
     *
     * @param int $sheetIndex
     * @return int
     */
    public function getDataRowStart($sheetIndex = null)
    {
        if (!is_null($sheetIndex)) {
            $a = $this->spreadsheet->getSheetCount();
            if ($sheetIndex > $a - 1) return 1;
            $i = $sheetIndex;
        } else {
            $i = $this->sheetActiveIndex;
        }
        if (!isset($this->sheetOptions[$i]['data_row_start']) || !isset($this->sheetOptions[$i]['data_row_start'])) return $this->sheetOptions[$i]['data_row_start'];

        if (!isset($this->sheetOptions[$i]) ||  !isset($this->sheetOptions[$i]['key_row'])) return 1;
        return $this->sheetOptions[$i]['key_row'] + 1;
    }

    /**
     * lấy dữ liệu từ một sheet
     *
     * @param int|null $sheetIndex
     * @param array $options
     * @return array<array>|Row[]|Collection<Row>
     */
    public function getSheetData($sheetIndex = null, $options = [])
    {
        if (!($workSheet = $this->getSheet($sheetIndex))) return [];
        $sheetIndex = $this->parseIndex($sheetIndex);
        $options = array_merge($this->getSheetOptions($sheetIndex), $options);

        $sheetData = $workSheet->toArray(null, true, true, true);
        if ($sheetData && $options) {
            $columns = [];
            $select = [];
            $opt = new Arr($options);
            $mapby = null;
            if ($opt->key_map) {
                $columns = $opt->key_map;
                if ($opt->key_row) {
                    $key_row = $opt->key_row;
                } else {
                    $key_row = 1;
                }
                if ($opt->data_row_start) {
                    //
                    $start = $opt->data_row_start;
                } else if (isset($sheetData[$key_row])) {
                    $start = $key_row + 1;
                }
            }

            if ($opt->select) {
                if (is_array($opt->select)) {
                    $select = $opt->select;
                } elseif (is_string($opt->select)) {
                    $select = array_map('trim', explode(',', $opt->select));
                }
            }
            if (count($columns)) {
                if ($opt->mapby && in_array($opt->mapby, $columns)) {
                    $mapby = $opt->mapby;
                }

                $length = count($sheetData);
                $data = [];
                for ($i = $start; $i <= $length; $i++) {
                    $d = [];
                    // chay qua list chỉ lấy dữ liệu trong keymap
                    foreach ($sheetData[$i] as $key => $value) {
                        if (isset($columns[$key]) && (!$select || in_array($columns[$key], $select))) {
                            $d[$columns[$key]] = $value;
                        }
                    }
                    if (!$d) continue;

                    if ($mapby && isset($d[$mapby])) {
                        $data[$d[$mapby]] = $d;
                    } else {
                        $data[] = $d;
                    }
                }

                if ($this->returnDataType != 'array') {
                    $arrayData = [];
                    if ($this->returnDataType == 'object') {
                        foreach ($data as $key => $value) {
                            $rc = new ReflectionClass($this->rowClass);
                            $d = $rc->newInstanceArgs([$value]);
                            $arrayData[$key] = $d;
                        }
                        if($this->returnSheetDataType == "object"){
                            $rc = new ReflectionClass($this->sheetClass);
                            $d = $rc->newInstanceArgs([$arrayData]);
                            $arrayData = $d;
                        }
                    }
                    elseif ($this->returnDataType == 'callable') {
                        foreach ($data as $key => $value) {
                            $d = call_user_func_array($this->rowClass, [$value]);
                            $arrayData[$key] = $d;
                        }
                    }
                    elseif ($this->returnDataType == 'xml') {
                        foreach ($data as $key => $value) {
                            $d = '<row>';
                            foreach ($value as $k => $v) {
                                $sk = str_slug($k);
                                $d .= "<$sk>$v</$sk>"; 
                            }
                            $d.= '</row>';
                        }
                        $arrayData = "<collection>". implode('', $arrayData) . "</collection>";
                    }else{
                        $arrayData = $data;
                    }
                    return $arrayData;
                }

                return $data;
            }
        }
        return $sheetData;
    }

    public function toArray()
    {
        $data = [];
        $list = $this->spreadsheet->getAllSheets();
        foreach ($list as $workSheet) {
            $data[] = $workSheet->toArray(null, true, true, true);
        }
        return $data;
    }

    /**
     * lấy chữ cái
     *
     * @param integer $index
     * @return string
     */
    public function getCharKey($index = 0)
    {
        $t = count($this->keys);
        $s = '';
        do {
            if ($index >= $t) {
                $s .= $this->keys[0];
            } else {
                $s .= $this->keys[$index];
            }
            $index -= $t;
        } while ($index >= $t);
        return $s;
    }

    public function setSpreadsheet(Spreadsheet $spreadsheet, $options = [], $createIfNotExists = false)
    {
        $this->spreadsheet = $spreadsheet;
        $this->setOptions($options, $createIfNotExists);
        return $this;
    }


    public static function template($filename, $options = [])
    {
        $excel = new static();
        if ($spreadsheet = $excel->open($filename)) {
            $excel->exists = 'template';
            $excel->setSpreadsheet($spreadsheet, $options);
        }
        return $excel;
    }

    public function __call($method, $arguments)
    {
        return null;
    }
}
