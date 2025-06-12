<?php

use JetBrains\PhpStorm\Pure;

#[Attribute]
class EventHandler {
    #[Pure] public function accept(DataEvent $event): bool {
        return true;
    }

    public function call(Bot $bot, string $eventFunction, DataEvent $event) {
        $bot->{$eventFunction}($event->event);
    }
}