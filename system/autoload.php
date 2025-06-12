<?php
include_once "utils.php";
loadAll(__DIR__."/impl/*.php");
loadAll(__DIR__."/attrs/*.php");
loadAll(__DIR__."/models/*.php");
loadAll(__DIR__."/*.php", [
    __DIR__."/utils.php",
]);