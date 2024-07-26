<?php

function base_path($path = null)
{
    return BASEDIR . ($path ? '/' .ltrim($path, '/'):'');
}


function getFields($table = null, $inline = false){
    $table = schema($table);
    if($inline){
        return "['".implode("', '", $table->getColumns())."']";
    }

    return $table;

}
function getColumns($table = null){
    $table = schema($table);
    
    return $table->getColumns();

}

function getResource($table = null){
    $fillable = schema($table)->getColumns();

    $a = "";
    foreach ($fillable as $field) {
        $a.= "\n            '$field' => \$this->$field,";
        // echo "\n$field:";
    }
    $a .= "\n";
    return $a;
}

function getRules($table = null){
    $fillable = schema($table)->getData();
    $a = "";
    foreach ($fillable as $field => $type) {
        $a.= "\n            '$field' => '$type',";
        // echo "\n$field:";
    }
    $a .= "\n";
    return $a;
}
function getMessages($table = null){
    $fillable = schema($table)->getData();
    $fields = schema($table)->getConfig(true);

    $a = "";

    foreach ($fields as $field => $config) {
        $label = $config->comment??implode(' ', array_map('ucfirst', explode('_', $config->name)));
        $type = $config->type;
        $field = $config->name;
        $a.= "\n            '$field.$type' => '$label Không hợp lệ',";
        // echo "\n$field:";
    }
    $a .= "\n";
    return $a;
}


function getProperties($table = null){
    $fillable = schema($table)->getData();

    $a = "";
    $fields = schema($table)->getConfig(true);
    foreach ($fields as $field => $config) {
        $label = $config->comment??implode(' ', array_map('ucfirst', explode('_', $config->name)));
        $type = $config->type;
        $field = $config->name;
        $a.= "\n * @property $type \$$field $label";
        // echo "\n$field:";
    }
    // $a .= "\n";
    return $a;
}


function defaultJson($table = null){
    $fields = schema($table)->getConfig(true);

    $a = [];
    foreach ($fields as $field => $cfg) {
        $lb = $cfg->comment??implode(' ', array_map('ucfirst', explode('_', $cfg->name)));
        $a[$field] = [
            'type' => $cfg->type == 'boolean'?'switch':($cfg->type == 'integer' || $cfg->type == 'float'?'number':'text'),
            'label' => $lb,
            'placeholder' => 'Nhập '.$lb
        ];
    }
    return $a;
}


function show($data)
{
    if(is_array($data)) $data = json_encode($data);
    echo $data;
}

function show_list($params, ...$args){
    $t = count($args);
    if(isset($args[0])){
        switch($args[0]){
            case 'controller':
                case 'ctl':
                    if($t > 1){
                        $l = strtolower($args[1]);
                        if($l == 'methods' || $l == 'method' || $l == 'mt'){
                            echo '
void save(Request $request) - lưu dữ liệu sau khi validate
                            ';
                        }
                    }
                    break;

        }
    }
}