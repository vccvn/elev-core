<?php

namespace Gomee\Crawlers;

use Gomee\Repositories\Files\FileRepository;
use Gomee\Repositories\Metadatas\MetadataRepository;

use Carbon\Carbon;
use Gomee\Files\Image;
use Gomee\Helpers\Arr;

class Crawler
{
    use Crawl;

    
     /**
     * chay lai thiet lap
     */
    public function __construct()
    {
        if(method_exists($this, 'init')){
            $this->init();
        }
    }

    public function __call($name, $arguments)
    {
        
    }
}