<?php

namespace App\Repositories\FOLDER;

use Gomee\Repositories\BaseRepository;
use App\Masks\FOLDER\NAMEMask;
use App\Masks\FOLDER\NAMECollection;
use App\Models\MODEL;
use App\Validators\FOLDER\NAMEValidator;
use Illuminate\Http\Request;

/**
 * @method NAMECollection<NAMEMask>|MODEL[] filter(Request $request, array $args = []) lấy danh sách MODEL được gán Mask
 * @method NAMECollection<NAMEMask>|MODEL[] getFilter(Request $request, array $args = []) lấy danh sách MODEL được gán Mask
 * @method NAMECollection<NAMEMask>|MODEL[] getResults(Request $request, array $args = []) lấy danh sách MODEL được gán Mask
 * @method NAMECollection<NAMEMask>|MODEL[] getData(array $args = []) lấy danh sách MODEL được gán Mask
 * @method NAMECollection<NAMEMask>|MODEL[] get(array $args = []) lấy danh sách MODEL
 * @method NAMECollection<NAMEMask>|MODEL[] getBy(string $column, mixed $value) lấy danh sách MODEL
 * @method NAMEMask|MODEL getDetail(array $args = []) lấy MODEL được gán Mask
 * @method NAMEMask|MODEL detail(array $args = []) lấy MODEL được gán Mask
 * @method NAMEMask|MODEL find(integer $id) lấy MODEL
 * @method NAMEMask|MODEL findBy(string $column, mixed $value) lấy MODEL
 * @method NAMEMask|MODEL first(string $column, mixed $value) lấy MODEL
 * @method MODEL create(array $data = []) Thêm bản ghi
 * @method MODEL update(integer $id, array $data = []) Cập nhật
 */
class NAMERepository extends BaseRepository
{
    /**
     * class chứ các phương thức để validate dử liệu
     * @var string $validatorClass 
     */
    protected $validatorClass = NAMEValidator::class;
    /**
     * tên class mặt nạ. Thường có tiền tố [tên thư mục] + \ vá hậu tố Mask
     *
     * @var string
     */
    protected $maskClass = NAMEMask::class;

    /**
     * tên collection mặt nạ
     *
     * @var string
     */
    protected $maskCollectionClass = NAMECollection::class;


    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return \App\Models\MODEL::class;
    }

}