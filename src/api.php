<?php
define("NO_HACK", 1);
ini_set("display_errors", 1);

//Загружаем необходимые файлы
include "./system/data/config.php";
include "./system/api/BotModel.php";
include "./system/autoload.php";

//Подключаемся к главному mysql серверу
$config = getConfig();
$mysqli = mysqli_connect(
    $config['mysql_server'],
    $config['mysql_username'],
    $config['mysql_password'],
    $config['mysql_database']
);

//Завершаем работу скрипта, если не удаётся соединиться с сервером
if (!$mysqli) {
    die("Failed to connect to mysql server");
}

//Получаем данные
if(!isset($_GET["method"], $_GET["session_token"])){
    sendError(0);
}

$method = $_GET["method"];
$session_token = $_GET["session_token"];

$params = $_GET;
unset($params["method"]);
unset($params["session_token"]);

$session_token_data_loader = $mysqli->query("SELECT * FROM `session_tokens` WHERE `session_token` = '$session_token'");
if($session_token_data_loader->num_rows==0){
    sendError(1);
}

if($method=="get_bots"){
    $bots = [];

    $request = $mysqli->query("SELECT `id`, `group_id`, `is_included`, `secret_key`, `confirmation_key`, `name` FROM `bots`");
    while($bot = $request->fetch_array()){
        $bots[] = new BotModel($bot);
    }
    sendResponse($bots);
}else if(!isset($_GET["bot_id"]))
    sendError(3);

$request = $mysqli->query("SELECT `id`, `group_id`, `is_included`, `secret_key`, `confirmation_key`, `name` FROM `bots` WHERE `id` = {$_GET['bot_id']}");
$bot_data = $request->fetch_assoc();

//Подготавливем список модулей движка
$modules = [];

//Ищем доступные модули
foreach(glob("./system/modules/*Module.php") as $module_path){

    //Узнаем имя модуля
    preg_match('/.\/system\/modules\/(.*).php/', $module_path, $output);
    $module_classname = $output[1];

    //Загружаем файл модуля
    require_once $module_path;

    //Подсоединяем модуль
    new $module_classname();
}

$response = sendApiCall($method, $params);
if(!is_array($response))
    sendError(4);

sendResponse($response);

function setupModule(Module $module){
    global $mysqli, $modules, $bot_data;
    $module->mysqli = $mysqli;
    $module->botId = (int) $bot_data["id"];
    $module->bot_data = $bot_data;

    $modules[$module->name] = $module;
}

function setupBot(Bot $bot){}


function sendError(int $code){
    echo json_encode(["error"=>$code]);
    die;
}

function sendResponse($data){
    echo json_encode(["response"=>$data]);
    die;
}

function sendApiCall(string $method, array $params){
    global $modules;

    /**
     * @var $module Module
     */
    try {
        foreach ($modules as $module) {
            if(is_array($module->onApiCall($method, $params))){
                return $module->onApiCall($method, $params);
            }
        }
    }catch(Exception $exception){
        foreach ($modules as $module) {
            $module->onError($exception->getMessage());
        }
    }

    return false;
}