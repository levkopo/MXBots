<?php


abstract class Platform {
    public mysqli $mysqli;

    public function __construct(
        public string $platformId,
    ){}

    public function onDefinePlatform(DataEvent $event): bool {
        return false;
    }

    public function onSendAnswer(DataEvent $event): bool {
        return false;
    }

    public function onStopBotRunning(DataEvent $event): bool {
        return false;
    }
}