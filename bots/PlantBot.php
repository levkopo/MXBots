<?php


class PlantBot extends NBot {

    public VKApiModule $vkApi;
    protected string $lkToken = "<Токен от аккаунта ВК>";

    public function wall_post_new(Event $event) {
        $post = $event->object;
        if($post['marked_as_ads']==1)
            return;

        $attachments = [];
        if(isset($post['attachments'])) {
            foreach($post['attachments'] as $attachment) {
                $type = $attachment['type'];
                $object = $attachment[$type];
                $attachments[] = $type.$object['owner_id']."_".$object['id'];
            }
        }

        $response = $this->vkApi->mysqli->query("SELECT * FROM `bots`");
        $vkApi = new VKApiModule();
        $vkApi->mysqli = $this->vkApi->mysqli;

        $bots = [];
        while($bot = $response->fetch_array()){
            if($bot['group_id']!=201703715&&$bot['group_id']!=202911302) {
                $bots[] = $bot;
            }
        }

        $bot = $bots[rand(0, sizeof($bots)-1)];
        $bot["access_token"] = $this->lkToken;
        $vkApi->bot = $bot;
        $vkApi->botId = $bot['id'];

        try {
            $vkApi->asyncRequest("wall.post", [
                "owner_id" => $bot['group_id'] * -1,
                "message" => $post['text'],
                "attachments" => implode(",", $attachments),
                "copyright" => "https://vk.com/wall".$post['owner_id'].'_'.$post['id']]);
        }catch (VKApiException $exception){
            throw $exception;
        }
    }
}