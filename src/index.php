<?php

include __DIR__."/./../vendor/autoload.php";
//------------------------------------------

use dbpp\DBPP;

if(!isset($argv[0])){
    try {
        $data = json_decode(file_get_contents('php://input'),
            true, 512, JSON_THROW_ON_ERROR);

        if(isset($data["type"])&&$data["type"]!=="confirmation"&&!isset($data['debug'])){
            exec("php -f index.php \"".base64_encode(json_encode($data))."\"",
                $response);

            echo "ok";
            return;
        }

    } catch (JsonException $e) {
        return;
    }
}

ini_set("display_errors", 1);

//Загружаем необходимые файлы
include __DIR__."/./../system/data/config.php";
include __DIR__."/./../system/autoload.php";

//Подключаемся к главному mysql серверу
$database = new MainDatabase();
DBPP::init($database, new PDO("mysql:host=".getConfig()['mysql_server'].
    ";dbname=".getConfig()['mysql_database'].";charset=utf8",
    getConfig()['mysql_username'], getConfig()['mysql_password']));

$mysqli = mysqli_connect(
    getConfig()['mysql_server'],
    getConfig()['mysql_username'],
    getConfig()['mysql_password'],
    getConfig()['mysql_database']
);

//Завершаем работу скрипта, если не удаётся соединиться с сервером
if (!$mysqli) {
    die("Failed to connect to mysql server");
}

//Получаем информацию о запросе от ВК
$data = json_decode(isset($argv[1])?base64_decode($argv[1])
    :file_get_contents('php://input'), true);
$group_id = $data["group_id"];
$secret_key = $data["secret"];

//Для Debug
define("DEBUG", isset($data["debug"])&&$data["debug"]===1);
if(DEBUG)
    ini_set("display_errors", 1);

//Ищем бота по ID группы и по секретному ключу
$bot_data_request = $database->bots->get($group_id, $secret_key);

//Если не нашли, отключаемся
if(!$bot_data_request||count($bot_data_request)===0) {
    die("There is no such bot in this group");
}

//Если нашли, то парсим информацию о боте
$bot = new BotModel($bot_data_request[0]);

//Подготавливем список модулей движка
$modules = [];

//Ищем доступные модули
foreach(glob("../system/modules/*Module.php") as $module_path){

    //Узнаем имя модуля
    preg_match('/..\/system\/modules\/(.*).php/', $module_path, $output);
    $module_classname = $output[1];

    //Загружаем файл модуля
    require_once $module_path;

    //Подсоединяем модуль
    $module = new $module_classname();
    if($module instanceof Module){
        $module->mysqli = $mysqli;
        $module->botId = $bot->id;
        $module->bot = $bot;
        $module->runFromThread = isset($argv[0]);

        $modules[] = $module;
    }
}

//Парсим событие от ВК
$event = new Event($data["type"], isset($data["object"])?$data["object"]:null,
    isset($data["event_id"])?$data["event_id"]:null);

//Отправляем событие модулям
try {
    $dataEvent = new DataEvent(EVENT_VK, $data);
    $dataEvent->event = $event;

    sendEvent($dataEvent);
    sendEvent(new DataEvent(EVENT_STOP_SCRIPT, []));
} catch (Exception $e) {
    if(DEBUG) {
        throw $e;
    }
}

/*
 * Функции
 * */

function setupModule(Module $module){}

function setupBot(Bot $bot){
    global $modules;
    $bot->modules = $modules;
}
