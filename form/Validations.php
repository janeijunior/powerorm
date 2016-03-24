<?php
namespace powerorm\form;


defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 1/11/16
 * Time: 11:55 AM
 */
class Validations{
    protected $_CI;
    public function __construct(){
        $this->_CI = & get_instance();
        // incase it not loaded
        $this->_CI->load->library('form_validation');
    }

    public function is_password_strong($password){

        $status = FALSE;
        // ensure length
        if(strlen($password)>=8):

            // ensure the password contains atleast an uppercase and a lowercase character
            if(preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)){
                $status=TRUE;
            }

            // ensure it has atleast numeric value
            if(!preg_match('/[0-9]/', $password)){
                $status=FALSE;
            }
            // ensure it has atleast special character `$-#@&` values
            if(!preg_match('/[^a-z0-9]/', $password)){
                $status=FALSE;
            }
        endif;

        if(!$status){
            $message = 'The password field requires'.
                ' <ul><li>eight characters and must contain atleast</li> '.
                '<li>one capital letter,</li> <li>one special character and <li>one numeric character.</li></ul>';
            $this->_CI->form_validation->set_message('is_password_strong', $message);
        }

        return $status;
 

    }

}