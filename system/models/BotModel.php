<?php


class BotModel {

    public int $id;
    public int $groupId;
    public bool $isIncluded;
    public string $accessToken;
    public string $secret;
    public string $confirmationKey;
    public string $botFilename;

    public function __construct(array $data) {
        $this->id = $data['id'];
        $this->isIncluded = $data['is_included']===1;
        $this->groupId = $data['group_id'];
        $this->accessToken = $data['access_token'];
        $this->secret = $data['secret_key'];
        $this->confirmationKey = $data['confirmation_key'];
        $this->botFilename = $data['name'];
    }
}