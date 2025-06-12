<?php

const ACCESS_TOKEN = "<Токен от аккаунта ВК>";

if (php_sapi_name() != "cli") {
    echo "Access denied";
    die(403);
}

$countries = [];

while(true){
    $mysqli = new mysqli("localhost", "bot", "password", "bot_gamebot");
    if($response = $mysqli->query("SELECT * FROM `countries`")){
        while($country = $response->fetch_array()){
            $nextVoting = $country["last_voting"]+2628000;
            $endVoting = $nextVoting+86400;
            $isVotingDay = $nextVoting<=strtotime('now 00:00:00')&&
                $endVoting>=strtotime('now 00:00:00');

            if($isVotingDay&&!isset($countries[$country['id']])){
                sendToUsersInCountry($country['id'], "В вашей стране начались выборы!
                    Зайдите в меню и проголосуйте -- от этого зависит будущее вашей страны!");
                $countries[$country['id']] = 1;
            }else if(!$isVotingDay&&isset($countries[$country['id']])&&$countries[$country['id']]==1){
                $countries[$country['id']] = 2;

                $candidates = [];
                if($response = $mysqli->query("SELECT `peer_id` FROM `users` WHERE `country` = {$country['id']}")){
                    while($user = $response->fetch_array()){
                        if($user['selected_candidate']!=0){
                            if(!isset($candidates[$user['selected_candidate']]))
                                $candidates[$user['selected_candidate']] = 0;

                            $candidates[$user['selected_candidate']]++;
                        }
                    }
                }

                $newPresident = array_search(max($candidates), $candidates);
                $mysqli->query("UPDATE `users` SET `selected_candidate` = '0' WHERE `country` = {$country['id']}");
                $mysqli->query("UPDATE `countries` SET 
                       `president_id` = '$newPresident'
                       `last_voting` = UNIX_TIMESTAMP() WHERE `id` = {$country['id']}");


                sendToUsersInCountry($country['id'], "Поздравляем!
                        Новый президент выбран!\nПосмотрите его на вкладке \"Страна\"");
                unset($countries[$country['id']]);
            }
        }
    }

    sleep(60);
}

function sendToUsersInCountry(int $country, string $text){
    global $mysqli;
    if($response = $mysqli->query("SELECT `peer_id` FROM `users` WHERE `country` = $country")){
        while($user = $response->fetch_array()){
            try{
                var_dump(file_get_contents("https://api.vk.com/method/messages.send?".http_build_query([
                        "v"=>5.103,
                        "message"=>$text,
                        "random_id"=>0,
                        "peer_id"=>$user['peer_id'],
                        "access_token"=>ACCESS_TOKEN
                    ])));
            }catch (Exception $e){}
        }
    }
}