<?php

const GAME_STATE_AGM = -1;
const GAME_STATE_MONEY_TRANSFER = 1;
const GAME_STATE_SHOP = 2;
const GAME_STATE_NEW_JOB = 3;
const GAME_STATE_VKCOIN_LINK = 4;
const GAME_STATE_BUY_LOTTERY = 5;
const GAME_STATE_CHANGE_COUNTRY = 6;
const GAME_STATE_CHANGE_VAT = 7;
const GAME_STATE_CHANGE_NAME = 8;
const GAME_STATE_MAKE_STATEMENT = 9;

class OwlBot extends NBot {

    public VKApiModule $vkApi;
    public mysqli $mysqli;

    private Keyboard $backMenu;
    private Keyboard $mainMenu;
    private Keyboard $countryMenu;
    private Keyboard $topMenu;
    private Keyboard $presidentActions;
    private Keyboard $jobMenu;
    private array $strings;

    public function onModulesLoaded(): void {
        $this->mysqli = new mysqli("localhost", "bot", "password", "bot_gamebot");
    }

    public function bind(){
        $this->backMenu = Keyboard::new()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['back'],
                "payload"=>json_encode(["cmd"=>"menu"]),
            ], "secondary");

        $this->mainMenu = Keyboard::new()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['profile'],
                "payload"=>json_encode(["cmd"=>"profile"]),
            ], "secondary")
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['balance'],
                "payload"=>json_encode(["cmd"=>"balance"]),
            ], "secondary")
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['top'],
                "payload"=>json_encode(["cmd"=>"top"]),
            ], "secondary")
            ->newLine()

            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['shop'],
                "payload"=>json_encode(["cmd"=>"shop"]),
            ], "secondary")
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['work'],
                "payload"=>json_encode(["cmd"=>"job"]),
            ], "secondary")
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['country'],
                "payload"=>json_encode(["cmd"=>"country"]),
            ], "secondary");

        $this->countryMenu = Keyboard::new();

        $this->topMenu = Keyboard::new()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['topAll'],
                "payload"=>json_encode(["cmd"=>"topAll"]),
            ], "positive")
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['topCountry'],
                "payload"=>json_encode(["cmd"=>"topCountry"]),
            ], "secondary")

            ->newLine()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['back'],
                "payload"=>json_encode(["cmd"=>"menu"]),
            ], "secondary");

        $this->presidentActions = Keyboard::new()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['changeVat'],
                "payload"=>json_encode(["cmd"=>"changeCountryVAT"]),
            ], "secondary")
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['changeCountryName'],
                "payload"=>json_encode(["cmd"=>"changeCountryName"]),
            ], "secondary")

            ->newLine()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['territory'],
                "payload"=>json_encode(["cmd"=>"territoryActions"]),
            ], "secondary")
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['maturityUp'],
                "payload"=>json_encode(["cmd"=>"maturityUp"]),
            ], "secondary")


            ->newLine()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['presidentMakeStatement'],
                "payload"=>json_encode(["cmd"=>"makeStatement"]),
            ], "secondary")

            ->newLine()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['back'],
                "payload"=>json_encode(["cmd"=>"menu"]),
            ], "secondary");

        $this->jobMenu = Keyboard::new()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['startJob'],
                "payload"=>json_encode(["cmd"=>"startJob"]),
            ], "secondary")
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['quitJob'],
                "payload"=>json_encode(["cmd"=>"quitJob"]),
            ], "secondary")

            ->newLine()
            ->addButton([
                "type"=>"text",
                "label"=>$this->strings['back'],
                "payload"=>json_encode(["cmd"=>"menu"]),
            ], "secondary");
    }

    /**
     * @param Event $event
     * @throws VKApiException
     */
    public function group_join(Event $event){
        $userId = $event->object['user_id'];
        $response = $this->mysqli->query("SELECT * FROM `users` WHERE `peer_id` = "
            .$userId);
        if(mysqli_num_rows($response)==0){
            $this->mysqli->query("INSERT INTO `users` (`id`, `peer_id`, `shops`, `country`, `created_by`, `money`)
                    VALUES (NULL, '".$userId."', '[]', ".rand(1,3).", ".time().", 20000);");
            return;
        }

        $user = $response->fetch_assoc();
        $this->mysqli->query(
            "UPDATE `users` SET 
                    `money` = ".($user['money']+5000)."
                     WHERE `id` = ".$user['id']);

        $this->vkApi->sendMessage($userId,
            "✔ Вы получили дополнительный бонус за подписку! 5000₽ у вас на счету", null, $this->backMenu);
    }

    /**
     * @param Event $event
     * @throws VKApiException
     */
    public function group_leave(Event $event){
        $userId = $event->object['user_id'];
        $response = $this->mysqli->query("SELECT * FROM `users` WHERE `peer_id` = "
            .$userId);
        if(mysqli_num_rows($response)!=0){
            $user = $response->fetch_assoc();
            $this->mysqli->query(
                "UPDATE `users` SET 
                    `money` = ".($user['money']-5000)."
                     WHERE `id` = ".$user['id']);

            $this->vkApi->sendMessage($userId,
                "✔ Кхм... А бонус то я заберу!\n-5000₽", null, $this->backMenu);
            return;
        }
    }

    /**
     * @param Event $event
     */
    public function wall_post_new(Event $event){
        $attachment = "wall".$event->object['owner_id']."_".$event->object['id'];
        $filters = [];

        if(preg_match('/#страна(.+?);/', $event->object['text'], $output_array)){
            $countryName = $output_array[1];
            var_dump($countryName);

            if(($response = $this->mysqli->query("SELECT * FROM `countries` WHERE `name` = '$countryName'"))
                &&$response->num_rows!=0){
                $country = $response->fetch_assoc();

                $filters[] = "`country` = {$country['id']}";
            }else return;
        }else if(!preg_match('/#важно/', $event->object['text']))
            return;

        if($response = $this->mysqli->query("SELECT `peer_id` FROM `users` ".(sizeof($filters)!=0?"WHERE "
                .implode(" AND ", $filters):""))){
            while($user = $response->fetch_array()){
                try{
                    $this->vkApi->sendMessage($user['peer_id'], "", $attachment);
                }catch (Exception $e){}
            }
        }
    }

    /**
     * @param Event $event
     * @throws VKApiException
     */
    public function message_new(Event $event){
        if(isset($event->object["client_info"])){
            $client_info = $event->object["client_info"];

            $lang_id = (int) $client_info["lang_id"];
            $this->loadLang($lang_id);
        }else
            $this->loadLang(0);

        $this->bind();
        $peerId = (int) $event->object["message"]["peer_id"];
        $from_id = (int) $event->object["message"]["from_id"];

        $thisVkUser = $this->getVKUser($from_id);
        $message = str_replace(["&#13;", "'"], "", $event->object["message"]["text"]);

        $payload = !isset($event->object["message"]["payload"])||
            strlen($event->object["message"]["payload"])==0?[]
            :json_decode($event->object["message"]["payload"], true);

        $response = $this->mysqli->query("SELECT * FROM `users` WHERE `peer_id` = ".$from_id);
        if(mysqli_num_rows($response)==0){
            $this->mysqli->query("INSERT INTO `users` (`id`, `peer_id`, `shops`, `country`, `created_by`) VALUES (NULL, '".$from_id."', '[]', ".rand(1,3).", ".time().");");
            $this->vkApi
                ->sendMessage($from_id, $this->strings['newUser'], $this->mainMenu);
            return;
        }

        $user = $response->fetch_assoc();
        $country = $this->mysqli->query("SELECT * FROM `countries` WHERE `id` = {$user['country']}")
            ->fetch_assoc();

        if(($c = round((time()-$user['recent_satiety_change'])/7200))&&
            $c!=0){
            $user['satiety'] = $user['satiety']-$c;
            $this->mysqli->query("UPDATE `users` SET `satiety` = '{$user['satiety']}' 
                                                                WHERE `users`.`id` = {$user['id']}");
        }

        if($user['satiety']<=0) {
            if($user['recent_satiety_change']==0) {
                $this->mysqli->query("UPDATE `users` SET `satiety` = '100', `recent_satiety_change` = UNIX_TIMESTAMP()
                                                                WHERE `users`.`id` = {$user['id']}");
            }else{
                $this->vkApi->sendMessage($peerId, $this->strings['dieFood'], null, $this->backMenu);
                $this->mysqli->query("DELETE FROM `users` WHERE `users`.`id` = {$user['id']}");
            }
        }

        $isPresident = $country['president_id']==$user['id'];
        $nextVoting = $country["last_voting"]+2628000;
        $isVotingDay = $nextVoting<=strtotime('now 00:00:00')&&
            $nextVoting+86400>=strtotime('now 00:00:00');

        if($isVotingDay){
            $votingButton = [
                "type"=>"text",
                "label"=>$this->strings['voting'],
                "payload"=>json_encode(["cmd"=>"votingDay"]),
            ];

            $this->mainMenu
                ->newLine()
                ->addButton($votingButton, "positive");
            $this->countryMenu->addButton($votingButton, "positive");
        }


        if(isset($payload['cmd'])) {
            $command = $payload['cmd'];
            if($command=="menu"||$command=="main_menu") {
                goto menu;
            }else if($command=="votingDay"){
                //ГОЛОСОВАНИЕ: информация

                if($user['selected_candidate']==0){
                    $candidates = json_decode($country['voting_candidates']);

                    $keyboard = Keyboard::new();

                    $line = 0;
                    foreach($candidates as $candidate){
                        if($candidate!=$user['id']) {
                            $candidate = $this->getUser($candidate);
                            $candidateVK = $this->getVKUser($candidate['peer_id']);

                            if(sizeof($keyboard->buttons[$line])==2) {
                                $keyboard->newLine();
                                $line++;
                            }

                            $keyboard->addButton([
                                "type" => "text",
                                "label" => $candidateVK['first_name']." ".$candidateVK['last_name'],
                                "payload" => json_encode(["cmd" => "vote", "id" => $candidate['id']]),
                                ], "secondary");
                        }
                    }

                    $keyboard->newLine()
                        ->addButton([
                            "type"=>"text",
                            "label"=>$this->strings['back'],
                            "payload"=>json_encode(["cmd"=>"menu"]),
                        ], "secondary");

                    $this->vkApi->sendMessage($peerId, $this->strings['candidates'],
                        null, $keyboard);
                }else{
                    if($candidate = $this->getUser($user['selected_candidate'])){
                        $vkUser = $this->getVKUser($candidate['peer_id']);
                        $this->vkApi->sendMessage($peerId, sprintf($this->strings['alreadySetCandidate'],
                            $this->getVKUserLink($vkUser)),
                            null, $this->backMenu);
                    }else goto menu;
                }
            }else if($command=="vote"){
                //ГОЛОСОВАНИЕ: выбор

                if(!isset($payload['id'])||!$isVotingDay)
                    goto menu;

                $candidateId = $payload['id'];
                if(!in_array($candidateId, json_decode($country['voting_candidates']))
                    ||$candidateId==$user['id']) {
                    $this->vkApi->sendMessage($peerId, $this->strings['unknownCandidate'],
                        null, $this->backMenu);
                    return;
                }

                if($candidate = $this->getUser($candidateId)){
                    $vkUser = $this->getVKUser($candidate['peer_id']);
                    if($user['selected_candidate']!=0) {
                        $this->vkApi->sendMessage($peerId, sprintf($this->strings['alreadySetCandidate'],
                            $this->getVKUserLink($vkUser)), null, $this->backMenu);
                    }else{
                        $this->mysqli->query("UPDATE `users` SET `selected_candidate` = '$candidateId' 
                                    WHERE `users`.`id` = {$user['id']}");
                        $this->vkApi->sendMessage($peerId, sprintf($this->strings['voteCandidate'], $this->getVKUserLink($vkUser)),
                            null, $this->backMenu);
                    }
                }else $this->vkApi->sendMessage($peerId, $this->strings['unknownCandidate'],null,
                    $this->backMenu);
            }else if($command=="country"){
                //СТРАНА: информация

                $votingDate = $isVotingDay?$this->strings['today']:date("d.m.y", $nextVoting);
                $president = $this->getUser($country['president_id']);
                $presidentVK = $this->getVKUser($president['peer_id']);

                if($country['boundaries']==0)
                    $this->countryMenu->addButton([
                        "type"=>"text",
                        "label"=>$this->strings['changeCountry'],
                        "payload"=>json_encode(["cmd"=>"changeCountry"]),
                    ], "secondary");

                if($isPresident){
                    if(sizeof($this->countryMenu->buttons[0])>1)
                        $this->countryMenu->newLine();

                    $this->countryMenu
                        ->addButton([
                            "type"=>"text",
                            "label"=>$this->strings['presidentActions'],
                            "payload"=>json_encode(["cmd"=>"presidentActions"]),
                        ], "negative");
                }

                if(sizeof($this->countryMenu->buttons[sizeof($this->countryMenu->buttons)-1])>1)
                    $this->countryMenu->newLine();
                $this->countryMenu
                    ->addButton([
                        "type"=>"text",
                        "label"=>$this->strings['back'],
                        "payload"=>json_encode(["cmd"=>"menu"]),
                    ], "secondary");

                $this->vkApi->sendMessage($peerId, sprintf($this->strings["countryData"],
                    $country['name'], $this->getVKUserLink($presidentVK), $country['budget'].'₽',
                    $country['vat'], $country['territory'], $votingDate, $country['boundaries'],
                    $this->strings[$country['boundaries']==0?"openBoundariesInfo":"closeBoundariesInfo"],
                    $this->vkApi->asyncRequest("messages.getInviteLink", [
                        "peer_id"=>2000000000+$country['chat_id']
                    ])['link']),
                    null, $this->countryMenu);
            }else if($command=="changeCountryName"){
                //СТРАНА: изменение названия

                if(!$isPresident)
                    goto menu;

                $this->vkApi->sendMessage($peerId, $this->strings['enterCountryName'], null, $this->backMenu);
                $this->setUserState($user['id'], GAME_STATE_CHANGE_NAME);
            }else if($command=="top"){
                //ТОП: меню

                $this->vkApi->sendMessage($peerId, $this->strings['topDesc'], null, $this->topMenu);
            }else if($command=="topAll"||$command=="topCountry"){
                //ТОП: топ страны и общий

                $response = $this->mysqli->query(
                    "SELECT *, `rating` as `top` FROM `users`".
                            ($command=="topCountry"?" WHERE `country` = {$country['id']} ":"").
                            "ORDER BY `top` DESC LIMIT 10");

                $topMenu = '';
                $top = 1;
                while($topUser = $response->fetch_array()){
                    $userInfo = $this->getVKUser($topUser['peer_id']);
                    $topMenu .= sprintf($this->strings['topUserItem'],
                        $top, $this->getVKUserLink($userInfo), $topUser['rating'],
                        $topUser['money'].'₽');
                    $top++;
                }

                $this->vkApi->sendMessage($peerId, $topMenu, null, $this->topMenu);
            }else if($command=="profile"){
                //ПРОФИЛЬ: информация

                $this->profile($peerId, $user, $country);
            }else if($command=="changeCountry"){
                //СТРАНА: переезд

                if($country['boundaries']==1)
                    goto menu;

                $countries = $this->mysqli->query("SELECT * FROM `countries`");

                $countriesMessage = $this->strings['countries'];
                while($countryItem = $countries->fetch_array())
                    if($countryItem['boundaries']==0)
                        $countriesMessage .= sprintf($this->strings['countryItem'],
                            $countryItem['id'], $countryItem['name'], 500 * ($country['vat'] / 100)+500);

                $countriesMessage .= $this->strings['countriesDesc'];
                $this->vkApi->sendMessage($peerId, $countriesMessage, null, $this->backMenu);
                $this->setUserState($user['id'], GAME_STATE_CHANGE_COUNTRY);
            }else if($command=="changeCountryVAT") {
                //СТРАНА: изменение налогов

                if(!$isPresident)
                    goto menu;

                $this->vkApi->sendMessage($peerId, $this->strings['vatChange'], null, $this->backMenu);
                $this->setUserState($user['id'], GAME_STATE_CHANGE_VAT);
            }else if($command=="presidentActions"){
                //СТРАНА: меню президента

                if(!$isPresident)
                    goto menu;

                $this->vkApi->sendMessage($peerId, $this->strings['presidentActions'], null,
                    $this->presidentActions);
            }else if($command=="makeStatement"){
                //СТРАНА: заяавление

                if(!$isPresident)
                    goto menu;

                if($country['last_statement']+86400>time()){
                    $min = date_create("@".($country['last_statement']+86400-time()))
                        ->format("H часов i минут");
                    $this->vkApi->sendMessage($peerId, sprintf($this->strings['presidentMakeStatementTime'], $min), null,
                        $this->presidentActions);
                    return;
                }

                $this->setUserState($user['id'], GAME_STATE_MAKE_STATEMENT);
                $this->vkApi->sendMessage($peerId, $this->strings['presidentMakeStatementDesc'], null,
                    $this->backMenu);
            }else if($command=="territoryActions"){
                //СТРАНА: действия с территорией

                $actions = Keyboard::new()
                    ->addButton([
                        "type"=>"text",
                        "label"=>$this->strings[$country['boundaries']==0?'closeBoundaries':'openBoundaries'],
                        "payload"=>json_encode(["cmd"=>"changeBoundaries"]),
                    ], "secondary");

                $this->vkApi->sendMessage($peerId, $this->strings['territory'], null,
                    $actions);
            }else if($command=="changeBoundaries"){
                //СТРАНА: границы

                if(!$isPresident)
                    goto menu;

                $boundaries = $country['boundaries']==1?0:1;
                $this->mysqli->query("UPDATE `countries` SET `boundaries` = '{$boundaries}' WHERE `countries`.`id` = {$country['id']}");
                $this->vkApi->sendMessage($peerId, $this->strings['done'], null,
                    $this->backMenu);

                $this->vkApi->sendMessage(2000000000+$country['chat_id'],
                    sprintf($this->strings['changedBoundariesMessage'],
                        $this->getVKUserLink($thisVkUser), $boundaries==1?$this->strings['closeBoundariesMessage']:
                            $this->strings['openBoundariesMessage']),
                    null, $this->backMenu);
            }else if($command=="quitJob"){
                //РАБОТА: увольнение

                $this->vkApi->sendMessage($peerId, $this->strings['badSection'], null);
            }else if($command=="shop"){
                //МАГАЗИН: меню

                $shopKeyboard = Keyboard::new();
                $shopKeyboard->addButton([
                    "type"=>"text",
                    "label"=>$this->strings['food'],
                    "payload"=>json_encode(["cmd"=>"food"]),
                ], "secondary");


                $shopKeyboard->newLine()
                    ->addButton([
                        "type"=>"text",
                        "label"=>$this->strings['back'],
                        "payload"=>json_encode(["cmd"=>"menu"]),
                    ], "secondary");

                $this->vkApi->sendMessage($peerId, $this->strings['developSection'], null, $shopKeyboard);
            }else if($command=="food"){
                //МАГАЗИН: еда

                $response = $this->mysqli->query("SELECT * FROM `food`
                        WHERE `min_maturity`<= {$country['maturity']}");

                $allFood = [];
                while($food = $response->fetch_array()){
                    $allFood[] = sprintf($this->strings['foodItem'],
                        $food['id'], $food['name'], $food['price'].'₽', $food['saturability']);
                }

                $this->vkApi->sendMessage($peerId, $this->strings['food'].":\n".
                    implode("\n", $allFood)."\n\n".$this->strings['foodBuy'], null,
                    $this->backMenu);
            }else if($command=="startJob"){
                //РАБОТА: начать работу

                if($user['work']==0||$user['work']==null)
                    goto findWork;

                $work = $this->mysqli->query("SELECT * FROM `works` WHERE `id` = ".$user['work'])
                    ->fetch_assoc();
                if(($user['last_work_time']+900)>time()){
                    $o_min = round((($user['last_work_time']+900)-time())/60);
                    $this->vkApi->sendMessage($from_id, sprintf($this->strings['workAlready'], $o_min), null,
                        $this->backMenu);
                }else{
                    $price = $work['salary'];
                    if($this->changeBalanceFromCountry($user, $country, $price)) {
                        $this->mysqli->query("UPDATE `users` SET `last_work_time` = ".time().", `rating` = ".($user['rating']+10)."
                            WHERE `id` = ".$user['id']);
                        $this->vkApi->sendMessage($peerId, sprintf($this->strings['worked'], $price.'₽'), null,
                            $this->backMenu);
                    }else $this->vkApi->sendMessage($peerId, $this->strings['budgetJob'], null, $this->backMenu);
                }
            }else if($command=="job"){
                //РАБОТА: меню/поиск работы

                if($user['work']==0||$user['work']==null){
                    findWork:
                    $response = $this->mysqli->query("SELECT * FROM `works` WHERE `minimum_rating` <= {$user['rating']} 
                        AND `seats_count` != 0");

                    $works = [];
                    while($work = $response->fetch_array()){
                        $works[] = sprintf($this->strings['workItem'],
                            $work['id'], $work['name'],
                            $work['salary'],
                            $work['seats_count']
                        );
                    }

                    $this->vkApi->sendMessage($peerId,
                    $this->strings['workFindHeader'].implode("\n", $works).
                            $this->strings['workFindFooter'], null, $this->backMenu);
                }else{
                    $this->vkApi->sendMessage($peerId, $this->strings['sectionJob'],
                        null, $this->jobMenu);
                }
            }else if($command == "maturityUp"){
                //СТРАНА: развитие

                if(!$isPresident)
                    goto menu;

                $peopleCount = $this->mysqli->query(
                    "SELECT COUNT(*) as `peopleCount` FROM `users` WHERE `country` = {$country['id']}")
                    ->fetch_assoc()['peopleCount'];
                $price = ($country['maturity']+1)*$peopleCount*30;
                $price = round($price * ($country['vat'] / 100) + $price);

                $budget = $country['budget']-$price;
                if($budget<0){
                    $this->vkApi->sendMessage($peerId,
                        sprintf($this->strings['noBudget'], $price.'₽'),
                        null, $this->backMenu);
                    return;
                }

                $maturity = $country['maturity']+1;
                $this->mysqli->query("UPDATE `countries` SET `maturity` = {$maturity},
                       `budget` = {$budget}
                        WHERE `countries`.`id` = {$country['id']}");
                $this->vkApi->sendMessage($peerId,
                    $this->strings['done'],
                    null, $this->backMenu);
            }
        }else if($user['state']!=null&&!isset($payload['cmd'])){
            $state = $user['state'];
            if($state==GAME_STATE_CHANGE_NAME){
                if(!$isPresident){
                    $this->vkApi->sendMessage($peerId, $this->strings['nonPresident'],
                        null, $this->backMenu);
                    return;
                }

                $message = str_replace(["\n", "\r", " "], "", $message);
                if(strlen($message)==0){
                    $this->vkApi->sendMessage($peerId, $this->strings['countryNameInvalid'],
                        null, $this->backMenu);
                    return;
                }

                $this->vkApi->asyncRequest("messages.editChat", [
                    "chat_id"=>$country['chat_id'],
                    "title"=>"Страна ".$message
                ]);
                $this->vkApi->sendMessage(2000000000+$country['chat_id'],
                    sprintf($this->strings['changedCountryNameMessage'], $this->getVKUserLink($thisVkUser), $message),
                    null, $this->backMenu);
                $this->mysqli->query("UPDATE `countries` SET `name` = '$message' 
                    WHERE `countries`.`id` = {$country['id']}");

                $this->vkApi->sendMessage($peerId, $this->strings['done'],
                    null, $this->backMenu);
                $this->setUserState($user['id'], "NULL");
            }else if($state==GAME_STATE_AGM){

            }else if($state==GAME_STATE_MONEY_TRANSFER){

            }else if($state==GAME_STATE_SHOP){

            }else if($state==GAME_STATE_NEW_JOB){

            }else if($state==GAME_STATE_VKCOIN_LINK){

            }else if($state==GAME_STATE_BUY_LOTTERY){

            }else if($state==GAME_STATE_CHANGE_COUNTRY){
                $myCountry = $country;
                if($country['boundaries']==1)
                    goto menu;

                if(!ctype_digit($message)|| (int) $message <= 0){
                    $country = false;
                }else $message = (int) $message;

                if($country) {
                    $response = $this->mysqli->query("
                        SELECT * FROM `countries` WHERE `id` = '$message'");
                    if($response->num_rows==0)
                        $country = false;
                    else $country = $response->fetch_assoc();
                }

                if(!$country){
                    $this->vkApi->sendMessage($peerId, $this->strings['unknownCountry'], null,
                        $this->backMenu);
                    return;
                }

                if($country['boundaries']!=0){
                    $this->vkApi->sendMessage($peerId, $this->strings['closedBoundariesCountry'], null,
                        $this->backMenu);
                    return;
                }

                $price = 500 * ($country['vat'] / 100)+500;
                if(!$this->changeBalanceFromCountry($user, $myCountry, -$price)) {
                    $this->vkApi->sendMessage($peerId, $this->strings['noMoney'], null, $this->backMenu);
                    return;
                }else $this->vkApi->sendMessage($peerId, sprintf($this->strings['welcomeToCountry'], $country['name']),
                    null, $this->backMenu);

                $this->mysqli->query("UPDATE `users` SET `country` = '{$country['id']}'
                        WHERE `users`.`id` = {$user['id']}");
                $this->setUserState($user['id'], "NULL");
            }else if($state==GAME_STATE_CHANGE_VAT){
                if(!$isPresident){
                    $this->vkApi->sendMessage($peerId, $this->strings['nonPresident'],
                        null, $this->backMenu);
                    return;
                }else if(!ctype_digit($message)|| (int) $message<=0||$message>100) {
                    $this->vkApi->sendMessage($peerId, sprintf($this->strings['vatTooMaxOrMin'], 100),
                        null, $this->backMenu);
                    return;
                }

                $this->mysqli->query("UPDATE `countries` SET `vat` = '$message' 
                        WHERE `countries`.`id` = {$country['id']}");
                $this->setUserState($user['id'], "NULL");

                $this->vkApi->sendMessage($peerId, $this->strings['done'],
                    null, $this->backMenu);
                $this->vkApi->sendMessage(2000000000+$country['chat_id'],
                    sprintf($this->strings['changedCountryVatMessage'],
                        $this->getVKUserLink($thisVkUser), $message),
                    null, $this->backMenu);
            }else if($state==GAME_STATE_MAKE_STATEMENT){
                if($country['last_statement']+86400>time())
                    goto menu;

                $message = sprintf($this->strings['presidentStatement'],
                    $this->getVKUserLink($thisVkUser),
                    $message);

                if($response = $this->mysqli->query("SELECT `peer_id` FROM `users` WHERE `country` = {$country['id']}")){
                    while($user = $response->fetch_array()){
                        try{
                            $this->vkApi->sendMessage($user['peer_id'], $message, null, $this->backMenu);
                        }catch (Exception $e){}
                    }
                }

                $this->mysqli->query("UPDATE `countries` SET `last_statement` = UNIX_TIMESTAMP() WHERE `countries`.`id` = {$country['id']}");
                $this->vkApi->sendMessage(2000000000+$country['chat_id'], $message,
                    null, $this->backMenu);
                $this->setUserState($user['id'], "NULL");
            }else if($peerId==$from_id)
                goto menu;
        }else if(strlen($message)!=0){
            $words = explode(" ", $message);
            if(preg_match("/[Пп]роф(иль|)/", $words[0])) {
                if(isset($words[1])) {
                    if(preg_match("/\[id(.*)\|(.*)]/", $words[1], $data)) {
                        $user = $this->getUserByVK($data[1]);
                    }else $user = $this->getUser((int) $words[1]);

                    if(!$user)
                        goto menu;

                    $country = $this->mysqli->query("SELECT * FROM `countries` WHERE `id` = {$user['country']}")
                        ->fetch_assoc();
                }


                $this->profile($peerId, $user, $country);
            }else if(preg_match("/[Кк]аз(ино|)/", $words[0])&&isset($words[1])){
                $price = -$words[1];
                if(rand(0, 10)==1&&($price += rand(1, $words[1] * 2.5))&&
                        $this->changeBalanceFromCountry($user, $country, $price)){
                    $this->vkApi->sendMessage($peerId, sprintf($this->strings['casinoGood'], $price+$words[1]),
                        null, $this->backMenu);
                }else{
                    $this->changeBalanceFromCountry($user, $country, $price);
                    $this->vkApi->sendMessage($peerId, $this->strings['casinoBad'], null, $this->backMenu);
                }
            }else if(preg_match("/([Кк])уп(ить|)/", $words[0])&&isset($words[1], $words[2])){
                if($words[1]=="еду"){
                    if(($food = $this->mysqli->query("SELECT * FROM `food` WHERE `id` = ".((int) $words[2])))&&
                        $food->num_rows!=0&&$food = $food->fetch_assoc()){
                        $price = $food['price'];

                        if($this->changeBalanceFromCountry($user, $country, -$price)){
                            $this->vkApi->sendMessage($peerId, $this->strings['done'],
                                null, $this->backMenu);
                            $satiety = $user['satiety']+$food['saturability'];
                            if($satiety>100)
                                $satiety = 100;

                            $this->mysqli->query("UPDATE `users` SET `satiety` = '$satiety', 
                                                                `recent_satiety_change` = UNIX_TIMESTAMP()
                                                                WHERE `users`.`id` = {$user['id']}");
                        }else goto menu;
                    }else $this->vkApi->sendMessage($peerId, $this->strings['unknownItem'],
                        null, $this->backMenu);
                }
            }else if(preg_match("/([Уу])строиться/", $words[0])&&isset($words[1])){
                if((int) $words[1]==0)
                    goto findWork;

                if(($work = $this->mysqli->query("SELECT * FROM `works` WHERE `id` = ".$words[1]))&&
                        $work->num_rows!=0&&$work = $work->fetch_assoc()){
                    if($work['seats_count']==0){
                        $this->vkApi->sendMessage($from_id, $this->strings['jobSeatsEnd'], null,
                            $this->backMenu);
                        return;
                    }

                    $work['seats_count']--;
                    $this->mysqli->query("UPDATE `works` SET `seats_count` = 
                            '{$work['seats_count']}' WHERE `works`.`id` = {$work['id']}");
                    $this->mysqli->query("UPDATE `users` SET `work` = {$work['id']} WHERE `id` = ".$user['id']);

                    $this->vkApi->sendMessage($peerId, $this->strings['jobNew'], null, $this->jobMenu);
                }else goto findWork;
            }else goto menu;
        }else {
            menu:
            if($peerId==$from_id){
                $this->setUserState($user['id'], "NULL");
                $this->vkApi->sendMessage($peerId, $this->strings['mainMenu'], null, $this->mainMenu);
            }
        }
    }

    public function changeBalanceFromCountry(array $user, array $country, int $count){
        if($user['money']+$count<0)
            return false;

        if($country['budget']-$count<0)
            return false;

        $newBudget = $country['budget']-$count;
        $newBalance = $user['money']+$count;
        $this->mysqli->query("UPDATE `countries`
                SET `budget` = '{$newBudget}' WHERE `countries`.`id` = {$country['id']}");
        $this->mysqli->query("UPDATE `users`
                SET `money` = '{$newBalance}' WHERE `users`.`id` = {$user['id']}");

        return true;
    }

    /**
     * @param int $peerId
     * @param array $user
     * @param array $country
     * @throws VKApiException
     */
    public function profile(int $peerId, array $user, array $country){
        $moreInfo = '';
        if($user['work']!=null) {
            $work = $this->mysqli->query("SELECT * FROM `works` WHERE `id` = ".$user['work'])
                ->fetch_assoc();
            $moreInfo .= sprintf($this->strings['workProfileData'], $work['name']);
        }
        $shops = json_decode($user['shops']);
        if(sizeof($shops)!=0){
            $moreInfo .= $this->strings['property'];
            $r = $this->mysqli->query("SELECT * FROM `shop` WHERE `id` 
                               IN (".implode(",", $shops).") ORDER BY `type` DESC");

            while ($shopItem = $r->fetch_array()){
                $moreInfo .= '• '.$shopItem['name'].' (ID: '.$shopItem['id'].")\n";
            }
        }

        $this->vkApi->sendMessage($peerId, sprintf($this->strings['profileData'],
            $this->getVKUserLink($this->getVKUser($user['peer_id'])),
            $user['id'], $user['money'].'₽', $user['rating'], $country['name'],
            $user['satiety'],
            date("d.m.Y", $user['created_by']), $moreInfo), null, $this->backMenu);
    }

    /**
     * @param int $price
     * @param array $country
     * @param array $otherCountry
     * @return int
     * @deprecated
     */
    public function countryToOtherCountryCurrency(int $price, array $country, array $otherCountry){
//        $noVatPrice = $otherCountry['budget']/$country["budget"]*$price;
//        return round(($noVatPrice+($noVatPrice * ($country['vat'] / 100))));
        return $price;
    }

    /**
     * @param int $price
     * @param array $country
     * @return int
     * @deprecated
     */
    public function worldToCountryCurrency(int $price, array $country){
        $mcCountry = $this->mysqli->query("SELECT * FROM `countries` WHERE `id` = 4")
                ->fetch_assoc();

        return $this->countryToOtherCountryCurrency($price, $country, $mcCountry);
    }

    public function setUserState(int $id, string|int $state){
        $this->mysqli->query("UPDATE `users` SET `state` = $state WHERE `users`.`id` = $id");
    }

    public function getVKUserLink(array $user): string {
        return "[id{$user['id']}|{$user['first_name']} {$user['last_name']}]";
    }

    /**
     * @param int $peerId
     * @return array
     * @throws VKApiException
     */
    public function getVKUser(int $peerId): array {
        return $this->vkApi->asyncRequest("users.get", ["user_ids"=>$peerId])[0];
    }

    public function getUser(int $id): array|bool{
        if(($response = $this->mysqli->query("SELECT * FROM `users` WHERE `id` = ".$id))
            &&$response->num_rows!=0){
            return $response->fetch_assoc();
        }

        return false;
    }

    public function getUserByVK(int $userId): array|bool{
        if(($response = $this->mysqli->query("SELECT * FROM `users` WHERE `peer_id` = ".$userId))
            &&$response->num_rows!=0){
            return $response->fetch_assoc();
        }

        return false;
    }

    public function loadLang(int $id, bool $f = false){
        $allStrings = yaml_parse_file(__DIR__.'/./OwlBot_files/strings.yaml');
        foreach ($allStrings["langs"] as $lang){
            if($lang["lang_id"]==$id) {
                $this->strings = str_replace('\n', "\n", $lang["strings"]);
                return;
            }
        }

        if(!$f)
            $this->loadLang(0, true);
    }
}