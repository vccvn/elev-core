<?php
if(!function_exists('addShortCodeAsset')){
    /**
     * addShortCodeAsset
     * 
     */
    function addShortCodeAsset($dir, $type = 'theme')
    {
        $base = base_path();
        $d = base_path($dir);
        // $s = base_path($source);
        $filemanager = new Filemanager();
        $list = $filemanager->getList($d);
        // print_r($list);
        // return ;

        for($i = 0; $i < count($list); $i++){
            $file = $list[$i];
            if($file->type == 'folder'){
                addShortCodeAsset($dir . '/' .$file->name);
                continue;
            }
            elseif($file->extension != 'php') continue;
            echo "Đang phân tích file: $file->name...\n";
            $content = $filemanager->getContent($file->path);
            $content = str_replace('@{{{', '<ba-dau-mo-ngoac-nhom>', $content);
                $content = str_replace('{{{', '@{{{', $content);
                    $content = str_replace('<ba-dau-mo-ngoac-nhom>', '@{{{', $content);
            
            preg_match_all('/\<(a|link|script|img)\s.*(href|src)=(\'[^\']+\'|\"[^\"]+\")/i', $content, $matches);
            // print_r($matches[3]);
            for($j = 0; $j < count($matches[1]); $j++){
                $match = $matches[0][$j];
                $tag = $matches[1][$j];
                $attr = $matches[2][$j];
                $link = $matches[3][$j];

                $vl = substr($link, 1, strlen($link) - 2);
                $sub = substr($vl, 0, 7);
                if($sub == 'assets/'){
                    $rvl = "{{{$type}_asset('" . substr($vl, 7) . "')}}";
                    $replace = str_replace($link, "\"$rvl\"", $match);
                    $content = str_replace($match, $replace, $content);
                    echo "Đã sửa $vl -> $rvl\n";
                }
            }
            preg_match_all('/\:\s*url\((\'[^\']+\'|\"[^\"]+\"|[^\)])\)/i', $content, $match2);
            for($j = 0; $j < count($match2[1]); $j++){
                $match = $match2[0][$j];
                $link = $match2[1][$j];

                $vl = str_replace(['"', "'"], '', $link);
                $sub = substr($vl, 0, 7);
                if($sub == 'assets/'){
                    $rvl = "{{{$type}_asset(\"" . substr($vl, 7) . "\")}}";
                    $replace = str_replace($link, "\"$rvl\"", $match);
                    $content = str_replace($match, $replace, $content);
                    echo "Đã sửa $vl -> $rvl\n";
                }
            }
            $filemanager->save($file->path, $content);
            echo "\n";
            // return "a";
        }
        

    }
}