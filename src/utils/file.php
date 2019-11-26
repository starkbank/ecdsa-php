<?php

namespace starkbank\ecdsa_php\utils;

class File {
    public static function read($path, $mode="r") {
        $file = fopen($path, $mode);
        $content = fread($file, filesize(path));
        fclose($file);
        return $content;
    }
}

?>