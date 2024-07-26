<?php

namespace Gomee\Models;

use Carbon\Carbon;
use Gomee\Engines\Helper;
use Illuminate\Database\Eloquent\SoftDeletingScope;


use Illuminate\Support\Str;

trait Uuid
{
    /**
     * có sử dụng uuid hay không
     *
     * @var boolean|string
     */
    protected $useUuid = false;
    protected static function boot()
    {
        // Boot other traits on the Model
        parent::boot();

        /**
         * Listen for the 'creating' event on the Track Model.
         * Sets the 'id' to a UUID using Str::uuid() on the instance being created
         */

        static::creating(function ($model) {

            if (!$model->useUuid || $model->useUuid === 'no') return;
            $uuidName = $model->useUuid === true ? 'uuid' : ($model->useUuid === 'primary' ? $model->getKeyName() : $model->useUuid);
            $uuidValue = $model->{$uuidName};
            // Check if the primary key doesn't have a value
            if (!$uuidValue) {
                // Dynamically set the primary key
                $model->setAttribute($uuidName, Str::uuid()->toString());
            }
        });
    }

    // Tells Eloquent Model not to auto-increment this field
    public function getIncrementing()
    {
        if (!$this->useUuid || $this->useUuid == 'no') return true;

        return false;
    }

    // Tells that the IDs on the table should be stored as strings
    public function getKeyType()
    {
        if (!$this->useUuid || $this->useUuid == 'no')
            return parent::getKeyType();
        return 'string';
    }
}
