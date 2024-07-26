<?php

namespace Gomee\Services\Traits;

use Gomee\Helpers\Arr;
use Illuminate\Http\Request;

use Gomee\Files\Filemanager;
use Gomee\Files\Image;
use Gomee\Engines\Helper;
use Gomee\Files\File;
use Gomee\Files\FileUpdated;

// use JamesHeinrich\GetID3\GetID3;

/**
 * các thuộc tính và phương thức của form sẽ được triển trong ManagerController
 */
trait FileMethods
{

    /**
     * @var Filemanager $filemanager
     */
    protected $filemanager = null;

    protected $makeThumbnail = false;


    public $storagePath = '';

    public function jsonPath($path = null)
    {
        return Helper::jsonPath($path);
    }

    public function parsePath($path)
    {
        if ($path == substr($base = base_path(), 0, strlen($path))) return $path;
        if (str_replace('static/contents', '', $path)  != $path) $p = Helper::public_path('/');
        elseif ($this->storagePath == substr($base, 0, strlen($this->storagePath))) {
            $p = $this->storagePath;
        } else {
            $p = Helper::public_path('static/contents');
        }
        return rtrim($p, '/') . '/' . ltrim($path);
    }

    public function fileInit()
    {
        if (!$this->storagePath) $this->storagePath = Helper::storage_path('uploads');
        $this->filemanager = new Filemanager();
    }

    public function getFilemanager($dir = null)
    {
        return new Filemanager($dir);
    }



    /**
     * luu file tai thu muc public
     * @param string $dir
     * @param string $filename
     * @param mixed $content
     * @param string $mime_type
     * @return File|null
     */
    public function saveFile($dir = null, $filename = null, $content = null, $mime_type = null)
    {
        if ($filename) {
            $file = new Filemanager($dir);
            return $file->save($filename, $content, $mime_type);
        }
        return null;
    }

    /**
     * luu file tai thu muc public
     * @param string $dir
     * @param string $filename
     * @param mixed $content
     * @param string $mime_type
     * @return File|null
     */
    public function savePublicFile($dir = null, $filename = null, $content = null, $mime_type = null)
    {
        return $this->saveFile(Helper::public_path($dir), $filename, $content, $mime_type);
    }

