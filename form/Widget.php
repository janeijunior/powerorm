<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 6/23/16
 * Time: 3:55 PM
 */

namespace powerorm\form\widgets;


use powerorm\exceptions\NotImplemented;
use powerorm\form\fields\ChoiceField;
use powerorm\helpers\Tools;
use powerorm\model\field\TextField;
use powerorm\Object;

/**
 * base class for all widgets, should never initialized
 * Class Widget
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Widget extends Object
{

    public $attrs;
    public $needs_multipart_form = FALSE;
    public $is_required = FALSE;

    public function __construct($attrs=[], $kwargs=[])
    {
        $this->attrs = $attrs;
        $this->init();
    }

    public static function instance($attrs=[], $kwargs=[]){
        return new static($attrs, $kwargs);
    }

    public function build_attrs($attrs=[], $kwargs=[])
    {

        $final_attrs = array_merge($this->attrs, $kwargs);

        if(!empty($attrs)):
            $final_attrs = array_merge($final_attrs, $attrs);
        endif;

        return $final_attrs;
    }

    public function render($name, $value, $attrs=[], $kwargs=[])
    {
        throw new NotImplemented('subclasses of Widget must provide a render() method');

    }

    /**
     * Prepare value for use on HTML widget
     * @param $value
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare_value($value){
        return $value;
    }

    public function value_from_data_collection($data, $name)
    {
        return (isset($data[$name])) ? $data[$name]: NULL;
    }

    public function is_hidden(){
        return ($this->has_property('input_type')) ? $this->input_type==='hidden' : FALSE;
    }

    public function flat_attrs($attrs){
        $str_attrs = '';
        foreach ($attrs as $key=>$attr) :
            if($attrs===TRUE || $attrs===FALSE):
                $str_attrs .= ' '.$key;
            else:
                $str_attrs .= sprintf(' %1$s = %2$s',$key, $attr);
            endif;
        endforeach;

        return $str_attrs;
    }
}

/**
 * base class for all input widgets, should never initialized
 * Class Input
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Input extends Widget{
    public $input_type = NULL;

    public function render($name, $value, $attrs=[], $kwargs=[])
    {
        $final_attrs = $this->build_attrs($attrs, ['type'=>$this->input_type, 'name'=>$name]);

        // if we have value , add it
        if(!empty($value)):
            $final_attrs['value'] = $this->prepare_value($value);
        endif;

        return sprintf('<input %s>', $this->flat_attrs($final_attrs));
    }
}

/**
 * Text input: <input type="text" ...>
 *
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class TextInput extends Input{
    public $input_type = 'text';
}

/**
 * Text input: <input type="email" ...>
 *
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class EmailInput extends TextInput{
    public $input_type = 'email';
}

/**
 * Text input: <input type="url" ...>
 *
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class UrlInput extends TextInput{
    public $input_type = 'url';
}

/**
 * Similar to Select, but allows multiple selection: <select multiple='multiple'>...</select>
 *
 * Class PasswordInput
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class PasswordInput extends TextInput{
    public $input_type = 'password';
}

/**
 * Text input: <input type="number" ...>
 *
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class NumberInput extends TextInput{
    public $input_type = 'number';
}

/**
 * Hidden input: <input type='hidden' ...>
 *
 * Note that there also is a MultipleHiddenInput widget that encapsulates a set of hidden input elements.
 *
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class HiddenInput extends TextInput{
    public $input_type = 'hidden';
}

/**
 * Text area: <textarea>...</textarea>
 *
 * Class TextArea
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class TextArea extends Widget{

    public function __construct($attrs=[], $kwargs=[]){
        $default_attrs = ['cols'=>'40', 'rows'=> '10'];
        if(!empty($attrs)):
            $attrs = array_merge($default_attrs, $attrs);
        endif;
        parent::__construct($attrs);
    }

    public function render($name, $value, $attrs=[], $kwargs=[]){
        $final_attrs = $this->build_attrs($attrs, ['type'=>$this->input_type, 'name'=>$name]);

        return sprintf('<textarea %1$s>%2$s</textarea>', $final_attrs, $this->prepare_value($value));
    }
}

/**
 * This creates a single checkbox on the fields, to create more than one use {@see MultipleCheckboxes}
 *
 * Checkbox: <input type='checkbox' ...>
 *
 * Class CheckboxInput
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CheckboxInput extends Widget{

    public function render($name, $value, $attrs=[], $kwargs=[])
    {
        $final_attrs = $this->build_attrs($attrs, ['type'=>'checkbox', 'name'=>$name]);

        // if we have value , add it
        // but since we are dealing with checkbox, this will be checked
        if($this->is_checked($value)):
            $final_attrs['checked'] = 'checked';
        endif;

        if(!empty($value)):
            return (string) $value;
        endif;

        return sprintf('<input %s>', $this->flat_attrs($final_attrs));
    }

    public function is_checked($value){
        return !empty($value);
    }

    public function value_from_data_collection($data, $name)
    {
        // checkboxes are either checked or not checked they dont take values like other input fields
        if(!array_key_exists($name, $data)):
            return FALSE;
        endif;

        $value = $data[$name];

        if(is_bool($value)):
            return $value;
        endif;


        // type cast otherwise
        return (bool) $value;
    }
}



// **************************************************************************************************

// ******************************************* CHOICES *******************************************

// **************************************************************************************************



/**
 *
 * Select widget: <select><option ...>...</select>
 *
 * Options:
 *
 *  choices
 *
 *      This attribute is optional when the form field does not have a choices attribute.
 *      If it does, it will override anything you set here when the attribute is updated on the Field.
 *
 * Class Select
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Select extends Widget{
    public $multiple_selected = FALSE;
    public $open_select = '<select %s >';

    public $choices=[];

    public function __construct($attrs=[], $kwargs=[]){
        parent::__construct($attrs);

        if(array_key_exists('choices', $kwargs)):
            $this->choices = $kwargs['choices'];
        endif;
    }

    public function render($name, $value, $attrs=[], $kwargs=[]){
        if(empty($value)):
            // in case its null, false etc
            $value = [];
        endif;

        $final_attrs = $this->build_attrs($attrs, ['name'=>$name]);
        $output = [];
        // open select
        $output[] = sprintf($this->open_select, $this->flat_attrs($final_attrs));
        // add select options
        $options[] = $this->render_options($value);

        if(!empty($options)):
            $output = array_merge($output, $options);
        endif;
        // close select
        $output[] = '</select>';

        return join(' ', $output);
    }

    public function _prepare_selected($selected){
        return (is_array($selected) && empty($selected))? $selected : [$selected];
    }

    public function render_options($selected_choices){
        $selected_choices = $this->_prepare_selected($selected_choices);

        /**
         * 'choices'=>[
         *      'gender'=> ['f'=>'Female', 'm'=>'Male' ],
         *      'bmw'=>'mercedes benz'
         * ]
         */
        $choices = $this->choices;

        $output = [];

        foreach ($choices as $label=>$value) :

            if(is_array($value)):

                $output[] = sprintf('<optgroup label="%s">', $label);

                foreach ($value as $c_label=>$c_value) :

                    $output[] = $this->render_option($selected_choices, $c_value, $c_label);

                endforeach;

                $output[] = '</optgroup>';
            else:

                $output[] = $this->render_option($selected_choices, $value, $label);
            endif;
