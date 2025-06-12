<?php

abstract class Module{

    public mysqli $mysqli;
    public BotModel $bot;
    public int $botId;
    public bool $runFromThread = false;

    final public function __construct(){
        setupModule($this);
    }

    public static function onRegisterPlatforms(): void {}
    public function onNewEvent(DataEvent $event): void {}
    public function onError(string $message): void {}
    public function onApiCall(string $method, array $data): mixed { return false; }
}

