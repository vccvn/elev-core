<?php

use Illuminate\Support\Facades\Schema;


function get_args_params($args = [])
{
    $data = [
        'args' => [],
        'params' => []
    ];
    if (is_array($args)) {
        $oldIsParams = false;
        $oldParamKey = null;
        $oldHasValue = false;
        $i = 0;
        foreach ($args as $key => $param) {
            if (substr($param, 0, 2) == '--') {
                $oldIsParams = true;
                $p = substr($param, 2);
                $pc = explode('=', $p);


                $f = strtolower(array_shift($pc));

                $pk = $p;
                $pv = true;
                if (count($pc) > 0) {
                    $pk = $f;
                    $pv = implode('=', $pc);
                    $oldHasValue = true;
                } elseif (count($pn = explode(':', $p)) > 1 && $f2 = strtolower(array_shift($pn))) {
                    $pk = $f2;
                    $pv = implode(':', $pn);
                    $oldHasValue = true;
                } else {
                    $oldHasValue = false;
                    $pv = true;
                }

                $oldParamKey = $pk;

                if (array_key_exists($pk, $data['params'])) {
                    if ($pv == true) {
                        // test
                    } elseif ($data['params'][$pk] == true) {
                        $data['params'][$pk] = $pv;
                    } elseif (is_array($data['params'][$pk])) {
                        $data['params'][$pk][] = $pv;
                    } else {
                        $data['params'][$pk] = [$data['params'][$pk]];
                        $data['params'][$pk][] = $pv;
                    }
                } else {
                    $data['params'][$pk] = $pv;
                }
            } elseif (!$oldHasValue && $oldIsParams) {
                if ($data['params'][$oldParamKey] === true) {
                    $data['params'][$oldParamKey] = $param;
                } elseif (is_array($data['params'][$oldParamKey])) {
                    $data['params'][$oldParamKey][] = $param;
                } else {
                    $data['params'][$oldParamKey] = [$data['params'][$oldParamKey]];
                    $data['params'][$oldParamKey][] = $param;
                }
                // print_r($data);

                $oldIsParams = false;
                $oldHasValue = false;
                $oldParamKey = null;
            } else {
                $data['args'][] = $param;
                $oldHasValue = false;
                $oldIsParams = false;
                $oldParamKey = null;
            }


            $i++;
        }
    }
    return $data;
}


function make_command($args = [], $command = null, ...$params)
{
    if (!$command) {
        echo "Tham so:\n\t\$command -- leng65 lệnh\n\t...\$params -- danh sách tham số\n\n";
        return null;
    }

    if (!preg_match('/^[A-z_]+[A-z0-9_]*$/', $command)) {
        echo 'Command không được chứa ký tự đặt biệt';
        return null;
    }
    if (function_exists($command)) {
        echo 'Command Đã tồn tại';
        return null;
    }
    $args = array_map(function ($value) {
        return '$' . str_replace('/[^A-z0-9_\=\'\"\s\[\]]/i', '_', trim($value));
    }, $params);
    $find = ['Command', '$args'];
    $replace = [$command, implode(', ', $args)];
    $template = file_get_contents(DEVPATH . '/templates/command.php');
    $code = str_replace($find, $replace, $template);
    $filemanager = new Filemanager();
    $filemanager->setDir((DEVPATH . '/commands/'));
    if ($a = $filemanager->save($command . '.php', $code, 'php')) {
        echo "Tạo $command thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
    } else {
        echo "Lỗi không xác định\n";
    }
}


if (!function_exists('make_controller')) {
    /**
     * make_controller
     * 
     */
    function make_controller($args = [], $type = 'client', $name = null, $repo = null, $title = null, $module = null)
    {
        if (!$name) {
            echo "Tham so:\n\t\$type -- loai controller (web, admin, manager, api, custom)\n\t\$name -- Ten controller\n\t\$repo -- ten class Repository/Model\n\t\$title -- ten/tieu de\n\t\$module -- js module && route module\n\n";
            return null;
        }
        $folders = [
            'client' => 'Clients',
            'cms' => 'CMS',
            'admin' => 'Admin',
            'account' => 'Accounts',
            'manager' => 'Manager',
            'branch' => 'Branch',
            'cpanel' => 'CPanel',
            'backend' => 'Backend',
            'private' => 'Private',
            'public' => 'Public',
            'protected' => 'Protected',
            'publish' => 'Publish',
            'api' => 'Apis',
            'web' => 'Web',
            'frontend' => 'Frontend',
            'merchant' => 'Merchant',
            'sub' => 'SubSystem',
            'sub-system' => 'SubSystem',
            'custom' => null
        ];
        $ac = explode('/', str_replace("\\", "/", $name));
        $name = array_pop($ac);
        $te = explode('.', $type);
        $subSystem = null;
        $subName = null;
        if (count($te) > 1) {
            $type = $te[0];

            $subSystem = $te[1] .'.';
            $subName = ($te[0] == 'ai'? 'AI': ucfirst($te[1])) . "\\";
        }
        if (!array_key_exists($t = strtolower($type), $folders) || !$name) return null;
        $s = implode('/', array_map('ucfirst', $ac));
        $folder = $folders[$t] . ($s ? '/' . $s : '');
        $master = ucfirst($t);
        $prectr = $master;
        if ($master) {
            $prectr = $folders[$t] . "\\" . $master;
        }
        $sub = null;
        if ($folder) {
            $folder = '/' . trim($folder, '/');
            $sub = str_replace("/", "\\", $folder);
        }
        if (!$repo) $repo = $name;
        $repos = explode('/', str_replace("\\", "/", $repo));
        $repo = ucfirst(array_pop($repos));
        $repf = count($repos) ? implode('/', array_map('ucfirst', $repos)) : ucfirst(Str::plural($repo));

        if (!$title) $title = $name;
        if (!$module) $module = strtolower(Str::plural($name));

        $find = ['NAME', 'MASTER', 'SUB', 'REPO', 'REPF', 'MODULE', 'TITLE', 'PRECTRL', '#use controller;', 'SSNN'];
        $replace = [$name, $master, $sub, $repo, $repf, $module, $title, $prectr, $s ? '' : '# ', $subName];

        $template = file_get_contents(DEVPATH . '/templates/controller.php');
        $code = str_replace($find, $replace, $template);
        $filemanager = new Filemanager();
        $filemanager->setDir((BASEDIR . '/app/Http/Controllers' . $folder . '/'));
        if ($a = $filemanager->save($name . 'Controller.php', $code, 'php')) {
            echo "Tạo {$name}Controller thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        } else {
            echo "Lỗi không xác định\n";
        }
    }
}

