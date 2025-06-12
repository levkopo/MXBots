<?php

use JetBrains\PhpStorm\Pure;

#[Attribute]
class VKMessageEvent extends VKEvent {
    public bool $onlyChat;

    #[Pure] public function __construct(bool $onlyChat = false) {
        parent::__construct("message_new");
        $this->onlyChat = $onlyChat;
    }

    #[Pure] public function accept(DataEvent $event): bool {
        $message = $event->event->object['message'];
        $isChat = $message['from_id']!=$message['peer_id'];

        return parent::accept($event)&&
            $this->onlyChat==$isChat;
    }
}