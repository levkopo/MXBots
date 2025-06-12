<?php

use JetBrains\PhpStorm\Pure;

require_once __DIR__."/./file/File.php";
class FileModule extends Module{
    public string $name = "file";
    private array $bot_tmp_files = [];

    public function onNewEvent(DataEvent $event): void {
        if($event->type===EVENT_STOP_SCRIPT){
            foreach($this->bot_tmp_files as $file){
                unlink($file->getPath());
            }
        }
    }

    #[Pure] public function generateName(): string {
        return $this->botId.microtime().count($this->bot_tmp_files)
            .md5($this->botId.microtime().count($this->bot_tmp_files));
    }

    public function createFile(string $name, string $extension, string $data): File{
        $file = new File($name, $extension);
        $file->saveData($data);
        $this->bot_tmp_files[] = $file;
        return $file;
    }

    public function createFileFromURL(string $name, string $extension, string $url): File{
        $file = new File($name, $extension);
        $file->saveData(file_get_contents($url));
        $this->bot_tmp_files[] = $file;
        return $file;
    }
}