    /**
     * upload file
     * @param Request $request
     * @param string $field
     * @param string $filenameWithoutExtension
     * @param string $path
     * @return FileUpdated
     */
    public function uploadSingleFile($file, $filenameWithoutExtension = false, $path = null)
    {
        if (!$path) $path = $this->parsePath($this->module);
        $extension = strtolower($file->getClientOriginalExtension());
        $original_filename = $file->getClientOriginalName();

        // neu co ten file cu
        if (is_bool($filenameWithoutExtension) || $filenameWithoutExtension === true || $filenameWithoutExtension === false) {
            $attachment = $this->getFilenameWithoutExtension($original_filename, $extension) . ($filenameWithoutExtension == true ? '-' . uniqid() : '');
        } elseif ($fn = $this->getFilenameWithoutExtension($filenameWithoutExtension, $extension)) {
            $attachment = $fn;
        } else {
            $attachment = $this->getFilenameWithoutExtension($original_filename, $extension) . '-' . uniqid();
        }


        $filename = $attachment . '.' . $extension;

        $mime = $file->getClientMimeType();
        $ftype = explode('/', $mime);
        $filetype = $ftype[0];

        if (!$file->move($path, $filename)) return false;
        // den day là cus3 laravel
        $filepath = rtrim($path, '/') . '/' . ltrim($filename, '/');
        // $this->filemanager->chmod($filepath, 0755);
        $size = filesize($filepath) / 1024;

        $f = new Arr(compact('filename', 'original_filename', 'filepath', 'extension', 'mime', 'size', 'filetype'));
        if ($filetype == 'audio' || $filetype == 'video') {
            $getID3 = null;
            try {
                if (class_exists('james-heinrich/getid3')) {
                    $getID3 = app('JamesHeinrich\GetID3\GetID3');
                } elseif (class_exists('GetID3')) {
                    $getID3 = app('GetID3');
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
            if ($getID3) {

                $info = $getID3->analyze($filepath);
                $f->info = $info;
            } else {
                $f->info = [];
            }
        } else {
            $f->info = [];
        }

        return $f;
    }

    /**
     * upload file
     * @param Request $request
     * @param string $field
     * @param string $filenameWithoutExtension
     * @param string $path
     * @return FileUpdated
     */
    public function uploadFile(Request $request, $field = 'file', $filenameWithoutExtension = true, $path = null)
    {
        if ($request->hasFile($field)) {
            if (!$path) $path = $this->parsePath($this->module);
            $this->filemanager->setDir($path, true);
            $destinationPath = $this->filemanager->getDir();
            if (!is_dir($destinationPath)) {
                $this->filemanager->makeDir($destinationPath, 0755);
            }
            $file = $request->file($field);
            return $this->uploadSingleFile($file, $filenameWithoutExtension, $destinationPath);
        }
        return false;
    }

    /**
     * upload nhiều file
     * @param Request $request
     * @param string $field
     * @param string $filenameWithoutExtension
     * @param string $path
     * @return FileUpdated[]
     */
    public function uploadMultiFile(Request $request, $field = 'file', $filenameWithoutExtension = true, $path = null)
    {

        if ($request->hasFile($field)) {
            if (!$path) $path = $this->parsePath($this->module);
            $this->filemanager->setDir($path, true);
            $destinationPath = $this->filemanager->getDir();
            if (!is_dir($destinationPath)) {
                $this->filemanager->makeDir($destinationPath, 0755);
            }

            $files = $request->file($field);
            if ($t = count($files)) {
                $list = [];
                foreach ($files as $i => $file) {
                    if ($f = $this->uploadSingleFile($file, $filenameWithoutExtension . ($filenameWithoutExtension && $t > 1 ? '-' . ($i + 1) : ''), $path)) {
                        $list[] = $f;
                    }
                }
                return $list;
            }
        }
        return [];
    }


    /**
     * lưu một file ảnh sau khi crop
     *
     * @param string $image_path
     * @param integer $width
     * @param integer $height
     * @param string $path
     * @return bool
     */
    public function saveImageCrop($image_path, $filename, $width = 500, $height = 500, $path = null)
    {

        $thumb = new Image($image_path);
        if ($thumb->check()) {
            if ($thumb->getWidth() < $width && $thumb->getHeight() < $height) return false;
            $thumb->resizeAndCrop($width, $height);
            if (!$path) $path = $this->parsePath($this->module);
            // $path = rtrim($path, '/').'/thumbs';
            $this->filemanager->setDir($path, true);
            $destinationPath = $this->filemanager->getDir();
            if ($thumb->save($path . '/' . $filename)) {
                return true;
            }
        }
        return false;
    }

    /**
     * save base64 file data
     * @param string $base64
     * @param string $filenameWithoutExtension
     * @param string $path
     */
    public function saveBase64File($base64, $filenameWithoutExtension = null, $path = null, $extension = null)
    {
        if ($file = $this->filemanager->getBase64Data($base64)) {
            if (!$path) $path = $this->parsePath($this->module);
            $this->filemanager->setDir($path, true);
            // neu co ten file cu
            if ($fn = $this->getFilenameWithoutExtension($filenameWithoutExtension)) {
                $attachment = $fn;
            } else {
                $attachment = str_slug(microtime(), '-');
            }
            $filename = $attachment . '.' . $file->extension;
            return $this->filemanager->save($filename, $file->data, $file->extension);
        }

        return false;
    }


    /**
     * save base64 file data
     * @param string $base64
     * @param string $filenameWithoutExtension
     * @param string $path
     */
    public function saveBase64Image($base64, $filenameWithoutExtension = null, $path = null, $extension = null)
    {
        $extension = $this->parseImageFileExtension($extension);
        if ($file = $this->filemanager->getBase64Data($base64)) {
            if (!$path) $path = $this->parsePath($this->module);

            $this->filemanager->setDir($path, true);
            // neu co ten file cu
            if ($fn = $this->getFilenameWithoutExtension($filenameWithoutExtension)) {
                $attachment = $fn;
            } else {
                $attachment = str_slug(microtime(), '-');
            }
            $filename = $attachment . '.' . $file->extension;
            if ($save = $this->filemanager->save($filename, $file->data, $file->extension)) {



                if ($extension && $extension != $file->extension) {
                    $p = $this->filemanager->getDir();
                    $image = new Image($save->path);

                    $fn2 = $attachment . '.' . $extension;
                    if ($image->save($pth = $p . '/' . $fn2, $extension)) {
                        $save->filename = $fn2;
                        $save->path = $pth;
                        $save->extension = $extension;
                        $this->filemanager->delete($filename);
                    }
                }
                return $save;
            }

            return null;
        }

        return false;
    }

    /**
     * lấy tên file ko có phần mở rộng
     * @param string $filenameWithoutExtension
     * @return string|null
     */
    public function getFilenameWithoutExtension($filenameWithoutExtension = null, $extension = null)
    {
        if ($filenameWithoutExtension) {
            $of = explode('.', $filenameWithoutExtension);
            $ext = array_pop($of);
            if (($extension && strtolower($extension) == strtolower($ext)) || $mime = $this->filemanager->getMimeType($ext)) {
                $filename = implode('.', $of);
            } else {
                $filename = $filenameWithoutExtension;
            }


            if ($filename) return $filename;
        }
        return null;
    }


    /**
     * kiem tra path
     * @param string
     */
    public function checkPath($path = null)
    {
        $path = rtrim(rtrim($path, "\\"), '/');
        $base = rtrim(rtrim(base_path(''), "\\"), '/');
        if (count($p = explode($base, $path)) == 2) return true;
        return false;
    }

    /**
     * upload image or base 64
     * @param Request $request
     * @param string $field
     * @param string $field
     * @param string $filenameWithoutExtension
     * @param string $path
     *
     * @return string filename
     */
    public function saveImageFileData(Request $request, $field = 'image', $filenameWithoutExtension = null, $path = null, $width = null, $height = null)
    {
        $file = null;
        if ($this->makeThumbnail) {
            if ($request->hasFile($field) && $fileUpload = $this->uploadFile($request, $field, $filenameWithoutExtension, $path)) {
                // gan cho abatar gia tri moi
                $file = $fileUpload->filename;

                // nếu nhu có dử liệu file  ở dạng base 64, không yêu cầu giử nguyên kích thước, và upload thành công
                if ($request->input($field . '_data') && $fileSaved = $this->saveBase64Image($request->input($field . '_data'), $filenameWithoutExtension, $path . '/thumbs', $fileUpload->extension)) {
                    if (!file_exists(public_path($path . '/thumbs/' . $file))) {
                        $b = new Image($fileSaved->path);
                        $b->save(public_path($path . '/thumbs/' . $file), $fileUpload->mime);
                    }
                } elseif (is_numeric($width) && is_numeric($height) && $width > 0 && $height > 0) {
                    $this->saveImageCrop($fileUpload->filepath, $file, $width, $height, $path . '/thumbs');
                }
            }
            // nếu nhu có dử liệu file  ở dạng base 64, không yêu cầu giử nguyên kích thước, và upload thành công
            elseif ($request->input($field . '_data') && $fileSaved = $this->saveBase64File($request->input($field . '_data'), $filenameWithoutExtension, $path)) {
                // gan cho abatar gia tri moi
                $file = $fileSaved->filename;
                if (is_numeric($width) && is_numeric($height) && $width > 0 && $height > 0) {
                    $this->saveImageCrop($fileSaved->path, $file, $width, $height, $path . '/thumbs');
                } else {
                    $this->filemanager->copyFile($fileSaved->path, public_path($path . '/thumbs'));
                }
            }
        } else {
            // nếu nhu có dử liệu file  ở dạng base 64, không yêu cầu giử nguyên kích thước, và upload thành công
            if ($request->input($field . '_data') && !$request->input($field . '_keep_original') && $fileSaved = $this->saveBase64File($request->input($field . '_data'), $filenameWithoutExtension, $path)) {
                // gan cho abatar gia tri moi
                $file = $fileSaved->filename;
            } elseif ($request->hasFile($field) && $fileUpload = $this->uploadFile($request, $field, $filenameWithoutExtension, $path)) {

                $file = $fileUpload->filename;
                if (is_numeric($width) && is_numeric($height) && $width > 0 && $height > 0) {
                    $this->saveImageCrop($fileUpload->filepath, $file, $width, $height, $path);
                }
            }
        }

        // ngược lại nếu có file và upload thành công

        return $file;
    }

    /**
     * upload attach file
     * @param Request $request
     * @param Arr $data
     * @param string $field
     * @param string $path
     *
     * @return void
     */
    public function uploadImageAttachFile(Request $request, Arr $data, string $field = 'image', $path = null, $width = null, $height = null)
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            $extension = strtolower($file->getClientOriginalExtension());
            $otge = $extension;
            if ($extension == 'jpeg') {
                $extension = 'jpg';
            }
            $original_filename = $file->getClientOriginalName();
            $filename = str_slug($this->getFilenameWithoutExtension($original_filename, $otge)) . '-' . uniqid();

            $fn = null;
        } else {
            $fn = null;
            $filename = null;
        }

        // neu 1 tromg 2 uploaf thanh cong
        if ($image = $this->saveImageFileData($request, $field, $filename, $path, $width, $height)) {
            // nếu ảnh cũ khác ảnh mới thì xóa ảnh cũ
            if ($fn && $fn != $image) {
                $this->repository->deleteAttachFile($request->id);
            }
            $data->{$field} = $image;
        } else {
            $data->remove($field);
        }
    }



