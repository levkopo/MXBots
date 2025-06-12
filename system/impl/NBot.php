<?php


abstract class NBot extends Bot {

    public VKApiModule $vkApi;

    final public function onLoadModules(): void {
        try {
            $reflect = new ReflectionClass(get_class($this));
            foreach($reflect->getProperties() as $property){
                $type = $property->getType();
                if($type instanceof ReflectionNamedType&&
                    is_subclass_of($type->getName(), "Module")){
                    foreach($this->modules as $module) {
                        if($type->getName()===get_class($module)) {
                            $this->{$property->name} = $module;
                        }
                    }

                    if($this->{$property->name}===null) {
                        throw new ModuleNotFoundException($type->getName());
                    }
                }
            }

        } catch (ReflectionException) {}
    }

    public function onModulesLoaded(): void {}
}