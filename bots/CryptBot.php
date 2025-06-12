<?php

require_once __DIR__."/./CryptBot_files/functions.php";
class CryptBot extends NBot {

    public VKApiModule $vk_api;
    public FileModule $file;
    public AdsModule $ads;

    private MCrypt $MCrypt;
    private mysqli $mysqli;

    private array $all_strings;
    private array $strings = [];

    public function onModulesLoaded(): void{
        $this->mysqli = mysqli_connect("localhost", "bot",
            "password", "bot_encode");

        $this->all_strings = yaml_parse_file(__DIR__.'/./CryptBot_files/strings.yaml');
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

        $user = $this->vk_api->asyncRequest("users.get", ["user_ids"=>$event->object["message"]["from_id"]]);

        $peer_id = (int) $event->object["message"]["peer_id"];
        $from_id = (int) $event->object["message"]["from_id"];

        $key = "rHdHpd0Tk2BU1ic3";
        $customKey = false;
        $stmt = $this->mysqli->query("SELECT * FROM `peers` WHERE `peerId` = ".$peer_id);
        if($stmt->num_rows>0) {
            $customKey = true;
            $key = $stmt->fetch_assoc()['encodeKey']."MXB";
        }

        $this->MCrypt = new MCrypt($key);

        $payload = null;
        if(isset($event->object["message"]["payload"]))
            $payload = $event->object["message"]["payload"];

        $text = $event->object["message"]["text"];

        if($peer_id!=$from_id){
            return;
        }

        if($payload!=null) {
            $payload = json_decode($payload, true);
            if (json_last_error() == JSON_ERROR_NONE)
                if (isset($payload["command"]) && $payload["command"] == "start") {
                    $this->vk_api->sendMessage($peer_id, $this->strings["start"]);
                    return;
                }else if(isset($payload["command"]) && $payload["command"] == "--info"){
                    $this->vk_api->sendMessage($peer_id, $this->strings["start"], null, Keyboard::new()->addButton([
                        "type"=>"text",
                        "label"=>$this->strings["help"],
                        "payload"=>json_encode(["command"=>"start"]),
                    ], "secondary")->setOneTime(true));
                    return;
                }
        }

        $helpKeyboard = Keyboard::new()->setInline(true)->addButton([
            "type"=>"text",
            "label"=>$this->strings["help"],
            "payload"=>json_encode(["command"=>"start"]),
        ], "secondary");

        $words = explode(" ", $text);
        if(strlen($text)!=0&&$words[0][0]=="/") {
            if($words[0]=="/key"&&isset($words[1])) {
                if(mb_strlen($words[1])==16) {
                    if(!$customKey)
                        $this->mysqli->query("INSERT INTO `peers` (`peerId`, `encodeKey`) VALUES ('{$peer_id}', '".$this->mysqli->real_escape_string($words[1])."');");
                    else $this->mysqli->query("UPDATE `peers` SET `encodeKey` = '".$this->mysqli->real_escape_string($words[1])."' WHERE `peers`.`peerId` = '{$peer_id}';");
                    $this->vk_api->sendMessage($peer_id, $this->strings["ok"]);
                }else $this->vk_api->sendMessage($peer_id, "❗ Недоступная команда");
            }else if($words[0]=="/dkey"&&$customKey) {
                $this->mysqli->query("DELETE FROM `peers` WHERE `peerId` = {$peer_id};");
                $this->vk_api->sendMessage($peer_id, $this->strings["ok"]);
            }else $this->vk_api->sendMessage($peer_id, "❗ Недоступная команда");
        }else if($data = $this->decrypt($text)){
            $this->vk_api->sendMessage($peer_id, $this->strings["decrypted"]);
            $this->vk_api->sendMessage($peer_id, $data);
        }else if(preg_match('/MCrypt(.*);-2/', $text, $output)){
            try{
                $crypt = $output[1];
                $data_ = $this->MCrypt->decrypt($crypt);
                $this->vk_api->sendMessage($peer_id, $data_);
            }catch (Exception $e){
                if(DEBUG)
                    throw $e;

                $this->vk_api->sendMessage($peer_id, $this->strings["invalid"]);
            }
        }else if(preg_match('/MCrypt(.*)/', $text, $output)
            ||preg_match('/MCrypt(.*);/U', $text, $output)){
            try{
                $crypt = $output[1];
                $this->decodeCrypt($crypt, $user, $peer_id, function (array $decoded) use ($helpKeyboard, $peer_id){
                    if(!$decoded[0]){
                        $this->vk_api->sendMessage($peer_id, $this->strings["invalid"]);
                    }else{
                        $this->vk_api->sendMessage($peer_id, $this->strings["decrypted"]);
                        $this->vk_api->sendMessage($peer_id, $decoded[1], $decoded[2], $helpKeyboard);
                    }
                });
            }catch (Exception $e){
                if(DEBUG)
                    throw $e;

                $this->vk_api->sendMessage($peer_id, $this->strings["invalid"]);
            }
        }else if(strlen($text)!=0||isset($event->object['message']['attachments'][0])){
            $date = time();
            $params =  [
                "c"=>"vk",
                "ti"=>$date,
                "i"=>(int) $from_id,
                "t"=>$text,
                "v"=>$this->MCrypt->version
            ];

            if (sizeof($event->object['message']['attachments'])!=0){
                if(sizeof($event->object['message']['attachments'])>5){
                    $this->vk_api->sendMessage($peer_id, $this->strings["attachment_not_working"], null);
                    return;
                }

                $attachments = [];
                foreach($event->object['message']['attachments'] as $attachment){
                    $a = $this->encodeAttachment($peer_id, $attachment);
                    if(!$a){
                        return;
                    }

                    $a['t'] = $attachment["type"];
                    $attachments[] = $a;
                }


                $params["a"] = $attachments;
            }

            $encoded = "MCrypt".$this->MCrypt->encrypt(json_encode(
                    $params
                )).";";

            if(strlen($encoded)<=5000) {
                $this->vk_api->sendMessage($peer_id, $this->strings["encrypted"]);
                $this->vk_api->sendMessage($peer_id, $encoded, null, $helpKeyboard);
            }else $this->vk_api->sendMessage($peer_id, $this->strings["attachment_not_working"]);
        }else $this->vk_api->sendMessage($peer_id, $this->strings["attachment_not_working"]);

        if(random_int(0,2)===1) {
            $this->vkApi->sendMessage($peer_id, "Помоги нам -- расскажи о боте друзьям!\nhttps://vk.cc/c2F6gP");
        }
        $this->ads->sendAd($this, $peer_id);
    }

