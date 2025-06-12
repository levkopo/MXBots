<?php

use thiagoalessio\TesseractOCR\TesseractOcrException;


class MathBot extends NBot {
    public VKApiModule $vkApi;
    public FileModule $file;
    public AdsModule $ads;

    private array $all_strings;
    private array $strings = [];

    /**
     * @param Event $event
     * @throws VKApiException|TesseractOcrException
     */
    public function message_new(Event $event){
        $this->all_strings = yaml_parse_file(__DIR__.'/./MathBot_files/strings.yaml');

        if(isset($event->object["client_info"])){
            $client_info = $event->object["client_info"];

            $lang_id = (int) $client_info["lang_id"];
            $this->loadLang($lang_id);
        }else
            $this->loadLang(0);

        $peerId = $event->object["message"]["peer_id"];
        $from_id = $event->object["message"]["from_id"];
        $message = $event->object["message"]["text"];

        $payload = null;
        if(isset($event->object["message"]["payload"]))
            $payload = $event->object["message"]["payload"];

        if($peerId!==$from_id){
            return;
        }

        if($payload!==null) {
            $payload = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE&&isset($payload["command"])) {
                if($payload["command"]==="start") {
                    $this->vkApi->sendMessage($peerId, $this->strings["start"]);
                    return;
                }

                if($payload["command"]==="--info") {
                    $this->vkApi->sendMessage($peerId, $this->strings["start"],
                        null, Keyboard::new()->addButton(["type" => "text", "label" => $this->strings["help"], "payload" => json_encode(["command" => "start"]),], "secondary")->setOneTime(true));
                    return;
                }
            }
        }

        $keyboard = Keyboard::new()->setInline(true)->addButton([
            "type"=>"text",
            "label"=>$this->strings["help"],
            "payload"=>json_encode(["command"=>"start"]),
        ], "secondary");

        if(isset($event->object['message']['attachments'][0]["photo"])){
            $attachment = $event->object['message']['attachments'][0];
            $file = $this->file->createFileFromURL("{$peerId}_gs_c", "png", $attachment['photo']['sizes'][2]['url']);
            $message = (new thiagoalessio\TesseractOCR\TesseractOCR($file->getPath()))
                ->run();
            if($message===''){
                $this->vkApi->sendMessage($peerId, trim(
                    "❗ Бот не увидел пример на картинке. Попробуйте написать разборчивее"),
                    keyboard: $keyboard);
                return;
            }
        }

        try {
            $calc = new ChrisKonnertz\StringCalc\StringCalc();
            $result = $calc->calculate($message);

            $this->vkApi->sendMessage($peerId, sprintf($this->strings["result"], $result),
                null, $keyboard);
            $this->ads->sendAd($this, $peerId);
        }catch (ChrisKonnertz\StringCalc\Exceptions\StringCalcException|Exception) {
            $this->vkApi->sendMessage($peerId, $this->strings["error"], null, $keyboard);
        }
    }

    public function loadLang(int $id, bool $f = false){
        foreach ($this->all_strings["langs"] as $lang){
            if($lang["lang_id"]==$id) {
                $this->strings = str_replace('\n', "\n", $lang["strings"]);
                return;
            }
        }

        if(!$f)
            $this->loadLang(0, true);
    }

}