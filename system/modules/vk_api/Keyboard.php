<?php

use JetBrains\PhpStorm\Pure;

class Keyboard{

    public bool $one_time = true;
    public bool $inline = false;
    public array $buttons = [];

    #[Pure] public static function new(): Keyboard{
        return new Keyboard();
    }

    public function newLine(): Keyboard{
        if(count($this->buttons)!=3)
            $this->buttons[] = [];

        return $this;
    }

    public function addButton(array $action, string $color): Keyboard{
        /*if(!isset($this->buttons[count($this->buttons)-1]))
            $this->buttons[count($this->buttons)-1] = [];*/

        $this->buttons[count($this->buttons)-1][] = [
            "action"=>$action,
            "color"=>$color,
        ];

        return $this;
    }

    public function setInline(bool $inline): Keyboard{
        $this->inline = $inline;
        if($this->one_time)
            $this->one_time = false;
        return $this;
    }

    public function setOneTime(bool $one_time): Keyboard{
        if(!$one_time)
            $this->one_time = $one_time;
        return $this;
    }
}