//
        endforeach;

        return join(' ', $output);
    }

    public function render_option($selected_choices, $label, $value){
        $selected_html = '';

        if(in_array($value, $selected_choices)):
            $selected_html = 'selected="selected"';
        endif;

        return sprintf('<option value="%1$s" %2$s >%3$s</option>',$value, $selected_html, $label);
    }
}

/**
 * Similar to Select, but allows multiple selection: <select multiple='multiple'>...</select>
 *
 * Class SelectMultiple
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class SelectMultiple extends Select{
    public $multiple_selected = TRUE;
    public $open_select = '<select %s  multiple="multiple">';

    public function _prepare_selected($selected_choices){
        $selected = [];
        foreach ($selected_choices as $choice) :
            $selected[] = (string)$choice;
        endforeach;
        return $selected;
    }
}

/**
 * Base class of widgets like checkbox and radio which can be more than one.
 * Class ChoiceInputFields
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class ChoiceInputFields extends Input{
    public $choices=[];
    public $input_type = '';
    public $outer_html = '<ul %1$s> %2$s </ul>';
    public $inner_html = '<li>%1$s %2$s </li>';

    public function __construct($attrs=[], $kwargs=[]){
        parent::__construct($attrs);

        if(array_key_exists('choices', $kwargs)):
            $this->choices = $kwargs['choices'];
        endif;
    }

    public function render($name, $value, $attrs=[], $kwargs=[]){

        if(empty($value)):
            // in case its null, false etc
            $value = [];
        endif;

        $output = [];

        // add select options
        $options[] = $this->render_options($name, $value, $attrs);

        if(!empty($options)):
            $output = array_merge($output, $options);
        endif;

        return join(' ', $output);
    }

    public function render_options($field_name, $checked_choices, $attrs=[]){

        /**
         * 'choices'=>[
         *      'gender'=> ['f'=>'Female', 'm'=>'Male' ],
         *      'bmw'=>'mercedes benz'
         * ]
         */
        $choices = $this->choices;

        $output = [];

        $count = 1;
        foreach ($choices as $label=>$value) :

            $attrs_ = $this->build_attrs($attrs,[
                'name'=>$field_name,
                'type'=>$this->input_type,
            ]);

            $attrs_['id'] = $attrs_['id'].'_'.$count;

            if(is_array($value)):

                $sub_widget = new static($attrs_, ['choices'=>$value]);

                $output[] = sprintf($this->inner_html, $label, $sub_widget->render($field_name, $checked_choices));

            else:
                $sub_widget = '';

                $output[] = sprintf($this->inner_html,
                    $this->render_option($checked_choices, $value, $label, $attrs_), $sub_widget);
            endif;

            $count++;
        endforeach;

        return sprintf($this->outer_html, $this->flat_attrs($attrs),join(' ', $output));
    }

    public function render_option($checked_choices, $label, $value, $attrs=[]){

        $checked_html = '';
        $attrs['value'] = $value;

        $checked = $this->_prepare_checked($checked_choices);

        if(in_array($value, $checked)):
            $checked_html = 'checked="checked"';
        endif;

        $template = '<label for=""> <input %1$s %2$s/> %3$s </label>';

        return sprintf($template, $this->flat_attrs($attrs), $checked_html, $label);
    }

    public function _prepare_checked($checked_choices){
        return $checked_choices;
    }
}


/**
 * Similar to Select, but rendered as a list of radio buttons within
 *
 * Class RadioSelect
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RadioSelect extends ChoiceInputFields{
    public $input_type = 'radio';


    public function _prepare_checked($selected){
        return (is_array($selected) && empty($selected))? $selected : [$selected];
    }

}

/**
 * Similar to SelectMultiple, but rendered as a list of check buttons:
 *
 * Class CheckboxSelectMultiple
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class MultipleCheckboxes extends ChoiceInputFields{
    public $input_type = 'checkbox';

    public function _prepare_checked($selected_choices){
        $selected = [];
        foreach ($selected_choices as $choice) :
            $selected[] = (string)$choice;
        endforeach;
        return $selected;
    }

}

