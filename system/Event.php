<?php

class Event{
    public string $type;
    public ?string $eventId;
    public array $object = [];

    /**
     * Event constructor.
     * @param $type string
     * @param array|null $object array
     * @param string|null $eventId
     */
    public function __construct(string $type, ?array $object = null, ?string $eventId = null){
        $this->type = $type;
        $this->eventId = $eventId;
        if($object!=null)
            $this->object = $object;
    }
}