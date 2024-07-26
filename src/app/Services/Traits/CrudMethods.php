<?php

namespace Gomee\Services\Traits;

use Gomee\Models\Model;
use Gomee\Helpers\Arr;
use Illuminate\Http\Request;
// use Gomee\Html\HTML;


use Gomee\Laravel\Router;


/**
 * các thuộc tính và phương thức của form sẽ được triển trong ManagerController / hoặc admin controller
 *
 * @method mixed beforeSaveValidate( Request $request )
 * @method mixed beforeAjaxValidate( Request $request )
 * @method mixed beforeCreateValidate( Request $request )
 * @method mixed beforeAjaxCreateValidate( Request $request )
 * @method mixed beforeStoreValidate( Request $request )
 * @method mixed beforeUpdateValidate( Request $request )
 * @method mixed beforeAjaxUpdateValidate( Request $request )
 * @method mixed beforeValidate( Request $request )
 * @method mixed beforeHandleValidate( Request $request )
 * 
 * @method mixed beforeSave( Request $request, Arr $data, \Gomee\Models\Model $old = null  ) 
 * @method mixed beforeAjaxSave( Request $request, Arr $data, \Gomee\Models\Model $old = null ) 
 * @method mixed beforeCreate( Request $request, Arr $data ) 
 * @method mixed beforeAjaxCreate( Request $request, Arr $data ) 
 * @method mixed beforeStore( Request $request, Arr $data ) 
 * @method mixed beforeUpdate( Request $request, Arr $data, Model $old )
 * @method mixed beforeAjaxUpdate( Request $request, Arr $data, \Gomee\Models\Model $old )
 * @method mixed beforeMoveToTrash( \Gomee\Models\Model $model ) 
 * @method mixed beforeRestore( \Gomee\Models\Model $model )
 * @method mixed beforeDelete( \Gomee\Models\Model $model )
 * @method mixed prepareMoveToTrash( Request $request, array $ids = []) thực hiện hành động trước khi move to trash
 * @method mixed prepareRestore( Request $request, array $ids = []) thực hiện hành động trước khi restore
 * @method mixed prepareDelete( Request $request, array $ids = []) thực hiện hành động trước khi delete
 * 
 * @method mixed afterSave( Request $request, \Gomee\Models\Model $result, Arr $data )
 * @method mixed afterAjaxSave( Request $request, \Gomee\Models\Model $result, Arr $data )
 * @method mixed afterCreate( Request $request, \Gomee\Models\Model $result, Arr $data ) 
 * @method mixed afterAjaxCreate( Request $request, \Gomee\Models\Model $result, Arr $data ) 
 * @method mixed afterStore( Request $request, \Gomee\Models\Model $result, Arr $data ) 
 * @method mixed afterUpdate( Request $request, \Gomee\Models\Model $result, Arr $data ) 
 * @method mixed afterAjaxUpdate( Request $request, \Gomee\Models\Model $result, Arr $data ) 
 * @method mixed afterMoveToTrash( Request $request, \Gomee\Models\Model $result, Arr $data ) 
 * @method mixed afterRestore( Request $request, \Gomee\Models\Model $result, Arr $data )
 * @method mixed afterDelete( Request $request, \Gomee\Models\Model $result, Arr $data )
 * 
 * @method mixed done( Request $request, Arr $data )
 */
trait CrudMethods
{

    /**
     * route chuyển hướng sau khi lưu
     * @var string $redirectRoute
     */
    protected $redirectRoute = null;

    /**
     * @var array $redirectRouteParams
     */
    protected $redirectRouteParams = [];



    /**
     * @var string $primaryKeyName ten khoa chinh
     */
    protected $primaryKeyName = MODEL_PRIMARY_KEY;

    /**
     * @var array $apiDefaultData đử liệu mặc định trả về api
     * 
     */
    protected $apiDefaultData = [
        'status' => false,
        'message' => 'Thao tác thành công!',
        'data' => null,
        'errors' => []
    ];


    /**
     * danh sách trả về9
     * @var array $apiSystemVars
     */
    protected $apiSystemVars = ['status', 'data', 'message', 'errors'];


    protected $crudAction = null;

    /**
     * lưu dữ liệu bao gồm cập nhật hoặc tạo mới
     * @param Request $request
     * 
     * @return redirect
     */
    public function crudInit()
    {
        if ($this->repository) $this->primaryKeyName = $this->repository->getKeyName();
        // do some thing
    }

    /**
     * bắt sự kiện
     * @param string $event
     * @param array ...$params
     * @return mixed
     */
    public final function callCrudEvent(string $event, ...$params)
    {
        if (method_exists($this, $event)) {
            return call_user_func_array([$this, $event], $params);
        }
        return null;
    }



    public function getValidatedData(Request $request, $ruleOrValidatorClass = null, $message = [])
    {
        return $this->repository->validate($request, $ruleOrValidatorClass, $message);
    }


    /**
     * luu data tao moi
     * @param \Illuminate\Http\Request $request
     */
    public function create(Request $request)
    {
        return $this->save($request);
    }


    /**
     * luu data cap nhat
     * @param \Illuminate\Http\Request $request
     * @param int $id
     */
    public function update(Request $request, $id = null)
    {
        $id = $id?$id: $id = $request->{$this->repository->getKeyName()};
        if(!$id) $id = '_404_';
        return $this->save($request, $id);
    }

    /**
     * lấy id của request
     * @param Request $request
     * @return array
     */
    public function getIdListFromRequest(Request $request)
    {
        $ids = [];
        $listKey = ['ids', MODEL_PRIMARY_KEY];
        // $listKey[] = MODEL_PRIMARY_KEY;
        if ($this->primaryKeyName != MODEL_PRIMARY_KEY) {
            $listKey[] = $this->primaryKeyName;
        }
        foreach ($listKey as $key) {
            if ($list = $request->input($key)) {
                if (is_array($list)) $ids = array_merge($ids, $list);
                else $ids[] = $list;
            } elseif ($list = $request->{$key}) {
                if (is_array($list)) $ids = array_merge($ids, $list);
                else $ids[] = $list;
            }
        }
        return $ids;
    }

