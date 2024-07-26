<?php

namespace Gomee\Models;


use Gomee\Constants\DbConnectionConstant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Model as BaseModel;

class SQLModel extends BaseModel
{
    //
    use HasFactory, ModelEventMethods, ModelFileMethods, CommonMethods, Uuid;

    protected $connection = DbConnectionConstant::SQL;

    const MODEL_TYPE = 'sql';
    const UNTRASHED = 0;
    const TRASHED = 1;

    public function __getModelType__()
    {
        return static::MODEL_TYPE;
    }

    
    /**
     * các giá trị mặc định
     *
     * @var array
     */
    protected $defaultValues = [];

    /**
     * lấy về giá trị mặc định khi muốn fill để create data
     *
     * @return array<string, mixed>
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    
    /**
     * Get all of the allLanguageContents for the Model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allLanguageContents()
    {
        $ref = defined('static::REF_KEY') ? static::REF_KEY : ($this->table??'data');
        return $this->hasMany('App\\Models\\MultiLanguageContent', 'ref_id', 'id')->where('ref', $ref);
    }

    /**
     * Get the localeContents associated with the Model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function localeContents()
    {
        $ref = defined('static::REF_KEY') ? static::REF_KEY : ($this->table??'data');
        return $this->hasOne('App\\Models\\MultiLanguageContent', 'ref_id', 'id')->where('ref', $ref)->where('locale', config('app.locale'));
    }
}
