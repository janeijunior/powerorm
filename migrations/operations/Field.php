<?php
namespace powerorm\migrations\operations;



use powerorm\migrations\MysqlStatements;

class AddField extends Operation
{
    public $model_name;
    public $fields=[];
    public $options;

    public function __construct($model_name, $field, $options){
        $this->fields[$field->name] = $field;
        $this->model_name = $model_name;
        $this->options = $options;
    }

    public function up()
    {
        $triggers =  MysqlStatements::date_fields_triggers($this->db_table(), $this->fields);
        return array_merge(MysqlStatements::alter_table_add_field($this->db_table(), $this->fields), $triggers);
    }

    public function down()
    {
        return array_merge(MysqlStatements::drop_table_field($this->db_table(), $this->fields),
            MysqlStatements::date_fields_drop_triggers($this->db_table(), $this->fields));
    }

    public function message()
    {
        return "add";
    }

    public function state(){
        $model = ['model_name'=>$this->model_name,'operation'=>'add_field'];

        foreach ($this->fields as $field) :
            $fields['fields'][$field->name] = $field->skeleton();
        endforeach;


        return array_merge($model, $fields);
    }
}

class DropField extends Operation
{
    public $model_name;
    public $fields=[];
    public $options;

    public function __construct($model_name, $field, $options){
        $this->fields[$field->name] = $field;
        $this->model_name = $model_name;
        $this->options = $options;
    }

    public function up(){
        return MysqlStatements::drop_table_field($this->db_table(), $this->fields);
    }

    public function down()
    {
        return MysqlStatements::alter_table_add_field($this->db_table(), $this->fields);
    }

    public function message()
    {
        return "drop";
    }


    public function state(){
        $model = ['model_name'=>$this->model_name,'operation'=>'drop_field'];

        foreach ($this->fields as $field) :
            $fields['fields'][$field->name] = $field->skeleton();
        endforeach;


        return array_merge($model, $fields);
    }
}

class AlterField extends Operation{

    public $model_name;
    public $fields=[];
    public $options;
    public $forward=[];
    public $backward=[];
    public $unique_alter = [];
    public $index_alter = [];


    public function __construct($model_name, $fields, $options){

        $this->fields= $fields;

        $this->model_name = $model_name;
        $this->options = $options;
    }

    public function _setup(){
        foreach ($this->fields as $fields) :

            $past = $fields['past'];
            $present = $fields['present'];

            $this->forward[$present->name] = array_diff_assoc($present->options(), $past->options());
            $this->backward[$present->name] = array_diff_assoc($past->options(), $present->options());
        endforeach;

        foreach ($this->forward as $name => &$forward) :
            if(array_key_exists('unique', $forward)):
                $this->unique_alter[$name] = $forward['unique'];
            endif;
            if(array_key_exists('index', $forward)):
                $this->index_alter[$name] = $forward['index'];
            endif;
        endforeach;

        $this->_clean();

    }

    public function _clean(){

        foreach (['db_index', 'unique', 'constraint_name', 'on_update', 'on_creation'] as $key) :
            foreach ($this->backward as $index=>&$backward) :
                if(array_key_exists($key, $backward)):
                    unset($backward[$key]);
                endif;

                if(empty($backward)):
                    unset($this->backward[$index]);
                endif;
            endforeach;

            foreach ($this->forward as $index=>&$forward) :
                if(array_key_exists($key, $forward)):
                    unset($forward[$key]);
                endif;

                if(empty($forward)):
                    unset($this->forward[$index]);
                endif;
            endforeach;

        endforeach;
    }

    public function add_constraints($fields){
        $table = $this->db_table();
        $field_sql = [];
        // unique
        if(MysqlStatements::unique_constraint($fields) != NULL):
            foreach (MysqlStatements::unique_constraint($fields) as $index) :
                $field_sql[] =  MysqlStatements::_string_add_column_constraint($table, $index);
            endforeach;

        endif;

        // index
        if(MysqlStatements::add_indexes($fields) != NULL):
            foreach (MysqlStatements::add_indexes($fields) as $index) :
                $field_sql[] =  MysqlStatements::_string_add_column_constraint($table, $index);
            endforeach;

        endif;

        return $field_sql;
    }

    public function drop_constraints($fields){
        $table = $this->db_table();
        $field_sql = [];
        // drop indexes
        if(MysqlStatements::drop_indexes($fields) != NULL):
            foreach (MysqlStatements::drop_indexes($fields) as $index) :
                $field_sql[] = MysqlStatements::_string_drop_constraint($table, $index);
            endforeach;
        endif;
        return $field_sql;
    }

    public function up()
    {
        $this->_setup();

        $statement = [];


        if(!empty($this->forward)):
            array_push($statement, MysqlStatements::_string_modify_field($this->db_table(), $this->forward));
        endif;

        foreach ($this->fields as $name=>$field_obj) :


            if((isset($this->unique_alter[$name]) && $this->unique_alter[$name]===TRUE) ||
                (isset($this->index_alter[$name]) && $this->index_alter[$name]===TRUE)):
                $statement = array_merge($statement, $this->add_constraints([$this->fields[$name]['present']]));
            endif;

            if((isset($this->unique_alter[$name]) && $this->unique_alter[$name] ===FALSE) ||
                (isset($this->index_alter[$name]) && $this->index_alter[$name] === FALSE)):
                $statement = array_merge($statement,  $this->drop_constraints([$this->fields[$name]['past']]));
            endif;

        endforeach;

        return $statement;
    }

    public function down(){

        $this->_setup();

        $statement = [];

        if(!empty($this->backward)):
            array_push($statement, MysqlStatements::_string_modify_field($this->db_table(), $this->backward));
        endif;

        foreach ($this->fields as $name=>$field_obj) :


            if((isset($this->unique_alter[$name]) && $this->unique_alter[$name]===TRUE) ||
                (isset($this->index_alter[$name]) && $this->index_alter[$name]===TRUE)):
                $statement = array_merge($statement, $this->drop_constraints([$this->fields[$name]['present']]));
            endif;

            if((isset($this->unique_alter[$name]) && $this->unique_alter[$name] ===FALSE) ||
                (isset($this->index_alter[$name]) && $this->index_alter[$name] === FALSE)):
                $statement = array_merge($statement,  $this->add_constraints([$this->fields[$name]['past']]));
            endif;
        endforeach;


        return $statement;
    }

    public function message()
    {
        return 'modify_field';
    }

    public function state(){
        $model = ['model_name'=>$this->model_name,'operation'=>'modify_field'];

        $fields_collection['fields'] =[];
        foreach ($this->fields as $name=>$fields) :
            $fields_collection['fields'][$this->fields[$name]['present']->name] =
                $this->fields[$name]['present']->skeleton();
        endforeach;


        return array_merge($model, $fields_collection);
    }

}