function make_dto($args = [], $name = null, $model = null, $table = null)
{
    if (!$name) {
        echo "Tham so:\n\$name -- Ten Repository\n\$model -- Tên model\n";
        return null;
    }
    $names = explode('/', str_replace("\\", "/", $name));
    $name = ucfirst(array_pop($names));
    $folder = count($names) ? implode('/', array_map('ucfirst', $names)) : ucfirst(Str::plural($name));

    if (!$model) $model = $name;
    $table = $table ? $table : Str::tableName($name);
    $find =    ['NAME', 'MODEL', 'FOLDER', 'PROPERTIES',           'ACCESSIBLE'];
    $replace = [$name,  $model,  $folder,  getProperties($table),  getFields($table, true)];

    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/dto.php');
    $filemanager->setDir(base_path('app/DTOs/' . $folder . '/'));
    $code = str_replace($find, $replace, $template);
    if ($a = $filemanager->save($name . 'DTO.php', $code, 'php')) {
        echo "Tạo {$name}DTO thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
    } else {
        echo "Lỗi không xác định\n";
    }
}

function make_repository($args = [], $name = null, $model = null)
{
    if (!$name) {
        echo "Tham so:\n\$name -- Ten Repository\n\$model -- Tên model\n";
        return null;
    }
    $names = explode('/', str_replace("\\", "/", $name));
    $name = ucfirst(array_pop($names));
    $folder = count($names) ? implode('/', array_map('ucfirst', $names)) : ucfirst(Str::plural($name));

    if (!$model) $model = $name;
    $find =    ['NAME', 'MODEL', 'FOLDER'];
    $replace = [$name,  $model,  $folder];


    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/repository.php');
    $filemanager->setDir(base_path('app/Repositories/' . $folder . '/'));
    $code = str_replace($find, $replace, $template);
    if ($a = $filemanager->save($name . 'Repository.php', $code, 'php')) {
        echo "Tạo {$name}Repository thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
    } else {
        echo "Lỗi không xác định\n";
    }
}

function make_validator($args = [], $name = null, $table = null)
{
    if (!$name) {
        echo "Tham so:\n\$name -- Ten Validator\n\$table -- Tên bảng";
        return null;
    }
    $names = explode('/', str_replace("\\", "/", $name));
    $name = ucfirst(array_pop($names));
    $folder = count($names) ? implode('/', array_map('ucfirst', $names)) : ucfirst(Str::plural($name));

    if (!$table) $table = Str::tableName($name);

    $find = ['NAME', 'FOLDER', '$RULES', '$MESSAGES'];
    $replace = [$name, $folder, getRules($table), getMessages($table)];
    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/validator.php');
    $filemanager->setDir(base_path('app/Validators/' . $folder . '/'));
    $code = str_replace($find, $replace, $template);
    if ($a = $filemanager->save($name . 'Validator.php', $code, 'php')) {
        echo "Tạo {$name}Validator thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
    } else {
        echo "Lỗi không xác định\n";
    }
}



function make_engine($args = [], $name = null)
{
    if (!$name) {
        echo "Tham so:\n\$name -- Ten Engine";
        return null;
    }
    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/engine.php');
    $filemanager->setDir(base_path('app/Engines/'));
    $find = ['NAME'];
    $replace = [$name];
    $code = str_replace($find, $replace, $template);
    if ($a = $filemanager->save($name . 'Engine.php', $code, 'php')) {
        echo "Tạo {$name}Engine thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
    } else {
        echo "Lỗi không xác định\n";
    }
}


