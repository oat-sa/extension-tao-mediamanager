<?php

namespace oat\taoMediaManager\controller;

// Cyclomatic Complexity = 11
class HighCC
{
    public function example()
    {
        $a = $b = $c = 2;
        $a1 = $a2 = $b1 = $b2 = $c = $d = 1;
        $e = $f = $h = $z = 1;

        if ($a == $b) {
            if ($a1 == $b1) {
                fiddle();
            } elseif ($a2 == $b2) {
                fiddle();
            } else {
                fiddle();
            }
        } elseif ($c == $d) {
            while ($c == $d) {
                fiddle();
            }
        } elseif ($e == $f) {
            for ($n = 0; $n < $h; $n++) {
                fiddle();
            }
        } else {
            switch ($z) {
                case 1:
                    fiddle();
                    break;
                case 2:
                    fiddle();
                    break;
                case 3:
                    fiddle();
                    break;
                default:
                    fiddle();
                    break;
            }
        }
    }
}
