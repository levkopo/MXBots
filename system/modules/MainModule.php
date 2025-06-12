<?php
/**
 * Модуль оброботки и отправки событий боту
 *
 * Class MainModule
 */

class MainModule extends Module {

    public string $name = "system";

    public function onNewEvent(DataEvent $event): void{
        if($event->type===EVENT_STOP_SCRIPT) {
            return;
        }

        if($event->type===EVENT_VK) {
            if($event->event->type==="confirmation") {
                echo $this->bot->confirmationKey;
                return;
            }

            if($this->bot->isIncluded) {
                echo "ok";
                return;
            }

            require_once __DIR__."/./../../bots/".$this->bot->botFilename.".php";

            /**
             * @var $bot Bot
             */
            $bot = new $this->bot->botFilename();
            $eventFunction = $event->event->type;
            try {
                $reflect = new ReflectionClass(get_class($bot));
                foreach($reflect->getMethods() as $function) {
                    foreach($function->getAttributes() as $attribute) {
                        $attribute = $attribute->newInstance();
                        if(($attribute instanceof EventHandler)&&$attribute->accept($event)) {
                            $attribute->call($bot, $function->getName(), $event);
                            goto end;
                        }
                    }
                }
            } catch (ReflectionException) {}

            if(method_exists($this->bot->botFilename, $eventFunction)) {
                $bot->{$eventFunction}($event->event);
            }else if(preg_match_all('/_(.)/', $event->type, $outputArray)) {
                $method = $event->type;
                for($i = 0; $i < ($outputArray[0]); $i++) {
                    $method = str_replace($outputArray[0][$i], strtoupper($outputArray[1][$i]), $method);
                }

                if(method_exists($this->bot['name'], $method)) {
                    $bot->{$method}($event->event);
                }
            }

            end: echo "ok";
        }
    }
}