<?php

namespace EllipticCurve\Test;
use \EllipticCurve\Test\TestCase;

echo "\n\nRunning Random Integer function tests:";
\Test\printHeader("RandomInteger");


class TestRandomInteger extends TestCase
{
    public function testRandomInteger()
    {
        $min = gmp_init(0);
        $max = gmp_init(2 ** 4 + 1);
        $results = [];
        $success = true;
        for ($i = 0; $i < 1000000; $i++) {
            $integer = \EllipticCurve\Utils\Integer::between($min, $max);
            $key = gmp_strval($integer);
            if (array_key_exists($key, $results)) {
                $results[$key]++;
            } else {
                $results[$key] = 1;
            }
            $success = $success and ($integer >= $min and $integer <= $max);
        }

        // Verify if all numbers in the range were generated
        for ($value = $min; $value <= $max; $value++) {
            $key = gmp_strval($value);
            $success = $success & (($results[$key] ?? 0) > 0);
        }

        \Test\assertEqual($success, true);
        echo "\n The distribution of the generated integers was: \n";
        ksort($results);
        print_r($results);
    }
}


$tests = new TestRandomInteger();
$tests->run();
