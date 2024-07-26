<?php

use Illuminate\Database\Schema\Blueprint;

if(!defined('BASE_PATH')) define('BASE_PATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
define('BASEDIR', BASE_PATH);

define('DEVPATH', dirname(__FILE__));
require_once __DIR__.'/libs/Inflector.php';
require_once __DIR__.'/libs/Pluralizer.php';

require_once __DIR__.'/Helpers/Any.php';
require_once __DIR__.'/Helpers//Arr.php';
require_once __DIR__.'/Helpers//Str.php';

require_once __DIR__.'/Files/DirMethods.php';
require_once __DIR__.'/Files/FileMethods.php';
require_once __DIR__.'/Files/FileConverter.php';
require_once __DIR__.'/Files/FileType.php';
require_once __DIR__.'/Files/ZipMethods.php';
require_once __DIR__.'/Files/Filemanager.php';

require_once __DIR__.'/tests/str.php';
require_once __DIR__.'/functions.php';

require_once __DIR__.'/Illuminate/Schema.php';
require_once __DIR__.'/Illuminate/Blueprint.php';
require_once __DIR__.'/Illuminate/Migration.php';
require_once __DIR__.'/functions/make.php';



$dir = BASEDIR.'/database/migrations';
if (is_string($dir) && is_dir($dir)) {
    try{
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                $fs = explode('.',$file);
                $ex = array_pop($fs);
                $fc = $fs[0];
                if($ex == 'php' && !preg_match('/create_oauth_/i', $file)){
                    $mig = require_once $dir.'/'.$file;
                    if($mig && is_a($mig, Illuminate\Database\Migrations\Migration::class)){
                        $mig->up();
                    }
                    else{
                        $fcl = str_replace(' ', '', ucwords(str_replace('_', ' ', substr($fc, 18))));
                        if(class_exists($fcl)){
                            $rc = new ReflectionClass($fcl);
                            $a = $rc->newInstanceArgs( [] );
                            $a->up();
                        }
                    }
                    

                }
            
                
            }
            closedir($dh);
        }
    }catch(exception $e){
        // $this->errors[__METHOD__] = $e->getMessage();
    }
}

/**
 * get table
 *
 * @param string $table
 * @return Blueprint
 */
function schema($table)
{

    return Illuminate\Support\Facades\Schema::get($table);

}
$filemanager = new Filemanager(__DIR__ . '/commands');
if(count($files = $filemanager->getList(null, 'php'))){
    foreach($files as $file){
        require $file->path;
    }
}

require_once '__call__.php';