function make_model($args = [], $name = null, $table = null)
{
    if (!$name) {
        echo "Tham so:\n\$name -- Ten Model\n\$table -- Tên bảng\n...\$args -- tham số\n";
        return null;
    }

    $names = explode('/', str_replace("\\", "/", $name));
    $name = ucfirst(array_pop($names));
    $folder = count($names) ? implode('/', array_map('ucfirst', $names)) : ucfirst(Str::plural($name));

    if (!$table) $table = Str::tableName($name);

    $find = ['NAME', 'TABLE', 'FILLABLE', '//PROPS', 'MODEL_TYPE', 'PROPERTIES'];
    $props = [];
    $MODELtYPE = '';


    $params = $args;
    if ((isset($params['softdelete']) && $params['softdelete'] != 'false') || (isset($params['softDelete']) && $params['softDelete'] != 'false')) {
        $props[] = "protected \$deleteMode = 'soft';";
    }
    if (isset($params['timestamps']) && $params['timestamps'] == 'false') {
        $props[] = "public \$timestamps = false;";
    }

    $hasPK = false;
    $hasKT = false;
    if (isset($params['primaryKey']) && $params['primaryKey']) {
        $props[] = "protected \$primaryKey = '$params[primaryKey]';";
        $hasPK = true;
    } elseif (isset($params['primarykey']) && $params['primarykey']) {
        $props[] = "protected \$primaryKey = '$params[primarykey]';";
        $hasPK = true;
    } elseif (isset($params['pk']) && $params['pk']) {
        $props[] = "protected \$primaryKey = '$params[py]';";
        $hasPK = true;
    }

    if (isset($params['keyType']) && $params['keyType']) {
        $props[] = "protected \$keyType = '$params[keyType]';";
        $hasKT = true;
    } elseif (isset($params['keytype']) && $params['keytype']) {
        $props[] = "protected \$keyType = '$params[keytype]';";
        $hasKT = true;
    } elseif (isset($params['kt']) && $params['kt']) {
        $props[] = "protected \$keyType = '$params[kt]';";
        $hasKT = true;
    }



    if (isset($params['useuuid']) || isset($params['useUuid']) || isset($params['uuid'])) {

        $v = isset($params['useuuid']) ? $params['useuuid'] : (isset($params['useUuid']) ? $params['useUuid'] : (isset($params['uuid']) ? $params['uuid'] : true));
        $props[] = "public \$useUuid = " . ($v === true ? "true" : ($v === 'false' ? 'false' : ($v === 'true' || $v == '' ? 'true' : ($v == 'primary' || $v == 'id' ? "'primary'" : "'$v'"))
        )
        ) . ";";

        if ($v === 'true' || $v == true) {
            if (!$hasPK) $props[] = "protected \$primaryKey = 'uuid';";
            if (!$hasKT) $props[] = "protected \$keyType = 'string';";
        }
    }

    $connection = false;

    if (isset($params['connection']) && $params['connection']) {
        $props[] = "protected \$connection = '$params[connection]';";
        $connection = true;
        if ($params['connection'] == 'mongodb') {
            $props[] = "protected \$collection = '$table';";
        }
        $MODELtYPE = 'Mongo';
    }

    $mt = strtolower(isset($params['modeltype']) && $params['modeltype'] ? $params['modeltype'] : (isset($params['modelType']) && $params['modelType'] ? $params['modelType'] : ''));
    if ($mt) {
        if ($mt == 'mongo') {
            if (!$connection) {
                // $props[] = "protected \$connection = 'mongodb';";
                $props[] = "protected \$collection = '$table';";
                $MODELtYPE = 'Mongo';
            }
        } elseif ($mt == 'sql') {
            // $props[] = "protected \$connection = 'sql';";
            $MODELtYPE = 'SQL';
        }
    }
    if ((isset($params['defaultvalue']) && $params['defaultvalue'] != 'false') || (isset($params['defaultvalues']) && $params['defaultvalues'] != 'false')) {
        $d = "protected \$defaultValues = [\n        ";
        $columns = getColumns($table);
        $d .= implode(",\n        ", array_map(function ($c) {
            return "'$c' => ''";
        }, $columns));
        $d .= "\n    ];";

        $props[] = $d;
    }





    $replace = [$name, $table, getFields($table, true), implode("\n    ", $props), $MODELtYPE, getProperties($table)];
    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/model.php');
    $filemanager->setDir(base_path('app/Models/'));
    $code = str_replace($find, $replace, $template);
    if ($a = $filemanager->save($name . '.php', $code, 'php')) {
        echo "Tạo {$name} thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
    } else {
        echo "Lỗi không xác định\n";
    }
}





