<?php
namespace powerorm\migrations;

use powerorm\exceptions\OrmExceptions;
use powerorm\migrations\operations\AddM2MField;
use powerorm\migrations\operations\AddModel;
use powerorm\migrations\operations\AlterField;
use powerorm\migrations\operations\DropField;
use powerorm\migrations\operations\DropM2MField;
use powerorm\migrations\operations\DropModel;
use powerorm\migrations\operations\AddField;
use powerorm\model\ProxyModel;

/**
 * Class AutoDetector
 * @package powerorm\migrations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AutoDetector{
    public $operations = [];
    public $current_state;
    public $proxies = [];

    // use proxy objects to create migration this is to allow storing model state in file
    // add reference after creating table
    public function __construct($current_state, $history_state){
        $this->history_state = $history_state;
        $this->current_state = $this->_prepare($current_state);
    }

    public function _prepare($current_state){
        $current_state->models = $this->_model_resolution_order($current_state->models);
        return $current_state;
    }

    public function _model_resolution_order($models){
        $order_models = [];

        // loop as many times as the size of models passed in.
        $i = 0;
        while($i< count($models)):

            foreach ($models as $name=>$model) :
                if(empty($model->relations_fields)):
                    $order_models[$name]= $model;
                endif;

                $existing_models = array_merge(array_keys($order_models), $this->migrated_models());

                $dependencies = [];
                foreach ($model->relations_fields as $field) :
                    $dependencies[] = strtolower($field->related_model->meta->model_name);

                    $missing =array_diff($dependencies, $existing_models);

                    if(count($missing)==0):
                        $order_models[$name]= $model;
                    endif;

                endforeach;

            endforeach;
            $i++;
        endwhile;
        return $order_models;
    }

    public function get_operations(){
        return $this->find_changes();
    }

    public function migrated_models(){
        return array_keys($this->history_state->models);
    }

    public function operations_todo($model_name, $operation, $dependency){
        $this->operations[] = [
            'model_name'=>strtolower($model_name),
            'operation'=> $operation,
            'dependency'=>$dependency
        ];
    }

    public function find_changes(){
        # Generate non-rename model operations
        $this->find_deleted_models();
        $this->find_created_models();
        $this->find_added_fields();
        $this->find_dropped_fields();
        $this->find_altered_fields();

        // first resolve dependencies, to ensure that we don't add an operation that expects a model to
        // already exist only to find it does not
        $this->_operation_resolution_order();

        // try to merge some of the operations, e.g AddModel and AddField can be merged if the act on same model
        // and depend on model that already exists

        return $this->_optimize($this->operations);
    }

    public function find_created_models(){

        if(!empty($this->history_state->models)):
            $past_model_names = array_keys($this->history_state->models);
        else:
            $past_model_names = [];
        endif;

        $current_models = $this->_model_resolution_order($this->current_state->models);


        $current_model_names = array_keys($current_models);

        $added_models = array_values(array_diff($current_model_names, $past_model_names));

        // go through the created models and create necessary operations
        foreach ($added_models as $added_model) :

            $model_state = $current_models[$added_model];


            // create model operation
            $this->operations_todo(
                $added_model,
                new AddModel($added_model, $model_state->local_fields, ['table_name'=>$model_state->db_table]),
                []
            );

            // add relation operations
            // we do this separately because need the model to be created before the relationships are created
            // maybe optimize later
            foreach ($model_state->relations_fields as $field) :
                $field_depends_on = [ucwords(strtolower($field->related_model->meta->model_name)),
                    ucwords(strtolower($added_model))];
                if($field->inverse):
                    continue;
                endif;
                if($field->M2M):
                   $this->_add_m2m_field($model_state, $field, $field_depends_on);
                else:
                    $this->operations_todo($added_model,
                        new AddField($added_model, $field, ['table_name'=>$model_state->db_table]),
                        $field_depends_on
                    );
                endif;

            endforeach;

        endforeach;
    }

    public function find_deleted_models(){

        if(empty($this->history_state->models)):
            return;
        endif;

        $current_models = $this->current_state->models;

        $current_model_names = array_keys($current_models);
        $past_model_names = array_keys($this->history_state->models);

        $deleted_models = array_values(array_diff($past_model_names, $current_model_names));

        foreach ($deleted_models as $deleted_model) :
            $model_state = $this->history_state->models[$deleted_model];
            $name = $model_state->db_table;
            if(preg_match("/_fake_/", $name)):
                $name = str_replace('_fake_\\', '', $name);
            endif;
            $this->operations_todo(
                $deleted_model,
                new DropModel($deleted_model, $model_state->local_fields, ['table_name'=>$name]),
                []
            );
        endforeach;


    }

    public function find_added_fields(){
        if(empty($this->history_state->models)):
          return;
        endif;

        // search for each model in the migrations, if present get its fields
        // note those that we added
        foreach ($this->current_state->models as $model_name => $model_meta) :
            if(!isset($this->history_state->models[$model_name])):
                continue;
            endif;
            $model_past_state = $this->history_state->models[$model_name];
            $current_fields = array_keys($model_meta->fields);
            $past_fields = array_keys($model_past_state->fields);

            $new_fields_names = array_values(array_diff($current_fields, $past_fields));

            if(empty($new_fields_names)):
                continue;
            endif;

            foreach ($new_fields_names as $field_name) :
                $field = $model_meta->fields[$field_name];
                $field_depends_on = [];

                if(isset($field->inverse) && $field->inverse):
                    continue;
                endif;
                if(isset($field->related_model)):
                    $field_depends_on = [ucwords(strtolower($field->related_model->meta->model_name)),
                        ucwords(strtolower($model_name))];
                endif;

                if($field->M2M):
                    $this->_add_m2m_field($model_meta, $field, $field_depends_on);
                else:
                    $this->operations_todo($model_name,
                        new AddField($model_name, $field, ['table_name'=>$model_meta->db_table]),
                        $field_depends_on
                    );
                endif;
            endforeach;
        endforeach;


    }

    public function find_dropped_fields(){

        if(empty($this->history_state->models)):
          return;
        endif;

        // search for each model in the migrations, if present get its fields
        // note those that we added
        foreach ($this->current_state->models as $model_name => $model_obj) :
            if(!isset($this->history_state->models[$model_name])):
                continue;
            endif;
            $model_past_state = $this->history_state->models[$model_name];

            $current_fields = array_keys($model_obj->fields);
            $past_fields = array_keys($model_past_state->fields);

            $dropped_fields_names = array_values(array_diff($past_fields, $current_fields));


            if(empty($dropped_fields_names)):
                continue;
            endif;

            foreach ($dropped_fields_names as $field_name) :
                $field = $model_past_state->fields[$field_name];

                $field_depends_on = [];
                if(isset($field->related_model)):
                    $field_depends_on = [ucwords(strtolower($field->related_model->meta->model_name)),
                        ucwords(strtolower($model_name))];
                endif;

                if($field->M2M):
                    $this->_drop_m2m_field($model_obj, $field, $field_depends_on);
                else:
                    $this->operations_todo($model_name,
                        new DropField($model_name, $field, ['table_name'=>$model_obj->db_table]), $field_depends_on
                    );
                endif;
            endforeach;
        endforeach;


    }
    
    public function find_altered_fields(){
        if(empty($this->history_state->models)):
            return;
        endif;

        $date_fields = [];
        foreach ($this->current_state->models as $model_name => $model_obj) :
            if(!isset($this->history_state->models[$model_name])):
                continue;
            endif;
            $model_past_state = $this->history_state->models[$model_name];

            $past_fields = $model_past_state->fields;

            $unique_fields = [];
            foreach ($model_obj->fields as $name=>$field) :

                if(isset($field->inverse) && $field->inverse):
                    continue;
                endif;

                // if there is nothing in the past no need to go on
                if(!isset($past_fields[$name])):
                    continue;
                endif;

                $current_options = $field->options();
                $past_options = $past_fields[$name]->options();


                $modified_field_names = array_diff_assoc($current_options, $past_options);

                // triggers work on full table so work on them here
                if(array_key_exists('on_update', $modified_field_names) ||
                    array_key_exists('on_creation', $modified_field_names)):

                    array_push($date_fields, $name);
                endif;

                foreach (['constraint_name', 'update_on', 'on_creation'] as $f_name) :
                    if(array_key_exists($f_name, $modified_field_names)):
                        unset($modified_field_names[$f_name]);
                    endif;
                endforeach;


                // if there are not modifications found, no need to go on.
                if(empty($modified_field_names)):
                    continue;
                endif;
                if(!empty($modified_field_names)):
                    $this->operations_todo(
                        $model_name,
                        new AlterField($model_name,
                            [$name=>['present'=>$field, 'past'=>$past_fields[$name]]],
                            ['table_name'=>$model_obj->db_table,]
                        ),
                        []
                    );
                endif;
            endforeach;

        endforeach;

    }

    public function _dependency_check($operation, $history){
        // get already existing models
        $existing_models = $this->migrated_models();

        if(!empty($history)):

            // get names of models they act on, this means they create or act on a modes thats already created
            foreach ($history as $e_op) :
                $existing_models[] = ucwords(strtolower($e_op['operation']->model_name));
            endforeach;
        endif;

        // do the models that the operation depends on exist
        return array_diff($operation['dependency'], $existing_models);
    }

    public function _self_referencing($model, $dependency){
        return [ucwords(strtolower($model)), ucwords(strtolower($model))] == $dependency;
    }

    public function _optimize($operations){
        foreach ($operations as  $index=>&$main_operation) :

            // look forward through all the other operations and see if the can be merged
            // if merged remove them from the operations
            // if none add it to the new array
            foreach ($operations as  $candidate_index=>$candidate_operation) :

                // get operations between start and position of AddModel
                $history = array_slice($this->operations, 0, $index+1);

                // check if the candidate depends on models that don't exist
                $pending = $this->_dependency_check($candidate_operation, $history);

                // if they act on same model they can merge
                if($main_operation['model_name'] == $candidate_operation['model_name'] &&
                    empty($pending) &&
                    $index!=$candidate_index):

                    // IF A MERGE HAS HAPPENED REMOVE THE CURRENT CANDINDATE FROM THE LIST OF OPERATIONS
                    $combined = $this->_merge($main_operation, $candidate_operation);

                    if($combined):
                        // use unset over array_splice, since unset preserves the keys
                        unset($operations[$candidate_index]);
                    endif;
                endif;
            endforeach;

        endforeach;

        return array_values($operations);
    }

    /**
     * Orders the operations so that operations don't depend on models that dont exist.
     * @throws OrmExceptions
     */
    public function _operation_resolution_order(){
        var_dump("-resolution");
        $ordered_ops = [];
        $dependent_ops = [];
        $proxy_ops = [];

        // holds names of models that already exist/ are to be created
        $created_models = $this->migrated_models();

        // first those that depend on nothing
        // those that depend on one model and is not self referencing
        foreach ($this->operations as $op) :
            $mod_name = $op['model_name'];
            // no dependency mostly AddModel operation
            if(empty($op['dependency'])):
                $ordered_ops[] = $op;
                $created_models[] = $op['model_name'];
                continue;
            endif;

            // if this is not a proxy model.
            $is_proxy_model = isset($op['operation']->options['proxy_model']) && $op['operation']->options['proxy_model'];

            // with dependency come later
            if($is_proxy_model):
                $proxy_ops[] = $op;
                continue;
            endif;

            // with dependency come later
            if(!empty($op['dependency'])):
                $dependent_ops[] = $op;
            endif;
        endforeach;

        // those with dependency come next
        foreach ($dependent_ops as $dep_op) :

            if(in_array($dep_op['model_name'], $created_models)):
                $ordered_ops[] = $dep_op;
            else:
                throw new OrmExceptions(
                    sprintf('Trying `%1$s` that depends on model `%2$s` that does not seem to exist',
                        get_class($dep_op['operation']), $dep_op['model_name']));
            endif;
        endforeach;

        foreach ($proxy_ops as $dep_op) :

            $deps =[];
            foreach ($dep_op['dependency'] as $dep) :
                $deps[] = strtolower($dep);
            endforeach;
            $mission_dep = array_diff($deps, $created_models);

            if(count($mission_dep)==0):
                $ordered_ops[] = $dep_op;
            else:
                throw new OrmExceptions(
                    sprintf('Trying `%1$s` that depends on model `%2$s` that does not seem to exist',
                        get_class($dep_op['operation']), json_encode($mission_dep)));
            endif;
        endforeach;
        $this->operations = $ordered_ops;

    }

    public function _add_m2m_field($owner_meta, $field, $field_depends_on){

        if(empty($field->through)):
            $inverse_meta = $field->related_model->meta;
            $proxy = new ProxyModel($owner_meta,$inverse_meta);
            $name = strtolower($owner_meta->model_name);

            $this->operations_todo(
                $name,
                new AddM2MField($name, [$field->name=>$field], $proxy,
                    ['table_name'=>$proxy->meta->db_table, 'proxy_model'=>$proxy->meta->proxy_model]),
                $field_depends_on);
        endif;
    }

    public function _drop_m2m_field($owner_meta, $field, $field_depends_on){

        if(empty($field->through)):
            $inverse_meta = $field->related_model->meta;
            $proxy = new ProxyModel($owner_meta,$inverse_meta);
            $name = strtolower($owner_meta->model_name);

            $this->operations_todo(
                $name,
                new DropM2MField($name, [$field->name=>$field], $proxy,
                    ['table_name'=>$proxy->meta->db_table, 'proxy_model'=>$proxy->meta->proxy_model]),
                $field_depends_on);
        endif;

    }

    public function _merge(&$operation, $candidate_operation){

        if($operation['operation'] instanceof AddModel && $candidate_operation['operation'] instanceof AddField):
            return $this->_merge_model_add_and_field_add($operation, $candidate_operation);
        endif;

        if($operation['operation'] instanceof AddField && $candidate_operation['operation'] instanceof AddField):
            return $this->_merge_field_add($operation, $candidate_operation);
        endif;

        if($operation['operation'] instanceof DropField && $candidate_operation['operation'] instanceof DropField):
            return $this->_merge_field_drop($operation, $candidate_operation);
        endif;

        if($operation['operation'] instanceof AlterField && $candidate_operation['operation'] instanceof AlterField):
            return $this->_merge_field_alter($operation, $candidate_operation);
        endif;
    }

    /**
     * Tries to merge operations, returns null on fail, or $candidate_operation merged with the $operation
     * @param $operation
     * @param $candidate_operation
     * @param $before_candidate_operations
     * @return mixed
     */
    public function _merge_model_add_and_field_add(&$operation, $candidate_operation){
        $model_name = $operation['model_name'];

        // if self referencing just pass
        if($this->_self_referencing($model_name, $candidate_operation['dependency'])):
            return FALSE;
        endif;

        $fields = $candidate_operation['operation']->fields;

        foreach ($fields as $field) :
            $operation['operation']->fields[$field->name]= $field;
        endforeach;

        return TRUE;

    }

    public function _merge_field_add(&$operation, $candidate_operation){
        $fields = $candidate_operation['operation']->fields;
        foreach ($fields as $field) :
            $operation['operation']->fields[$field->name]= $field;
        endforeach;

        return TRUE;
    }

    public function _merge_field_drop(&$operation, $candidate_operation){
        $fields = $candidate_operation['operation']->fields;
        foreach ($fields as $field) :
            $operation['operation']->fields[$field->name]= $field;
        endforeach;

        return TRUE;
    }

    public function _merge_field_alter(&$operation, $candidate_operation){
        $next = $candidate_operation['operation']->fields;
        $present = $operation['operation']->fields;
        $operation['operation']->fields = array_merge($present, $next);
        return TRUE;
    }

    public function _search_m2m($model){
        $m2m_fields = [];
        foreach ($model->relations_fields as $field) :
            if($field instanceof \ManyToMany):
                $m2m_fields[] = $field;
            endif;
        endforeach;
        return $m2m_fields;
    }
}

// ToDo very serious validation on foreignkey constraint, based on cascade passed in.