    /**
     * upload attach file
     * @param Request
     * @param Arr $data
     * @param string $field
     * @param string $path
     *
     * @return void
     */
    public function uploadAttachFile(Request $request, Arr $data, string $field = 'image', $path = null)
    {
        $filename = $field;
        // neu 1 tromg 2 uploaf thanh cong
        if ($image = $this->uploadFile($request, $field, $filename, $path)) {
            // nếu ảnh cũ khác ảnh mới thì xóa ảnh cũ
            $data->{$field} = $image->filename;
        } else {
            $data->remove($field);
        }
        // dd($data->all());
    }



    /**
     * upload image or base 64
     * @param Request $request
     * @param string $field
     * @param string $filenameWithoutExtension
     * @param string $path
     * @param string $resize
     * @param int $width
     * @param int $height
     *
     * @return Arr|null filename
     */
    public function uploadImage(Request $request, $field = 'image', $filenameWithoutExtension = null, $path = null, $resize = false, $width = null, $height = null)
    {
        $file = null;

        // ngược lại nếu có file và upload thành công
        if ($request->hasFile($field) && $fileUpload = $this->uploadFile($request, $field, $filenameWithoutExtension, $path)) {
            // gan cho abatar gia tri moi
            $file = $fileUpload;
            if ($resize && $width && $height && $fileUpload->extension != 'svg') {
                if (!$path) $path = $this->parsePath($this->module);
                $path .= "/{$width}x{$height}";
                $this->filemanager->setDir($path, true);
                $dir = $this->filemanager->getDir();

                $image = new Image($fileUpload->filepath);
                if ($image->check()) {
                    $image->resizeAndCrop($width, $height);
                    $image->save($dir . '/' . $file->filename);
                }
            }
        }
        return $file;
    }



