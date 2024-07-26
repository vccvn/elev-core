<?php

namespace Gomee\Services\Traits;

use Gomee\Core\System;

trait PackageMethods{


    protected $packagePath = null;

    protected $package = null;
    
    public function packageInit()
    {
        if(!$this->package) return false;
        if($path = System::getPackagePath($this->package)){
            $this->packagePath = $path;
            $this->mode = 'package';
        }
    }
    
}