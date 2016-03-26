<?php
namespace powerorm\model\field;
/**
 * Act a buffer for Relation Fields, to help avoid issues with
 *
 * @package powerorm\model
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RelationObject{

    public $model_name;
    public $model;

    public function __construct($model_name){
        $this->model_name = $model_name;
    }

    public function _model_object(){
        $_ci = & get_instance();
        if(!isset($this->model)):
            $_ci->load->model($this->model_name);
            $this->model =  $_ci->{$this->model_name};
        endif;
    }

    public function __get($key){
        $this->_model_object();
        return $this->model->{$key};

    }
    public function __call($method, $args){
        $this->_model_object();

        if(empty($args)):
            // invoke from the queryset
            return call_user_func(array($this->model, $method));
        else:
            // invoke from the queryset
            if(is_array($args)):
                return call_user_func_array(array($this->model, $method), $args);
            else:
                return call_user_func(array($this->model, $method), $args);
            endif;
        endif;

    }
    public function __set($key, $value){
        $this->_model_object();
        $this->model->{$key} = $value;
    }

    public function __toString(){
        return sprintf("Related Model: %s", $this->model_name);
    }
}