<?php


class VinciBot extends NBot {
    public VKApiModule $vk_api;
    public FileModule $file;

    private array $allStrings;
    private array $strings;

    /**
     * @param Event $event
     * @throws VKApiException
     */
    public function message_new(Event $event){
        $this->allStrings = yaml_parse_file(__DIR__.'/./VinciBot_files/strings.yaml');

        if(isset($event->object["client_info"])){
            $client_info = $event->object["client_info"];

            $lang_id = (int) $client_info["lang_id"];
            $this->loadLang($lang_id);
        }else $this->loadLang(0);

        $peer_id = $event->object["message"]["peer_id"];
        $from_id = $event->object["message"]["from_id"];

        if($peer_id==$from_id){
            if(sizeof($event->object["message"]["attachments"])!=0){
                if($event->object["message"]["attachments"][0]["type"]=="photo"){
                    $list = json_decode(file_get_contents("http://vinci.camera/list"), true);
                    if($event->object["message"]["text"]==""||
                        (int) $event->object["message"]["text"]==0||
                        !isset($list[(int) $event->object["message"]["text"]==0]))
                        $filter = $list[rand(0, sizeof($list)-1)];
                    else $filter = $list[(int) $event->object["message"]["text"]+1];

                    $filterPreviewFile = $this->file->createFileFromURL("{$peer_id}_filter_preview_{$filter['id']}",
                        "jpg", $filter['file']);
                    $filterPreview = $this->vk_api->uploadPhoto($peer_id, $filterPreviewFile);

                    $this->vk_api->sendMessage($peer_id,
                        sprintf($this->strings['startEdit'], $filter['name']), "photo".$filterPreview[0]['owner_id']."_"
                        .$filterPreview[0]['id']);

                    $attachments = [];
                    foreach($event->object["message"]["attachments"] as $attachment){
                        $sizes = $attachment['photo']['sizes'];
                        $photo = $sizes[sizeof($sizes)-1]['url'];
                        $file = $this->file->createFileFromURL($this->file->generateName(),
                            "jpg", $photo);

                        $response = $this->upload("http://vinci.camera/preload", $file);
                        if(isset($response['preload'])){
                            $preload = $response['preload'];
                            $photoFilter = file_get_contents("http://vinci.camera/process/$preload/{$filter['id']}");
                            $photoFilterFile = $this->file->createFile($this->file->generateName(), "jpg", $photoFilter);
                            $photoFilterAttachment = $this->vk_api->uploadPhoto($peer_id, $photoFilterFile);
                            $attachments[] = "photo".$photoFilterAttachment[0]['owner_id']."_"
                                .$photoFilterAttachment[0]['id'];
                        }else{
                            $attachments = false;
                            $this->vk_api->sendMessage($peer_id, $this->strings['error']);
                            break;
                        }
                    }

                    if(is_array($attachments))
                        $this->vk_api->sendMessage($peer_id, $this->strings['done'], implode(",", $attachments));
                }else $this->vk_api->sendMessage($peer_id, $this->strings['start']);
            }else $this->vk_api->sendMessage($peer_id, $this->strings['start']);
        }

    }

    public function loadLang(int $id, bool $f = false){
        foreach ($this->allStrings["langs"] as $lang){
            if($lang["lang_id"]==$id) {
                $this->strings = str_replace('\n', "\n", $lang["strings"]);
                return;
            }
        }

        if(!$f)
            $this->loadLang(0, true);
    }

    public function upload($url, File $file): array {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('photo' => new CURLfile(realpath($file->getPath()))));
        $json = curl_exec($curl);
        curl_close($curl);
        return json_decode($json, true);
    }
}