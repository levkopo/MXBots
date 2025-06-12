<?php


class AdsModule extends Module{

    /**
     * @throws Exception
     */
    public function sendAd(Bot $bot, int $peer_id): void {
        $vk_api = $bot->findModule('vk_api');
        if(random_int(0,2)===1){
            $request = $this->mysqli->query("SELECT `text` FROM `ads` WHERE `bot_id` != ".$this->botId." 
                            ORDER BY rand() LIMIT 1");
            if($request->num_rows!==0){
                $data = $request->fetch_assoc();
                $vk_api->sendMessage($peer_id, $data["text"]);
            }
        }
    }
}