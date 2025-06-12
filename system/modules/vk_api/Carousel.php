<?php


class Carousel extends TemplateElement {
    
    public string $title;
    public string $description;
    public string $photo_id;
    public array $buttons = [];
    public array $action = [];

    /**
     * @return Carousel
     */
    public static function new(): Carousel{
        return new Carousel();
    }

    /**
     * @param string $title
     * @return Carousel
     */
    public function setTitle(string $title): Carousel{
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $photo_id
     * @return Carousel
     */
    public function setPhotoId(string $photo_id): Carousel{
        $this->photo_id = $photo_id;
        return $this;
    }

    /**
     * @param string $description
     * @return Carousel
     */
    public function setDescription(string $description): Carousel{
        $this->description = $description;
        return $this;
    }

    public function addButton(array $action, string $color): Carousel{
        $this->buttons[] = [
            "action"=>$action,
            "color"=>$color,
        ];

        return $this;
    }

    /**
     * @param array $action
     * @return Carousel
     */
    public function setAction(array $action): Carousel{
        $this->action = $action;
        return $this;
    }
}