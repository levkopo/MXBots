<?php


class TestBot extends NBot {

    public VKApiModule $vkApi;

    //PHP 7.1
    public function message_new(Event $event){
        $peerId = $event->object["message"]["peer_id"];
        $fromId = $event->object["message"]["from_id"];
        if($peerId==$fromId){
            $this->vkApi->sendMessage($peerId, "User message");
        }else{
            $this->vkApi->sendMessage($peerId, "Chat message");
        }
    }

    //PHP 8.0
    #[VKMessageEvent]
    public function userMessage(Event $event){
        //для пользователя
    }

    #[VKMessageEvent(true)]
    public function chatMessage(Event $event){
        //для чата
    }
}