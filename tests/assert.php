<?php

namespace Test;

$success = 0;
$failure = 0;
$currentTestFailed = false;

function assertEqual($a, $b, $message="") {
    if ($a != $b) {
        global $currentTestFailed;
        $currentTestFailed = true;
        $msg = $message ? $message : "'" . print_r($a, true) . "' != '" . print_r($b, true) . "'";
        echo "\n      FAIL: " . $msg;
    } else {
        echo "\n      success";
    }
}

function assertNotEqual($a, $b, $message="") {
    if ($a == $b) {
        global $currentTestFailed;
        $currentTestFailed = true;
        $msg = $message ? $message : "'" . print_r($a, true) . "' == '" . print_r($b, true) . "'";
        echo "\n      FAIL: " . $msg;
    } else {
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
        global $currentTestFailed;
        $currentTestFailed = true;
        echo "\n      FAIL: expected exception but none was thrown";
    } catch (\Exception $e) {
        if ($expectedMessage !== null && strpos($e->getMessage(), $expectedMessage) === false) {
            global $currentTestFailed;
            $currentTestFailed = true;
            echo "\n      FAIL: exception message '" . $e->getMessage() . "' does not contain '" . $expectedMessage . "'";
        } else {
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