if (!function_exists('make_mask')) {
    /**
     * make_mask
     * 
     */
    function make_mask($args = [], $name = null, $model = null, $table = null, $make_collection = null)
    {
        if (!$name) {
            echo "Tham so:\n\t\$name (required): Ten mask (nên sử dụng [Folder]/[name])\n\t\$model (option): Tên Model\n\t\$make_collection (option): có tạo collection hay ko";
            return null;
        }

        $names = explode('/', str_replace("\\", "/", $name));
        $name = ucfirst(array_pop($names));
        if (!$model) {
            $model = $name;
        }
        $folder = count($names) ? implode('/', array_map('ucfirst', $names)) : ucfirst(Str::plural($name));
        $sub = null;
        if ($folder) {
            $folder = '/' . trim($folder, '/');
            $sub = str_replace("/", "\\", $folder);
        }

        $table = $table?$table: Str::tableName($model);

        $find = ['NAME', 'MODEL', 'SUB', 'PROPERTIES'];

        $replace = [$name, $model, $sub, getProperties($table)];

        $template = file_get_contents(DEVPATH . '/templates/mask.php');
        $code = str_replace($find, $replace, $template);
        $filemanager = new Filemanager();
        $filemanager->setDir((BASEDIR . '/app/Masks' . $folder . '/'));
        if ($a = $filemanager->save($name . 'Mask.php', $code, 'php')) {
            echo "Tạo {$name}Mask thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        } else {
            echo "Lỗi không xác định\n";
        }
        if (!in_array($make_collection, ['-n', '--n', '-no', 'no', 'k', 'khong', 'đéo'])) {
            $find[] = 'MASK';
            $replace[] = $name;
            make_mask_collection_file($name, $folder, $find, $replace);
        }
    }
}


if (!function_exists('make_mask_collection')) {
    /**
     * make_mask
     * 
     */
    function make_mask_collection($name = null, $mask = null)
    {
        if (!$name) {
            echo "Tham so:\n\t\$name (required): Tên collection (nên sử dụng [Folder]/[name])\n\t\$mask (option): Tên mask";
            return null;
        }

        $names = explode('/', str_replace("\\", "/", $name));
        $name = ucfirst(array_pop($names));
        if (!$mask) {
            $mask = $name;
        }
        $folder = count($names) ? implode('/', array_map('ucfirst', $names)) : ucfirst(Str::plural($name));
        $sub = null;
        if ($folder) {
            $folder = '/' . trim($folder, '/');
            $sub = str_replace("/", "\\", $folder);
        }


        $find = ['NAME', 'MASK', 'SUB'];
        $replace = [$name, $mask, $sub];

        make_mask_collection_file($name, $folder, $find, $replace);
    }
}


if (!function_exists('make_mask_collection_file')) {
    /**
     * make_mask
     * 
     */
    function make_mask_collection_file($name, $folder, $find, $replace)
    {
        $template = file_get_contents(DEVPATH . '/templates/mask-collection.php');
        $code = str_replace($find, $replace, $template);
        $filemanager = new Filemanager();
        $filemanager->setDir((BASEDIR . '/app/Masks' . $folder . '/'));
        if ($a = $filemanager->save($name . 'Collection.php', $code, 'php')) {
            echo "Tạo {$name}Collection thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        } else {
            echo "Lỗi không xác định\n";
        }
    }
}



function make_resource($args = [], $name = null, $table = null)
{
    if (!$name) {
        echo "Tham so:\n\$name -- Ten resource\n\$table -- Tên bảng\n";
        return null;
    }
    if (!$table) $table = Str::tableName($name);

    $find = ['NAME', '$ELEMENTS'];
    $replace = [$name, getResource($table)];
    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/resource.php');
    $filemanager->setDir(base_path('app/Http/Resources'));
    $code = str_replace($find, $replace, $template);
    if ($a = $filemanager->save($name . 'Resource.php', $code, 'php')) {
        echo "Tạo {$name}Resource thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        $template = file_get_contents(DEVPATH . '/templates/resource-item.php');
        $code = str_replace($find, $replace, $template);
        $na = $name . 'Item';
        if ($a = $filemanager->save($na . '.php', $code, 'php')) {
            echo "Tạo $na thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        } else {
            echo "Lỗi không xác định\n";
        }

        $template = file_get_contents(DEVPATH . '/templates/resource-collection.php');
        $code = str_replace($find, $replace, $template);
        $na = $name . 'Collection';
        if ($a = $filemanager->save($na . '.php', $code, 'php')) {
            echo "Tạo $na thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        } else {
            echo "Lỗi không xác định\n";
        }
    } else {
        echo "Lỗi không xác định\n";
    }
}



function make_modules($args = [], $make_list = null, $name = null, $table = null)
{
    if (!$make_list) {
        echo "";
    }
    $supported = 'model,repository,validator,resource,controller';
    if (strtolower($make_list) == 'all' || $make_list == '*' || !$make_list) $make_list = $supported;
    $sp = explode(',', $supported);
    $ml = array_filter(
        array_map(
            function ($val) {
                return trim(strtolower($val));
            },
            explode(',', $make_list)
        ),
        function ($value) use ($sp) {
            return in_array($value, $sp);
        }
    );
    if (!$ml) {
        echo $make_list . 'không được hỗ trợ';
        return null;
    }
    if (!$name) $name = 'Test';
    $names = explode('/', str_replace("\\", "/", $name));
    $name = ucfirst(array_pop($names));
    $folder = count($names) ? implode('/', array_map('ucfirst', $names)) : ucfirst(Str::plural($name));
    $table = $table ?? Str::tableName($name);
    if ($make_list) {
        foreach ($ml as $item) {
            // 
            switch ($item) {
                case 'model':
                case 'resource':
                case 'validator':
                    call_user_func_array('make_' . $item, [$args, $name, $table, $folder]);
                    break;

                case 'repository':
                    # code...
                    call_user_func_array('make_' . $item, [$args, $name, $name, $folder]);
                    break;

                case 'controller':
                    # code...
                    break;

                default:
                    # code...
                    break;
            }
        }
    }
}

