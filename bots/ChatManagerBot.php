<?php

use Dejurin\GoogleTranslateForFree;
use JetBrains\PhpStorm\Pure;

require_once __DIR__."/./ChatManagerBot_files/GoogleTranslateForFree.php";

class ChatManagerBot extends NBot {
    public VKApiModule $vkApi;
    public FileModule $file;

    public function onModulesLoaded(): void {
        $this->vkApi->setup(null, "5.130");
    }

    /**
     * @param Event $event
     * @throws VKApiException|JsonException
     */
    #[VKMessageEvent]
    public function userMessage(Event $event){
        $peerId = $event->object["message"]["peer_id"];

        $this->vkApi->sendMessage($peerId, "*Инструкция*");
    }

    /**
     * @param array $message
     * @throws VKApiException|JsonException
     */
    #[Command("\!hello")]
    public function hello(array $message){
        $this->vkApi->sendMessage($message["peer_id"], "Сообщение для юзера");
    }

    /**
     * @param array $message
     * @param array $args
     * @throws VKApiException|JsonException
     */
    #[Command("\!отчистить", 1)]
    public function clear(array $message, array $args){
        if($this->checkPermissions($message['peer_id'])) {
            $peerId = $message["peer_id"];
            if ($args[0] > 100 || $args[0] <= 0) {
                $this->notify($peerId, "❗ Слишком большое количество сообщений для удаления!");
                return;
            }

            $this->vkApi->sendMessage($peerId, "✔ $peerId");
            $messages = $this->vkApi->asyncRequest("messages.getHistory", [
                "peer_id" => $peerId,
                "count" => $args[0]
            ]);

            $this->vkApi->sendMessage($peerId, "✔ ".json_encode($messages));
            $messages = $messages['items'];

            $messageIds = [];
            foreach ($messages as $message) {
                $messageIds[] = $message['conversation_message_id'];
            }

            $successCount = 0;
            $failCount = 0;
            if(count($messageIds)!=0) {
                $response = $this->vkApi->asyncRequest("messages.delete", [
                    'conversation_message_ids' => implode($messageIds)
                ]);

                foreach ($response as $item)
                    if ($item == 1) $successCount++;
                    else $failCount++;
            }

            $this->vkApi->sendMessage($peerId, "✔ Удалено $successCount из $args[0]");
        }
    }

    /**
     * @param array $message
     * @param array $args
     * @throws VKApiException|JsonException
     */
    #[Command("[оО]тмудохать", -1)]
    public function beat(array $message, array $args){
        if(sizeof($args)==-1)
            return;

        $attachments = [
            "photo-197863177_457239018",
            "photo-197863177_457239021",
        ];

        $user = $this->vkApi->asyncRequest("users.get",
            ["user_ids" => $message["from_id"]])[0];
        $this->vkApi->sendMessage($message["peer_id"], "{$user['first_name']} отмудохал "
            .implode(" ", $args),
            $attachments[rand(0, sizeof($attachments)-1)]);
    }

    /**
     * @param array $message
     * @throws VKApiException|JsonException
     */
    #[Command("!hideKB")]
    public function hideKeyboard(array $message){
        if($this->checkPermissions($message['peer_id']))
            $this->vkApi->sendMessage($message["peer_id"], "✔ Клавиатура скрыта", null,
                Keyboard::new()->setOneTime(true));
    }

    /**
     * @param array $message
     * @param array $args
     * @throws VKApiException|JsonException
     */
    #[Command("!ban", 1)]
    public function ban(array $message, array $args){
        if($this->checkPermissions($message['peer_id'])&&
            preg_match('/\[id(\d+)\|.+?]/', $args[0], $output))
            $this->vkApi->asyncRequest("messages.removeChatUser", [
                "chat_id"=>$message['peer_id']-2000000000,
                "user_id"=>$output[1]
            ]);
    }

    private function checkPermissions(int $peerId): bool{
        $members = $this->vkApi->asyncRequest("messages.getConversationMembers", [
            "peer_id"=> $peerId
        ]);

        foreach($members['items'] as $member)
            if($member["member_id"]==$peerId&&
                (!isset($member["is_admin"])||!$member["is_admin"])){
                $this->vkApi->sendMessage($peerId, "❗ Недостаточно прав в беседе");
                return false;
            }

        return true;
    }

    /**
     * @param array $message
     * @param array $args
     * @throws VKApiException
     */
    #[Command("!tr", -1)]
    public function translate(array $message, array $args){
        try{
            $translateGoogle = new GoogleTranslateForFree();
            $result = $translateGoogle->translate("auto", "ru", implode(" ", $args));
            if($result==null)
                throw new Exception();

            $this->notify($message['peer_id'],
                "Перевод: ".$result);
        }catch (Exception $e){
            $this->notify($message['peer_id'],
                "❗ Не удалось перевести текст!");
        }
    }

    /**
     * @param array $message
     * @param array $args
     * @throws VKApiException|JsonException
     */
    #[Command("!оч", 1)]
    public function randomNumberGame(array $message, array $args) {
        if(($num = rand(0, 10))&&$num==$args[0]){
            $attachments = [
                "photo-197863177_457239019",
            ];

            $this->vkApi->sendMessage($message["peer_id"],
                "Молодец! Ты отгадал число!\n Держи подарочек:",
                $attachments[rand(0, sizeof($attachments)-1)]);
        }else{
            $this->vkApi->sendMessage($message["peer_id"],
                "Увы, но бот загадал число $num");
        }
    }

    /**
     * @param array $message
     * @throws VKApiException|JsonException
     */
    #[Command("!оч")]
    public function randomNumberGameInfo(array $message) {
        $this->vkApi->sendMessage($message["peer_id"],
            "Игра \"Отгадай число\"\nТебе нужно отгадать число, которое загадал бот.\n
             Это число от 0 до 10.\n Напиши !оч и число");
    }

    /**
     * @param int $peerId
     * @param string $message
     * @throws VKApiException|JsonException
     */
    public function notify(int $peerId, string $message){
        $messageData = $this->vkApi->sendMessage($peerId, $message)[0];
        if(DEBUG) {
            var_dump($messageData);
            sleep(5);

            var_dump($this->vkApi->asyncRequest("messages.delete",
                ["conversation_message_ids" => $messageData['conversation_message_id'],
                    "delete_for_all" => 1]));
        }
    }
}

#[Attribute]
class Command extends VKMessageEvent{
    public string $command;
    public int $argsCount;

    #[Pure] public function __construct(string $command, int $argsCount = 0) {
        parent::__construct(true);
        $this->command = $command;
        $this->argsCount = $argsCount;
    }

    public function accept(DataEvent $event): bool {
        $textData = explode(" ", $event->event->object["message"]["text"]);
        return parent::accept($event)&&
            sizeof($textData)!=0&&
            (sizeof($textData)==$this->argsCount+1||$this->argsCount==-1)&&
            preg_match("/{$this->command}/", $textData[0]);
    }

    public function call(Bot $bot, string $eventFunction, DataEvent $event) {
        $textData = explode(" ", $event->event->object["message"]["text"]);
        array_shift($textData);

        if($this->argsCount!=0)
            $bot->{$eventFunction}($event->event->object["message"], $textData);
        else $bot->{$eventFunction}($event->event->object["message"]);
    }
}