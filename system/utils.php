<?php

use JetBrains\PhpStorm\Language;

function loadAll(#[Language("RegExp")] string $path, array $ignore = []){
    foreach(glob($path) as $path) {
        if(!in_array($path, $ignore)) {
            require_once $path;
        }
    }
}

function sendEvent(DataEvent $event){
    global $modules;

    try {
        foreach ($modules as $module) {
            $module->onNewEvent($event);
        }
    }catch(Exception $exception){
        if(DEBUG)
            throw $exception;

        foreach ($modules as $module) {
            $module->onError($exception->getMessage());
        }
    }
}