<?php
namespace powerorm\checks;

use powerorm\cli\ColorCLi;

abstract class Message{
    public $name;
    public $message;
    public $model_name;

    protected $linebreak=PHP_EOL;

    public function __construct($field_obj, $message){
        $this->name = $field_obj->name;
        $this->message = $message;
        $this->model_name = $field_obj->container_model;
    }

    public function message(){
        return sprintf("\t %3\$s(%1\$s) : %2\$s".PHP_EOL, $this->name, $this->message, $this->model_name);
    }
}

class Error extends Message{

    public function message(){
        ColorCLi::error(parent::message());
    }
}

class Warning extends Message{

    public function message(){
        ColorCLi::warning(parent::message());
    }
}

class Info extends Message{

    public function message(){
        ColorCLi::info(parent::message());
    }
}

class Success extends Message{
    public function message(){
        ColorCLi::success(parent::message());
    }
}