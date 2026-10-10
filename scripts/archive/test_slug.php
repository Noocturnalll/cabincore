<?php

use Illuminate\Support\Str;

require 'vendor/autoload.php';
$s = Str::slug('DATE', '_');
var_dump($s);
$s = Str::slug('AC REG', '_');
var_dump($s);
