<?php

namespace PhantomInstaller;

class PhantomBinary
{
    const BIN = '/home/devuser/projects/karma-be-ngo/bin/phantomjs';
    const DIR = '/home/devuser/projects/karma-be-ngo/bin';

    public static function getBin() {
        return self::BIN;
    }

    public static function getDir() {
        return self::DIR;
    }
}
