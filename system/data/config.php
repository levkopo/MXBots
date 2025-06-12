<?php
/**
 * Конфигурационный файл для движка
 * mysql_server - адрес mysql сервера
 * mysql_username - пользователь mysql сервера
 * mysql_password - пароль ползователя mysql сервера
 * mysql_database - название дата базы mysql сервера
 */

$config = [
    "mysql_server"=>"localhost",
    "mysql_username"=>"bot",
    "mysql_password"=>"password",
    "mysql_database"=>"bot_system",
];

function getConfig(){global $config; return $config;}