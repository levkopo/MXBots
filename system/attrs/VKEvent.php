<?php

use JetBrains\PhpStorm\Pure;

#[Attribute]
class VKEvent extends EventHandler {
    public string $eventType;

    #[Pure] public function __construct(string $eventType) {
        $this->eventType = $eventType;
    }

    #[Pure] public function accept(DataEvent $event): bool {
        return parent::accept($event)&&
            $event->event->type==$this->eventType;
    }
}