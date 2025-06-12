<?php


class File{
    public string $name;
    public string $extension = "";
    public int $size = 0;

    public function __construct(string $name, string $extension){
        $this->name = $name;
        $this->extension = $extension;
        $this->saveData("");
    }

    public function getPath():string {
        return "/../../../tmp/{$this->name}.{$this->extension}";
    }

    public function saveData(string $data):bool{
        return file_put_contents($this->getPath(), $data, LOCK_EX);
    }

    public function readData():string{
        return file_get_contents($this->getPath());
    }
}