function make_json($args = [], $table = null, $filename = null)
{
    $filemanager = new Filemanager(base_path('json'));
    if ($file = $filemanager->save($filename, Str::jsonVi(json_encode(defaultJson($table))), 'json')) {
        echo "Đã tạo file thành công!\n Bạn có thể chỉnh sửa file theo dường dẫn sau:\n$file->path\n";
    }
}


function make_json_module($args = [], $module = null, $table = null, $moduleName = null)
{
    if (!$module) {
        echo "Tham so:\n\t\$module -- Ten thư mục\n\t\$table -- Tên bảng\n\t\$path -- duong dan tu thu muc /json/";
        return null;
    }
    $names = explode('/', str_replace("\\", "/", $module));
    $name = ucfirst(array_pop($names));

    if (!$table) $table = Str::tableName($name);

    $filemanager = new Filemanager(base_path('json'));
    if ($file = $filemanager->save($module . '/form.json', Str::jsonVi(json_encode(['inputs' => defaultJson($table), 'config' => ['name' => $moduleName]], JSON_PRETTY_PRINT)), 'json')) {
        echo "create form success\nPath: $file->path\n";
    }
    $fields = schema($table)->getConfig(true);
    $json = [
        "name" => "[module]",
        "package" => "customers",
        "use_trash" => true,
        "titles" => ["default" => "Danh sách " . ($moduleName ?? '[module]'), "trash" => "Danh sách " . ($moduleName ?? '[module]') . " đã xóa"],
        "data" => [], "filter" => ["search_columns" => [], "sort_columns" => []],
        "table" => ["class" => "header-center", "columns" => []],
        "resources" => ["js_data" => [], "js" => [], "css" => []]
    ];
    $json['package'] = $table;
    if ($moduleName) $json['name'] = $moduleName;
    $columns = [];
    foreach ($fields as $col => $config) {
        $columns[] = [
            'title' => $config->comment ?? implode(' ', array_map('ucfirst', explode('_', $config->name))),
            'class' => '',
            'text' => ':' . $col
        ];
    }
    $json['table']['columns'] = $columns;
    if ($file = $filemanager->save($module . '/list.json', Str::jsonVi(json_encode($json, JSON_PRETTY_PRINT)), 'json')) {
        echo "create list success\nPath: $file->path\n";
    }
}

function update_json_form($args = [], $module = null, $column = null, $type = null, $label = null, $placeholder = null)
{
    if (!$module || !$column)
        echo "Bạn chưa nhập module hoặc tên field";
    elseif (!($filemanager = new Filemanager(base_path('json/admin/modules'))))
        echo "Không thể khởi tạo file manager";
    elseif (!($json = $filemanager->json($module . '/form.json')))
        echo "Module không tồn tại";
    else {
        $d = [];
        if ($type) $d['type'] = $type;
        if ($type) $d['label'] = $label;
        if ($type) $d['placeholder'] = $placeholder;
        elseif ($label) $d['placeholder'] = 'Nhập ' . strtolower($label);
        $data = array_merge($d, $args);
        $data = array_merge($json['inputs'][$column] ?? [], $data);
        if (array_key_exists('ph', $data)) {
            if (!array_key_exists('placeholder', $data)) {
                $data['placeholder'] = str_replace('@label', 'Nhập ' . ($data['label'] ?? ''), $data['ph']);
            } else {
                $data['placeholder'] = str_replace('@label', 'Nhập ' . ($data['label'] ?? ''), $data['placeholder']);
            }
            unset($data['ph']);
        } elseif (array_key_exists('placeholder', $data)) {
            $data['placeholder'] = str_replace('@label', 'Nhập ' . ($data['label'] ?? ''), $data['placeholder']);
        }
        $json['inputs'][$column] = array_merge($json['inputs'][$column] ?? [], $data);
        if ($file = $filemanager->save($module . '/form.json', Str::jsonVi(json_encode($json, JSON_PRETTY_PRINT)), 'json')) {
            echo "update form success\nPath: $file->path";
        } else {
            echo "Lưu file không thành công";
        }
    }
    echo "\n";
}

function update_storage_data()
{
    if (convert_json_to_php(base_path('json'), base_path('storage/crazy/data'))) {
        echo 'Cạp nhập file thành công';
    } else {
        echo 'Lỗi ko xác định';
    }
}

function convert_json_to_php($json_path, $php_path)
{
    $filemanager = new Filemanager($json_path);
    $status = false;
    if ($list = $filemanager->getList()) {
        foreach ($list as $file) {
            if ($file->type == 'folder') {
                if (convert_json_to_php($json_path . '/' . $file->name, $php_path . '/' . $file->name)) $status = true;;
            } elseif ($file->extension == 'json') {
                $filemanager->convertJsonToPhp($file->name, $php_path . '/' . preg_replace('/\.json$/i', '.php', $file->name));
                $status = true;
            }
        }
    }
    return $status;
}


