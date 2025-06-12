<?php
/**
 * Модуль VK api by mixowl
 *
 * Class VKApiModule
 */


use JetBrains\PhpStorm\Deprecated;

require_once __DIR__."/./vk_api/Keyboard.php";
require_once __DIR__."/./vk_api/VKApiException.php";
require_once __DIR__ . "/./vk_api/TemplateElement.php";
require_once __DIR__ . "/./vk_api/Carousel.php";

class VKApiModule extends Module {

    public string $name = "vk_api";

    private string $vk_url = "https://api.vk.com/method/";
    private string $vk_version = "5.103";

    /**
     * @param int $peer_id
     * @param string|null $message
     * @param string|null $attachment
     * @param Keyboard|null $keyboard
     * @param TemplateElement|null $template
     * @return mixed
     * @throws VKApiException
     * @throws JsonException
     */
    public function sendMessage(int $peer_id, string $message = null, string $attachment = null, Keyboard $keyboard = null,
                                TemplateElement $template = null): mixed {
        $params = ["peer_ids" => $peer_id, "random_id"=>0, "dont_parse_links"=>true];
        if($message!==null) {
            $params["message"] = $message;
        }

        if($attachment!==null) {
            $params["attachment"] = $attachment;
        }

        if($keyboard!==null) {
            $params["keyboard"] = json_encode($keyboard, JSON_THROW_ON_ERROR);
        }

        if($template!==null) {
            $params["template"] = json_encode($template, JSON_THROW_ON_ERROR);
        }

        return $this->asyncRequest("messages.send", $params);
    }

    /**
     * @param string $eventId
     * @param int $peerId
     * @param int $userId
     * @param $eventData
     * @return mixed
     * @throws VKApiException
     */
    public function sendMessageEventAnswer(string $eventId, int $peerId, int $userId, $eventData): mixed {
        return $this->asyncRequest("messages.sendMessageEventAnswer", [
            "event_id"=>$eventId,
            "user_id"=>$userId,
            "peer_id"=>$peerId,
            "event_data"=>json_encode($eventData),
        ]);
    }

    /**
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws VKApiException
     */
    public function asyncRequest(string $method, array $params = []): mixed {
        if(!isset($params["access_token"])) {
            $params["access_token"] = $this->bot->accessToken;
        }

        $params["v"] = $this->vk_version;

        $data = file_get_contents($this->vk_url.$method."?".http_build_query($params));
        if(!$data){
            throw new VKApiException("Network error", -1);
        }

        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);
        if(isset($data["error"])){
            throw new VKApiException($data["error"]["error_msg"], $data["error"]["error_code"]);
        }else if(isset($data["response"])){
            return $data["response"];
        }

        throw new VKApiException($data);
    }

    public function setup(?string $vk_url, ?string $vk_version): void{
        if($vk_url!=null)
            $this->vk_url = $vk_url;

        if($vk_version!=null)
            $this->vk_version = $vk_version;
    }

    /**
     * @param int $peer_id
     * @param File $file
     * @return array
     * @throws VKApiException
     */
    public function uploadPhoto(int $peer_id, File $file): array{
        $response = $this->asyncRequest('photos.getMessagesUploadServer', array(
            'peer_id' => $peer_id,
        ));

        $upload_response = $this->upload($response['upload_url'], $file);
        return $this->asyncRequest('photos.saveMessagesPhoto', array(
            'photo' => $upload_response['photo'],
            'server' => $upload_response['server'],
            'hash' => $upload_response['hash'],
        ));
    }

    /**
     * @param $url
     * @param File $file
     * @return array
     * @throws JsonException
     */
    public function upload($url, File $file): array {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile(realpath($file->getPath()))));
        $json = curl_exec($curl);
        curl_close($curl);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}