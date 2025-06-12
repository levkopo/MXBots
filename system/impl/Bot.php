<?php


abstract class Bot {
    public array $modules = [];

    /**
     * @deprecated use {@link NBot} class for modules
     */
    public array $required_modules = [];

    /**
     * Bot constructor.
     * @throws ModuleNotFoundException
     * @throws Exception
     */
    final public function __construct(){
        setupBot($this);
        $this->onLoadModules();
        $this->onModulesLoaded();
    }

    /**
     * @param $name
     * @return Module
     * @throws ModuleNotFoundException
     */
    final public function findModule($name): Module{
        if(isset($this->modules[$name])){
            return $this->modules[$name];
        }

        throw new ModuleNotFoundException($name);
    }

    /**
     * @throws ModuleNotFoundException
     */
    abstract public function onLoadModules(): void;

    /**
     * @throws Exception
     */
    abstract public function onModulesLoaded(): void;
}