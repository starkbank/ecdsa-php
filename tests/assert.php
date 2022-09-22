<?php

namespace Test;

$success = 0;
$failure = 0;

function assertEqual($a, $b) {
    if ($a != $b) {
        global $failure;
        $failure ++;
        echo "\n      FAIL: '" . $a . "' != '" . $b . "'";
    } else {
        global $success;
        $success ++;
        echo "\n      success";
    }
}

function printHeader($text) {
    echo "\n  " . $text . " test:";
}

function printSubHeader($text) {
    echo "\n    " . $text . ":";
}