function makeObj($object = null, ...$params)
{
    if ($object == null) {
        die("Please select item to make (repository, model, controller, mask)");
    }
    $p = get_args_params($params);

    if ($object == 'modules' || $object == 'module') {
        make_modules($p['params'], ...$p['args']);
    } elseif (is_callable('make_' . $object)) {
        $args = array_merge([$p['params']], $p['args']);
        call_user_func_array('make_' . $object, $args);
    }

    // else make_modules($object, $p['params'], ...$p['args']);
}

function analytic_str_params($params)
{
    $data = [
        'name' => '',
        'type' => 'string',
        'default' => null,
        'calls' => []
    ];
    $prs = explode('/', $params);
    if (count($prs) > 0) {
        $i = 0;
        foreach ($prs as $pr) {
            if (count($pp = explode('=', $pr)) == 2) {
                if ($i == 0)
                    $data['name'] = $pp[1];
                else {
                    $data['default'] = $pp[1];
                    if (in_array($pp[0], ['null', 'nullable']))
                        $data['calls'][] = [
                            'call' => 'nullable',
                            'params' => []
                        ];
                    else
                        $data['calls'][] = [
                            'call' => $pp[0],
                            'params' => []
                        ];
                }
            } elseif (count($pa = explode(':', $pr)) == 2) {
                if ($i == 0) {
                    $data['name'] = $pa[0];
                    $data['type'] = $pa[1];
                } else {
                    $data['calls'][] = [
                        'call' => $pa[0],
                        'params' => array_map('trim', explode(',', $pa[1]))
                    ];
                }
            } else {
                if ($i == 0)
                    $data['name'] = $pr;
                elseif (in_array($pp[0], ['null', 'nullable']))
                    $data['calls'][] = [
                        'call' => 'nullable',
                        'params' => []
                    ];
                else
                    $data['calls'][] = [
                        'call' => $pr,
                        'params' => []
                    ];
            }

            $i++;
        }
    }
    return $data;
}

function create_table($params = [], $table = null, ...$args)
{
    if (!$table) {
        echo "Tham so:\n\$name -- Ten bảng\n...\$args -- tham số\n";
        return null;
    }


    $table = Str::tableName($table);

    $columns = [];
    $drops = [];
    $add = $params['add'] ?? ($params['columns'] ?? ($params['column'] ?? ($params['col'] ?? '')));
    if ($add) {
        $cs = is_array($add) ? $add : explode(',', $add);
        if (count($cs)) {
            foreach ($cs as $text) {
                $c = analytic_str_params($text);
                if ($c['name']) {
                    $col = "\$table->" . $c['type'] . "('" . $c['name'] . "')";
                    // . ($c['length'] ? "->length($c[length])" : '')
                    if (isset($c['calls']) && $c['calls']) {
                        foreach ($c['calls'] as $cData) {
                            $col .= "->" . $cData['call'] . '(';
                            $col .= $cData['params'] ? implode(',', array_map(function ($v) {
                                return is_numeric($v) ? $v : "'" . $v . "'";
                            }, $cData['params'])) : "";
                            $col .= ')';
                        }
                    }
                    $col .= ''
                        . ((!is_null($c['default'])) ? '->default(' . (in_array(strtolower($c['type']), ['integer', 'biginteger', 'float', 'decimal', 'double', 'boolean']) ? $c['default'] : "\"$c[default]\"") . ')' : '')
                        . ';';
                    $columns[] = $col;

                    // $drops[] = "\$table->dropColumn('$c[name]');";
                }
            }
        }
    }



    $find = ['TABLE_NAME', '// COLUMN HERE'];
    //$columns = [];
    if ((isset($params['softdelete']) && $params['softdelete'] != 'false') || (isset($params['softDelete']) && $params['softDelete'] != 'false')) {
        $columns[] = "\$table->softDeletes();";
    }

    if (!(isset($params['timestamps']) && $params['timestamps'] == 'false')) {
        $columns[] = "\$table->timestamps();";
    }
    $replace = [$table, implode("\n            ", $columns)];
    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/create-table.php');
    $filemanager->setDir(base_path('database/migrations/'));
    $code = str_replace($find, $replace, $template);
    $fn = date('Y_m_d_His') . "_create_{$table}_table.php";
    if ($a = $filemanager->save($fn, $code, 'php')) {
        echo "Tạo bảng {$table} thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        $m = isset($params['model']) && $params['model'] ? $params['model'] : (isset($params['m']) && $params['m'] ? $params['m'] : null);
        if ($m) {
            makeObj('model', $m, $table);
        }
    } else {
        echo "Lỗi không xác định\n";
    }
    // if(is_array($args)){
    //     foreach ($args as $i => $col) {
    //         $name = '';
    //         $type = '';
    //         $length = null;
    //         $default = '';
    //         $props = [];
    //         if($t = count($c = explode(':', $col))){
    //             $name = trim(array_shift($c));
    //             if($t >= 2){
    //                 $ty = array_shift($c);

    //             }else{
    //                 $type = 'string';
    //             }


    //         }
    //     }
    // }

}


