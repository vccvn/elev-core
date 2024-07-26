<?php

namespace Gomee\Models;


trait ModelFileMethods
{
    /**
     * lấy về dường dẫn bí mật của user
     *
     * @param string $path
     * @return string
     */
    public function getSecretPath($path = null)
    {
        return env('STATIC_CONTENT_PATH', 'static/contents'). ($path?'/'.ltrim($path):'');
    }
}