    public function decrypt(string $text): bool|string {
        if(preg_match("/VK C[0O] FF EE (.*) VK C[0O] FF EE/", $text, $output)){
            try {
                $data = str_replace([
                    "PP",
                    "AP ID 0G",
                    "AP ID OG",
                    "II",
                    " "
                ], "", $output[1]);
                $data = hex2bin($data);

                $key = "stupidUsersMustD";
                return openssl_decrypt($data,
                    'AES-128-ECB', $key);
            }catch (Exception){}
        }else if($response = $this->isBase64($text)) {
            return $response;
        }

        return false;
    }

    public function isBase64(string $data): string|false {
        $decoded_data = base64_decode($data, true);
        $encoded_data = base64_encode($decoded_data);
        if ($encoded_data != $data) return false;
        else if (!ctype_print($decoded_data)) return false;

        return $decoded_data;
    }

    public function loadLang(int $id, bool $f = false): void {
        foreach ($this->all_strings["langs"] as $lang){
            if($lang["lang_id"]===$id) {
                $this->strings = str_replace('\n', "\n", $lang["strings"]);
                return;
            }
        }

        if(!$f) {
            $this->loadLang(0, true);
        }
    }

    /**
     * @param string $crypt
     * @param array $user
     * @param int $peer_id
     * @param $onDecrypted
     * @return bool
     * @throws VKApiException|JsonException
     */
    public function decodeCrypt(string $crypt, array $user, int $peer_id, $onDecrypted):bool{
        if(!is_callable($onDecrypted))
            return false;

        $data_ = $this->MCrypt->decrypt($crypt);
        if(is_null($data_)){
            $onDecrypted([false, 0]);
            return false;
        }

        $data_ = json_decode($data_, true, 512, JSON_THROW_ON_ERROR);
        if($data_['v']!==$this->MCrypt->version){
            $onDecrypted([false, 1]);
            return false;
        }

        $text = $data_['t'];
        /*$text = str_replace("{имя}", $user['first_name'], $text);
        $text = str_replace("{фамилия}", $user['last_name'], $text);
        $text = str_replace("{дата}", date("d-m-Y H:i"), $text);
        $text = str_replace("{json_user}", json_encode($user), $text);
        $text = str_replace("{version}", $this->MCrypt->version, $text);*/

        if(isset($data_['a'])){
            $attachments = [];
            foreach($data_['a'] as $attachment){
                $attachments[] = $this->decodeAttachment($peer_id, $attachment);
            }

            $onDecrypted([true, $text, implode(",", $attachments)]);
            return true;
        }

        $onDecrypted([true, $text, null]);
        return true;
    }

    /**
     * @param int $peer_id
     * @param array $attachment
     * @return array|bool
     * @throws VKApiException|JsonException
     */
    private function encodeAttachment(int $peer_id, array $attachment): bool|array {
        switch ($attachment['type']){
            case "photo":
                return array('pu'=>$attachment['photo']['sizes'][2]['url']);

            case "sticker":
                return array('su'=>$attachment['sticker']['images'][2]['url']);

            case "graffiti":
                return array('su'=>$attachment['graffiti']['url']);

            case "audio_message":
                if($attachment['audio_message']['duration']<=60){
                    return array('ogg' => $attachment['audio_message']['link_ogg']);
                }

                $this->vk_api->sendMessage($peer_id, $this->strings["too_long_audio_message"], null);
                return false;
        }

        $this->vk_api->sendMessage($peer_id, $this->strings["attachment_not_working"], null);
        return false;
    }

    /**
     * @param int $peer_id
     * @param array $array
     * @return bool|string
     * @throws VKApiException
     * @throws JsonException
     */
    private function decodeAttachment(int $peer_id, array $array): string|bool {
        switch ($array['t']){
            case "photo":
                $file = $this->file->createFileFromURL("{$peer_id}_photo_c", "jpg", $array['pu']);
                $a = $this->vk_api->uploadPhoto($peer_id, $file);
                return"photo".$a[0]['owner_id']."_".$a[0]['id'];

            case "audio_message":
                $file = $this->file->createFileFromURL("{$peer_id}_audio_message_c", "ogg", $array['ogg']);
                $response = $this->vk_api->asyncRequest("docs.getMessagesUploadServer",
                    ["type" => "audio_message", "peer_id" => $peer_id]);

                $data = $this->vk_api->upload($response["upload_url"], $file);
                $response = $this->vk_api->asyncRequest("docs.save", ['file' => $data['file']]);
                return "audio_message".$response["audio_message"]['owner_id']."_".$response["audio_message"]['id'];

            case "graffiti":
            case "sticker":
                $file = $this->file->createFileFromURL("{$peer_id}_gs_c", "png", $array['su']);
                $a = $this->vk_api->uploadPhoto($peer_id, $file);
                return "photo".$a[0]['owner_id']."_".$a[0]['id'];
        }

        return false;
    }
}