    /**
     * upload image or base 64
     * @param Request $request
     * @param string $field
     * @param string $filenameWithoutExtension
     * @param string $path
     * @param string $resize
     * @param int $width
     * @param int $height
     *
     * @return Arr|null filename
     */
    public function uploadMedia(Request $request, $field = 'image', $filenameWithoutExtension = null, $path = null, $resize = false, $width = null, $height = null)
    {
        $file = null;

        // ngược lại nếu có file và upload thành công
        if ($request->hasFile($field) && $fileUpload = $this->uploadFile($request, $field, $filenameWithoutExtension, $path)) {
            // gan cho abatar gia tri moi
            $file = $fileUpload;
            if ($resize && $width && $height && $fileUpload->filetype == 'image' && $fileUpload->extension != 'svg') {
                if (!$path) $path = $this->parsePath($this->module);
                $path .= "/{$width}x{$height}";
                $this->filemanager->setDir($path, true);
                $dir = $this->filemanager->getDir();

                $image = new Image($fileUpload->filepath);
                if ($image->check()) {
                    $image->resizeAndCrop($width, $height);
                    $image->save($dir . '/' . $file->filename);
                }
            }
        }
        return $file;
    }

    public function makeSocialImage(Arr $data, $folder = null)
    {
        if ($data->featured_image && $folder) {
            $image = new Image($this->parsePath($folder . DIRECTORY_SEPARATOR . $data->featured_image));
            $sw = $this->socialImageWidth;
            $sh = $this->socialImageHeight;
            $imgW = $image->getWidth();
            $imgH = $image->getHeight();
            if ($imgW > $sw && $imgH > $sh) {
                $image->resizeAndCrop($sw, $sh);
                $this->featureImageWidth = $sw;
                $this->featureImageHeight = $sh;
            } elseif ($imgW > 400 && $imgW < 500) {
                $sw = 480;
                $sh = 250;
                $image->resizeAndCrop($sw, $sh);
                $this->featureImageWidth = $sw;
                $this->featureImageHeight = $sh;
            } elseif ($imgW >= 500) {
                $sw = 526;
                $sh = 275;
                $image->resizeAndCrop($sw, $sh);
                $this->featureImageWidth = $sw;
                $this->featureImageHeight = $sh;
            } else {
                $this->featureImageWidth = $image->getWidth();
                $this->featureImageHeight = $image->getHeight();
            }
            $image->save($this->parsePath($folder . '/social/' . $data->featured_image));
        }
    }

    public function parseImageFileExtension($extension = null)
    {
        if ($extension == 'jpeg') $extension = 'jpg';
        return $extension;
    }
}
