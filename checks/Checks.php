<?php
namespace powerorm\checks;
use powerorm\cli\ColorCLi;
use powerorm\migrations\ProjectState;

class Checks{

    public static function run(){
        Checks::model_checks();
    }
    
    public static function model_checks(){
        ColorCLi::info("Performing System Checks .....");

        $err_check = [];
        foreach (ProjectState::app_model_objects() as $name=>$model_obj) :
            $checks = $model_obj->check();

            foreach ($checks as $check) :

                if($check instanceof Error):
                    $err_check[] = $check;
                endif;

                if($check instanceof Warning):
                    $warning_check[] = $check;
                endif;
            endforeach;

        endforeach;

        if(!empty($err_check)):
            ColorCLi::info("System check found some anomalies : ");

            Checks::_display($err_check, TRUE);
        endif;

        if(!empty($warning_check)):
            ColorCLi::info("Take note of the following : ");

            Checks::_display($warning_check);
        endif;

        if(empty($err_check) && empty($warning_check)):
            ColorCLi::success("System checks passed");
        endif;

    }

    public static function _display($checks, $exit=FALSE){

        if(!empty($checks)):

            foreach ($checks as $check) :
                $check->message();
            endforeach;

            if($exit):
                exit;
            endif;

        endif;
    }

}

// some shorthand functions
function check_error($field_obj, $message){
    return new Error($field_obj, $message);
}

function check_warning($field_obj, $message){
    return new Warning($field_obj, $message);
}

function check_info($message){
    return new Info($message);
}

function check_success($message){
    return new Success($message);
}