<?php


use JetBrains\PhpStorm\Pure;

class ModuleNotFoundException extends ErrorException{

    #[Pure] public function __construct(string $module = "", $code = 0, $severity = 1, $filename = __FILE__, $lineno = __LINE__, $previous = null){
        parent::__construct("Module \"$module\" not found. Please setup module!", $code, $severity, $filename, $lineno, $previous);
    }
}