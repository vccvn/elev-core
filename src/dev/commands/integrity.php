<?php

if (!function_exists('integrityGenerate')) {

    function generate_sri_hash($file, $algo = 'sha256')
    {
        return base64_encode(hash($algo, $file, true));
    }

    function generate_sri_openssl($file, $algo = 'sha256')
    {
        return base64_encode(openssl_digest($file, $algo, true));
    }

    function integrityGenerate($file)
    {
        if (preg_match('/^(http|https)\:\/\//', $file)) $content = file_get_contents($file);
        elseif (file_exists($file)) $content = file_get_contents($file);
        elseif (file_exists($path = base_path($file))) $content = file_get_contents($path);
        else $content = null;

        echo generate_sri_hash($content, 'sha256');
        echo "\n";
        echo generate_sri_hash($content, 'sha384');
        echo "\n";
        echo generate_sri_hash($content, 'sha512');

        echo "\n\n";

        echo generate_sri_openssl($content, 'sha256');
        echo "\n";
        echo generate_sri_openssl($content, 'sha384');
        echo "\n";
        echo generate_sri_openssl($content, 'sha512');

        echo "\n";
    }
}