function alter_table($params = [], $table = null, ...$args)
{
    if (!$table) {
        echo "Tham so:\n\$name -- Ten bảng\n...\$args -- tham số\n";
        return null;
    }
    // $table = Str::tableName($table);
    if (!Illuminate\Support\Facades\Schema::hasTable($table)) die('Bang nay ko da ton tai');


    $columns = [];
    $drops = [];
    $add = $params['add'] ?? ($params['columns'] ?? ($params['column'] ?? ($params['col'] ?? '')));
    if ($add) {
        $cs = is_array($add) ? $add : explode(',', $add);
        if (count($cs)) {
            foreach ($cs as $text) {
                $c = analytic_str_params($text);
                if ($c['name']) {
                    $col = "\$table->" . $c['type'] . "('" . $c['name'] . "')";
                    // . ($c['length'] ? "->length($c[length])" : '')
                    if (isset($c['calls']) && $c['calls']) {
                        foreach ($c['calls'] as $cData) {
                            $col .= "->" . $cData['call'] . '(';
                            $col .= $cData['params'] ? implode(',', array_map(function ($v) {
                                return is_numeric($v) ? $v : "'" . $v . "'";
                            }, $cData['params'])) : "";
                            $col .= ')';
                        }
                    }
                    $col .= ''
                        . ((!is_null($c['default'])) ? '->default(' . (in_array(strtolower($c['type']), ['integer', 'biginteger', 'float', 'decimal', 'double', 'boolean']) ? $c['default'] : "\"$c[default]\"") . ')' : '')
                        . ';';
                    $columns[] = $col;

                    $drops[] = "\$table->dropColumn('$c[name]');";
                }
            }
        }
    }

    if (array_key_exists('change', $params)) {
        $cs = is_array($params['change']) ? $params['change'] : explode(',', $params['change']);
        if (count($cs)) {
            foreach ($cs as $text) {
                $c = analytic_str_params($text);
                if ($c['name']) {
                    $col = "\$table->" . $c['type'] . "('" . $c['name'] . "')";
                    // . ($c['length'] ? "->length($c[length])" : '')
                    if (isset($c['calls']) && $c['calls']) {
                        foreach ($c['calls'] as $cData) {
                            $col .= "->" . $cData['call'] . '(';
                            $col .= $cData['params'] ? implode(',', array_map(function ($v) {
                                return is_numeric($v) ? $v : "\'" . $v . "\'";
                            }, $cData['params'])) : "";
                            $col .= ')';
                        }
                    }
                    $col .= ''
                        . ((!is_null($c['default'])) ? '->default(' . (in_array(strtolower($c['type']), ['integer', 'biginteger', 'float', 'decimal', 'double', 'boolean']) ? $c['default'] : "\"$c[default]\"") . ')' : '')
                        . '->change();';
                    $columns[] = $col;
                }
            }
        }
    }
    if (array_key_exists('drop', $params)) {
        $cs = is_array($params['drop']) ? $params['drop'] : explode(',', $params['drop']);
        if (count($cs)) {
            foreach ($cs as $text) {

                $columns[] = "\$table->dropColumn('$text');";
            }
        }
    }
    if (array_key_exists('drops', $params)) {
        $cs = is_array($params['drops']) ? $params['drops'] : explode(',', $params['drops']);
        if (count($cs)) {
            foreach ($cs as $text) {

                $columns[] = "\$table->dropColumn('$text');";
            }
        }
    }
    $COL = implode("\n            ", $columns);
    $DRO = implode("\n            ", $drops);

    $find = ['TABLE_NAME', '//COLUMNS', '//DROPS'];
    $replace = [$table, $COL, $DRO];
    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/alter-table.php');
    $filemanager->setDir(base_path('database/migrations/'));
    $code = str_replace($find, $replace, $template);
    $a = $args ? ('_' . implode('_', $args)) : '';
    $fn = date('Y_m_d_His') . "_alter_table_{$table}{$a}.php";
    if ($a = $filemanager->save($fn, $code, 'php')) {
        echo "Tạo bảng {$table} thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
    } else {
        echo "Lỗi không xác định\n";
    }
}
function create_provider($params = [], $name = null, ...$args)
{
    if (!$name) {
        echo "Tham so:\n\$name -- Provieder\n...\$args -- tham số\n";
        return null;
    }
    $name = ucfirst($name);
    $find = ['NAME'];
    $columns = [];

    if ((isset($params['f']) && $params['f'] != 'false') || (isset($params['full']) && $params['full'] != 'false') || (!isset($params['s']) || $params['f'] == 'false') || (!isset($params['short']) || $params['short'] == 'false')) {
        $name .= 'ServiceProvider';
    }

    if (!(isset($params['timestamps']) && $params['timestamps'] == 'false')) {
        $columns[] = "\$table->timestamps();";
    }
    $replace = [$name];
    $filemanager = new Filemanager();
    $template = file_get_contents(DEVPATH . '/templates/provider.php');
    $filemanager->setDir(base_path('app/Providers/'));
    $code = str_replace($find, $replace, $template);
    $fn = "{$name}.php";
    if ($a = $filemanager->save($fn, $code, 'php')) {
        echo "Tạo Provider {$name} thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
    } else {
        echo "Lỗi không xác định\n";
    }
}


