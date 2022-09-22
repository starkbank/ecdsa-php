<?php

namespace EllipticCurve\Utils;


class Pem
{
    public static function getContent($pem, $template)
    {
        $pattern = "/" . str_replace("{content}", "(.*)", $template) . "/";
        preg_match(
            str_replace("\n", "", $pattern), 
            str_replace("\n", "", $pem), 
            $matches
        );
        return $matches[1];
    }
    
    public static function create($content, $template)
    {
        $lines = [];
        foreach (range(0, strlen($content)-1, 64) as $start) {
            $lines[] = substr($content, $start, 64);
        }
        return str_replace("{content}", join("\n", $lines), $template);
    }
}
