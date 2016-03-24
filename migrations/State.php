<?php
namespace powerorm\migrations;

// go through all the models and get there model state
// based on current project state find if there is any operation needed
// by comparing the it with the project state of application based on migration
use PModel;
use powerorm\checks\Checks;

/**
 * Class ProjectState
 * @package powerorm\migrations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ProjectState{
    public $_model_paths;

    public function __construct(){
        $this->_model_paths = APPPATH.'models/';
    }

    // name and state pair
    public $models=[];

    public static function from_models(){
        $state = new ProjectState();
        $state->_from_models();
        return $state;
    }

    public static function from_migrations(){
        $state = new ProjectState();
        $state->_from_migrations();


        return $state;
    }

    public static function app_model_objects(){
        $state = new ProjectState();

        return $state->_app_models();
    }

    public function _from_migrations(){
        $this->_model_paths = '_fake_';
        $l = new MigrationLoader();
        foreach ($l->to_models() as $model_name=>$model_obj) :
            $this->models[$model_name] = $model_obj->meta;
        endforeach;
    }

    /**
     * Loads up all the models in the current project.
     */
    public function _from_models(){

        foreach ($this->_app_models() as $model_obj) :

            if($model_obj instanceof PModel):

                $this->models[strtolower($model_obj->meta->model_name)] = $model_obj->meta;
            endif;
        endforeach;
    }

    public function _app_models(){
        $models = [];
        foreach ($this->get_model_classes() as $model_name) :
            $model_obj=  $this->_load_model($model_name);
            if($model_obj instanceof PModel):
                $models[strtolower($model_name)] = $model_obj;
            endif;
        endforeach;
        return $models;
    }

    public function _load_model($model_name){
        $_ci =& get_instance();
        if(!isset($_ci->{$model_name})):
            $_ci->load->model($model_name);
        endif;

        return $_ci->{$model_name};
    }

    /**
     * Returns a list of all model files
     * @return array
     */
    public function get_model_files(){
        $model_files = [];

        foreach (glob($this->_model_paths."*.php") as $file) :
            $model_files[]=$file;
        endforeach;

        return $model_files;
    }

    /**
     * Returns a list of all model names in lowercase
     * @return array
     */
    public function get_model_classes(){
        $models = [];
        foreach ($this->get_model_files() as $file) :
            $models[]=$this->get_model_name($file);
        endforeach;

        return $models;
    }

    /**
     * Gets a model name from its model file name.
     * @param $file
     * @return string
     */
    public function get_model_name($file){
        return strtolower(trim(basename($file, '.php')));
    }

    public function find_model_state($name){
        $this->_from_models();
        foreach ($this->models as $model):
            if(strtolower($model->model_name) == strtolower($name)):
                return $model;
            endif;
        endforeach;


    }
}