if (!function_exists('create_service')) {
    /**
     * create_service
     * 
     */
    function create_service($args = [], $type = 'client', $name = null, $repo = null, $title = null, $module = null)
    {
        if (!$name) {
            echo "Tham so:\n\t\$type -- loai service (client, admin, manager, api, custom)\n\t\$name -- Ten service\n\t\$repo -- ten class Repository/Model\n\t\$title -- ten/tieu de\n\t\$module -- js module && route module\n\n";
            return null;
        }
        $folders = [
            'client' => 'Clients',
            'admin' => 'Admin',
            'account' => 'Accounts',
            'manager' => 'Manager',
            'branch' => 'Branch',
            'cpanel' => 'CPanel',
            'frontend' => 'Frontend',
            'backend' => 'Backend',
            'private' => 'Private',
            'public' => 'Public',
            'protected' => 'Protected',
            'publish' => 'Publish',
            'api' => 'Apis',
            'custom' => null
        ];
        $ac = explode('/', str_replace("\\", "/", $name));
        $name = array_pop($ac);
        if (!array_key_exists($t = strtolower($type), $folders) || !$name) return null;
        $s = implode('/', array_map('ucfirst', $ac));
        $folder = $folders[$t] . ($s ? '/' . $s : '');
        $master = ucfirst($t);
        $prectr = $master;
        if ($master) {
            $prectr = $folders[$t] . "\\" . $master;
        }
        $sub = null;
        if ($folder) {
            $folder = '/' . trim($folder, '/');
            $sub = str_replace("/", "\\", $folder);
        }
        if (!$repo) $repo = $name;
        $repos = explode('/', str_replace("\\", "/", $repo));
        $repo = ucfirst(array_pop($repos));
        $repf = count($repos) ? implode('/', array_map('ucfirst', $repos)) : ucfirst(Str::plural($repo));

        if (!$title) $title = $name;
        if (!$module) $module = strtolower(Str::plural($name));

        $find = ['NAME', 'MASTER', 'SUB', 'REPO', 'REPF', 'MODULE', 'TITLE', 'PRECTRL', '#use service;'];
        $replace = [$name, $master, $sub, $repo, $repf, $module, $title, $prectr, $s ? '' : '# '];

        $template = file_get_contents(DEVPATH . '/templates/service.php');
        $code = str_replace($find, $replace, $template);
        $filemanager = new Filemanager();
        $filemanager->setDir((BASEDIR . '/app/Services' . $folder . '/'));
        if ($a = $filemanager->save($name . 'Service.php', $code, 'php')) {
            echo "Tạo {$name}Service thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        } else {
            echo "Lỗi không xác định\n";
        }
    }
}



if (!function_exists('make_route')) {
    /**
     * make_controller
     * 
     */
    function make_route($args = [], $type = 'client', $filename = null, $controller = null, $MODULENAME = null, $MODULEDESCRIPTION = null)
    {
        $folders = [
            'client' => 'Clients',
            'cms' => 'CMS',
            'admin' => 'Admin',
            'account' => 'Accounts',
            'manager' => 'Manager',
            'branch' => 'Branch',
            'cpanel' => 'CPanel',
            'backend' => 'Backend',
            'private' => 'Private',
            'public' => 'Public',
            'protected' => 'Protected',
            'publish' => 'Publish',
            'api' => 'Apis',
            'web' => 'Web',
            'frontend' => 'Frontend',

            'custom' => null
        ];

        if (!$filename) {
            echo "Tham so:\n\t\$type -- loai controller (" . implode(', ', array_keys($folders)) . ")\n\t\$name -- Ten file route\n\t\$controller -- ten controller PathName [không cần Controller]\n\t\$ModuleName -- tenmodule\n\t\$ModuleDescription -- Mô tả\n\n";
            return null;
        }
        $ac = explode('/', str_replace("\\", "/", $controller));

        $name = ucfirst(array_pop($ac));

        if (!array_key_exists($t = strtolower($type), $folders) || !$name) {
            echo "Tham so:\n\t\$type -- loai route: (" . implode(', ', array_keys($folders)) . ")\n\n";
            return;
        }
        $s = implode('\\', array_map('ucfirst', $ac));
        $CONTROLLERPATH = $s ? $s . '\\' : '';
        $CONTROLLERNAME = $name;
        $find = ['CONTROLLERPATH', 'CONTROLLERNAME', 'MODULENAME', 'MODULEDESCRIPTION'];
        $replace = [$CONTROLLERPATH, $CONTROLLERNAME, $MODULENAME, $MODULEDESCRIPTION];

        $template = file_get_contents(DEVPATH . '/templates/route-' . $t . '.php');
        $code = str_replace($find, $replace, $template);
        $filemanager = new Filemanager();
        $filemanager->setDir((BASEDIR . '/routes/' . $t . '/'));
        if ($a = $filemanager->save($filename . '.php', $code, 'php')) {
            echo "Tạo {$name} route thành công!\nBạn có thể sửa file theo dường dẫn sau: \n$a->path \n";
        } else {
            echo "Lỗi không xác định\n";
        }
    }
}

function config()
{
    return true;
}
