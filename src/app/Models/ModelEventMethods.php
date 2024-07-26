<?php

namespace Gomee\Models;

use Carbon\Carbon;
use Gomee\Engines\Helper;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait ModelEventMethods
{
   
   /**
    * chế độ xóa
    *
    * @var integer
    */
   protected $deleteMode = 0;

   
    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;
    

    public function isSoftDeleteMode()
    {
        return $this->deleteMode == 1 || strtolower($this->deleteMode) == 'soft';
    }


    /**
     * chuyển trạng thái về đã xoa
     * @return boolean
     */
    public function moveToTrash()
    {
        if(!$this->canMoveToTrash()) return false;
        if(in_array('trashed_status', $this->fillable)){
            $this->beforeMoveToTrash();
            $this->trashed_status = 1;
            $sd = $this->save();
            if ($this->isSoftDeleteMode()) {
                // $this->beforeMoveToTrash();
                $delete = parent::delete();
                if ($delete) {
                    $sd = $delete;
                }
            }
            if($sd){
                $this->afterMoveToTrash();
                return true;
            }
            
            
            return false;
        }
        else if($this->isSoftDeleteMode())
        {
            $this->beforeMoveToTrash();
            $delete = parent::delete();
            if($delete){
                $this->afterMoveToTrash();
            }
           
        }
        else{
            return $this->delete();
        }
    }


    /**
     * phương thức sẽ được gọi trước khi chuyển bản ghi vào thùng rác
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function beforeMoveToTrash()
    {
        # code...
        # do something...
        return true;
    }

    /**
     * phương thức sẽ được gọi trước khi chuyển bản ghi vào thùng rác
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function afterMoveToTrash()
    {
        # code...
        # do something...
        return true;
    }

    /**
     * chuyển trạng thái từ đã xoa đã xóa về mình thường
     * @return boolean
     */
    public function restore()
    {
        if(in_array('trashed_status', $this->fillable)){
            $this->beforeRestore();
            $this->trashed_status = 0;
            $this->save();
            if($this->isSoftDeleteMode()){
                $this->sysRestore();
            }
            $this->afterRestore();
            return true;
        }
        elseif($this->isSoftDeleteMode()){
            $this->beforeRestore();
            $this->sysRestore();
            $this->afterRestore();
            return true;
        }
        return false;
    }

    /**
     * phương thức sẽ được gọi trước khi khôi phục bản ghi
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function beforeRestore()
    {
        # code...
        # do something...
        return true;
    }

    /**
     * phương thức sẽ được gọi trước khi khôi phục bản ghi
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function afterRestore()
    {
        # code...
        # do something...
        return true;
    }

    

    /**
     * xóa vĩnh viễn bản ghi
     * @return boolean
     */
    public function erase()
    {
        if(!$this->canDelete()) return false;
        $this->beforeErase();
        $delete = $this->forceDelete();
        $this->afterErase();
        return $delete;
    }

    /**
     * phương thức sẽ được gọi trước khi xóa bản ghi
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function beforeErase()
    {
        # code...
        # do something...
        return true;
    }

    /**
     * phương thức sẽ được gọi trước khi xóa bản ghi
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function afterErase()
    {
        # code...
        # do something...
        return true;
    }


    

    /**
     * xóa vĩnh viễn bản ghi
     * @return boolean
     */
    public function delete()
    {
        
        if(!$this->canDelete()) return false;
        if($this->isSoftDeleteMode())
        {
            $this->beforeMoveToTrash();
            $delete = parent::delete();
            if($delete){
                $this->afterMoveToTrash();
            }
           
        }else{
            $this->forceDeleting = true;
            $this->beforeDelete();
            $delete = parent::delete();
            if($delete){
                $this->afterDelete();
            }
        }
        return $delete;
    }

    /**
     * phương thức sẽ được gọi trước khi xóa bản ghi
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function beforeDelete()
    {
        # code...
        # do something...
        return true;
    }

    /**
     * phương thức sẽ được gọi trước khi xóa bản ghi
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function afterDelete()
    {
        # code...
        # do something...
        return true;
    }

    /**
     * kiểm tra có thể xóa hay không
     * @return boolean
     */
    public function canDelete()
    {
        return true;
    }


    
    
    /**
     * xóa vĩnh viễn bản ghi
     * @return boolean
     */
    public function forceDelete()
    {
        
        if(!$this->canForceDelete()) return false;
        $this->beforeForceDelete();
        $delete = $this->sysForceDelete();
        if($delete){
            $this->afterForceDelete();
        }
        
        
        return $delete;
    }


    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes()
    {
        $this->dates[] = $this->getDeletedAtColumn();
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    protected function sysForceDelete()
    {
        $this->forceDeleting = true;

        return tap(parent::delete(), function ($deleted) {
            $this->forceDeleting = false;

            if ($deleted) {
                $this->fireModelEvent('forceDeleted', false);
            }
        });
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting || !$this->isSoftDeleteMode()) {
            $this->exists = false;

            return $this->setKeysForSaveQuery($this->newModelQuery())->forceDelete();
        }

        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->timestamps && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    protected function sysRestore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return ! is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Register a restoring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a restored model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting()
    {
        return $this->forceDeleting;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        if(defined('static::MODEL_TYPE') && static::MODEL_TYPE == 'mongo') return $this->getDeletedAtColumn();
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }

    
    /**
     * phương thức sẽ được gọi trước khi xóa bản ghi
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function beforeForceDelete()
    {
        # code...
        # do something...
        return true;
    }

    /**
     * phương thức sẽ được gọi trước khi xóa bản ghi
     * vui lòng override lại phương thức này nếu muốn sử dụng
     * @return mixed
     */
    public function afterForceDelete()
    {
        # code...
        # do something...
        return true;
    }

    /**
     * kiểm tra có thể xóa hay không
     * @return boolean
     */
    public function canForceDelete()
    {
        return true;
    }


    /**
     * kiểm tra có thể xóa hay không
     * @return boolean
     */
    public function canMoveToTrash()
    {
        return true;
    }

    /**
     * xóa file đính kèm
     */
    public function deleteAttachFile()
    {
        return true;
    }

    /**
     * lấy tên file cũ
     */
    public function getAttachFilename()
    {
        return null;
    }

    /**
     * xóa dữ liễu trong bảng liên quan
     *
     * @param string|array ...$relations
     * @return bool
     */
    protected function deleteList(...$relations)
    {
        if(count($relations)){
            foreach ($relations as $relationName) {
                if(is_array($relationName)){
                    if(count($relationName)){
                        $rels = array_values($relationName);
                        $this->deleteList(...$rels);
                    }
                }elseif(is_string($relationName)){
                    $this->deleteRelationMany($relationName);
                }
            }
        }
    }

    private function deleteRelationMany($relation)
    {
        if($relations = $this->{$relation}){
            if(count($relations)){
                foreach ($relations as $key => $rel) {
                    $rel->delete();
                }
            }
        }
    }


    

}
