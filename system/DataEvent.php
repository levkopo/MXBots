<?php

const EVENT_VK = -1;
const EVENT_STOP_SCRIPT = 1;

class DataEvent {
    public Event $event;

    public function __construct(
        public int $type,
        public array $data,
        public Platform|false $platform = false
    ){}
}