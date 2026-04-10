<?php

namespace Test;

$success = 0;
$failure = 0;
$testNames = [];

function assertEqual($a, $b, $message="") {
    if ($a != $b) {
        global $failure;
        $failure ++;
        $msg = $message ? $message : "'" . print_r($a, true) . "' != '" . print_r($b, true) . "'";
        echo "\n      FAIL: " . $msg;
    } else {
        global $success;
        $success ++;
        echo "\n      success";
    }
}

function assertTrue($a, $message="") {
    assertEqual($a, true, $message ?: "expected true, got false");
}

function assertFalse($a, $message="") {
    assertEqual($a, false, $message ?: "expected false, got true");
}

function assertThrows($callable, $expectedMessage=null) {
    try {
        $callable();
        global $failure;
        $failure ++;
        echo "\n      FAIL: expected exception but none was thrown";
    } catch (\Exception $e) {
        if ($expectedMessage !== null && strpos($e->getMessage(), $expectedMessage) === false) {
            global $failure;
            $failure ++;
            echo "\n      FAIL: exception message '" . $e->getMessage() . "' does not contain '" . $expectedMessage . "'";
        } else {
            global $success;
            $success ++;
            echo "\n      success";
        }
    }
}

function printHeader($text) {
    echo "\n  " . $text . " test:";
}

function printSubHeader($text) {
    echo "\n    " . $text . ":";
}

function printResults() {
    global $success, $failure;
    echo "\n\n========================================";
    echo "\n  Results: " . ($success + $failure) . " tests, " . $success . " passed, " . $failure . " failed";
    echo "\n========================================\n";
    if ($failure > 0) {
        exit(1);
    }
}