    /**
     * xóa tạm thời bản gi
     * @param Request $request
     */
    public function moveToTrash(Request $request)
    {
        extract($this->apiDefaultData);
        $ids = $this->getIdListFromRequest($request);
        $this->callCrudEvent('prepareMoveToTrash', $request, $ids);
        // nếu có id
        if (count($ids) && count($list = $this->repository->get([$this->primaryKeyName => $ids]))) {
            $data = [];
            foreach ($list as $result) {
                $id = $result->{$this->primaryKeyName};
                $canDel = $result->canMoveToTrash();
                if ($canDel === true) {

                    // gọi hàm sự kiện truoc khi xóa
                    $this->callCrudEvent('beforeMoveToTrash', $result);

                    $this->fire('trashing', $this, $result);
                    // chuyen vao thung ra

                    $this->repository->moveToTrash($id);

                    // gọi hàm sự kiện truoc khi xóa
                    $this->callCrudEvent('afterMoveToTrash', $result);
                    $this->fire('trashed', $this, $result);

                    $data[] = $id;

                    $status = true;
                } elseif (!is_bool($canDel) && is_string($canDel) && strlen($canDel)) {
                    $errors[] = $canDel;
                } else {
                    $errors[] = "Bạn không thể di chuyển $this->moduleName " . ($result->title ? $result->title : ($result->name ? $result->name : ($result->label ? $result->label : 'có id ' . $id))) . " này vào thùng rác được";
                }
            }
            if ($status) {
                if (($t = count($data)) > 1) {
                    $message = "Đã xóa thành công $t $this->moduleName";
                } else {
                    $message = "Đã xóa $this->moduleName thành công!";
                }
            } else {
                $message = count($errors) == 1 ? $errors[0] : "Không thể chuyển một số mục vào thùng rác được!";
            }
        } else {
            $message = 'Không có mục nào được chọn';
        }
        return $this->json(compact(...$this->apiSystemVars));
    }




    /**
     * xóa vĩnh viễn bản gi
     * @param Request $request
     */
    public function delete(Request $request)
    {
        extract($this->apiDefaultData);
        $ids = $this->getIdListFromRequest($request);
        // nếu có id
        $this->repository->resetDefaultParams();
        $this->repository->resetTrashed();
        $this->callCrudEvent('prepareDelete', $request, $ids);
        $errors = [];
        if (count($ids) && count($list = $this->repository->get([$this->primaryKeyName => $ids]))) {
            $data = [];
            foreach ($list as $result) {
                $id = $result->{$this->primaryKeyName};
                $canDel = $result->canDelete();
                if ($canDel === true) {
                    // gọi hàm sự kiện truoc khi xóa
                    $this->callCrudEvent('beforeDelete', $result);
                    $this->fire('deleting', $this, $result);

                    // chuyen vao thung ra

                    if ($this->repository->delete($id)) {
                        // gọi hàm sự kiện truoc khi xóa
                        $this->callCrudEvent('afterDelete', $result);

                        $this->fire('deleted', $this, $result);

                        $data[] = $id;

                        $status = true;
                    }
                    else{
                        $errors[] = "Bạn không thể xóa $this->moduleName " . ($result->title ? $result->title : ($result->name ? $result->name : ($result->label ? $result->label : 'có id ' . $id))) . " này được";
                    }
                } elseif (!is_bool($canDel) && is_string($canDel) && strlen($canDel)) {
                    $errors[] = $canDel;
                } else {
                    $errors[] = "Bạn không thể xóa $this->moduleName " . ($result->title ? $result->title : ($result->name ? $result->name : ($result->label ? $result->label : 'có id ' . $id))) . " này vào thùng rác được";
                }
            }

            if ($status) {
                if (($t = count($data)) > 1) {
                    $message = "Đã xóa thành công $t $this->moduleName";
                } else {
                    $message = "Đã xóa $this->moduleName thành công!";
                }
            } else {
                $message = count($errors) == 1 ? $errors[0] : "Không thể chuyển một số mục vào thùng rác được!";
            }
        } else {
            $message = 'Không có mục nào được chọn';
        }
        return $this->json(compact(...$this->apiSystemVars));
    }


    /**
     * khôi phục bản gi xóa tạm
     * @param Request $request
     */
    public function restore(Request $request)
    {
        extract($this->apiDefaultData);
        $this->repository->resetTrashed();
        $ids = $this->getIdListFromRequest($request);
        // nếu có id
        // return $ids;
        $this->callCrudEvent('prepareRestore', $request, $ids);
        if (count($ids) && count($list = $this->repository->get([$this->primaryKeyName => $ids]))) {
            $data = [];

            foreach ($list as $result) {
                $id = $result->{$this->primaryKeyName};
                // gọi hàm sự kiện truoc khi khôi phục
                $this->callCrudEvent('beforeRestore', $result);
                $this->fire('restoring', $this, $result);

                // chuyen vao thung ra

                $this->repository->restore($id);

                // gọi hàm sự kiện truoc khi khôi phục
                $this->callCrudEvent('afterRestore', $result);

                $this->fire('restored', $this, $result);

                $data[] = $id;

                $status = true;
            }
            if (!$status) {
                $message = 'Có vẻ như thao tác không hợp lệ';
            }
        } else {
            $message = 'Không có mục nào được chọn';
        }
        return $this->json(compact(...$this->apiSystemVars));
    }
}
