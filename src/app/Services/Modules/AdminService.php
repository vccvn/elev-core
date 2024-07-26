<?php

namespace Gomee\Services\Modules;
use Gomee\Core\System;
use Gomee\Engines\Helper;
use Gomee\Services\BaseService;

class AdminService extends BaseService
{
    /**
     * @var string $routeNamePrefix
     */
    protected $routeNamePrefix = 'admin.';

    /**
     * @var string $viewFolder thu muc chua view
     * khong nen thay doi lam gi
     */
    protected $viewFolder = 'admin';
    /**
     * @var string dường dãn thư mục chứa form
     */
    protected $formDir = 'admin/forms';

    /**
     * @var string $menuName
     */
    protected $menuName = 'admin_menu';
    

    protected $scope = 'admin';

    protected $mode = 'package';


    
    /**
     * thuc thi mot so thiet lap
     * @return void
     */
    public function init()
    {
        $this->packageInit();
        $this->moduleInit();
        $this->crudInit();
        $this->fileInit();
        $this->formInit();
        $this->activeMenu();
        $this->start();
    }


    public function jsonPath($path = null)
    {
        return $this->packagePath .'/src/json' . ($path?'/' .ltrim($path):'');
    }
    

    public function updateFormDir()
    {
        $this->jsonFormDir = $this->packagePath .'/src/json/' . $this->formDir;
        $this->phpFormDir = Helper::storage_path('crazy/'. ltrim($this->formDir, '/'));
        $this->realFormDir = $this->jsonFormDir;
    }


    /**
     * thiết lập module
     */
    public function moduleInit()
    {
        if (!$this->moduleBlade) $this->moduleBlade = $this->module;

        if ($this->repository)
            $this->repository->notTrashed();

        $this->modulePath = $this->scope . '/modules/' . str_replace('.', '/', $this->module);
    }
}
