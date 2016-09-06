<?php

namespace eddmash\powerorm\migrations;

use eddmash\powerorm\migrations\operations\field\AddField;
use eddmash\powerorm\migrations\operations\field\AlterField;
use eddmash\powerorm\migrations\operations\field\DropField;
use eddmash\powerorm\migrations\operations\field\RenameField;
use eddmash\powerorm\migrations\operations\model\CreateModel;
use eddmash\powerorm\migrations\operations\model\DropModel;
use eddmash\powerorm\migrations\operations\model\RenameModel;
use eddmash\powerorm\model\field\ManyToManyField;
use eddmash\powerorm\NOT_PROVIDED;
use eddmash\powerorm\Object;

/**
 * This class detects any changes in the models and creates migration files to reflect this changes.
 *
 * Class AutoDetector
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AutoDetector extends Object
{
    protected $operations = [];
    protected $new_state;
    protected $old_state;
    protected $questioner;

    const ACTION_CREATED = 'created';
    const ACTION_DROPPED = 'dropped';
    const ACTION_ALTER = 'alter';

    const TYPE_MODEL = 'model';
    const TYPE_FIELD = 'field';

    /**
     * @param ProjectState    $old_state
     * @param ProjectState    $new_state
     * @param Questioner|null $questioner
     */
    public function __construct(ProjectState $old_state, ProjectState $new_state, Questioner $questioner = null)
    {
        $this->new_state = $new_state;
        $this->old_state = $old_state;
        $this->questioner = (is_null($questioner)) ? Questioner::instance() : $questioner;
    }

    /**
     * Takes a migration graph and returns all the changes that have happend within the models.
     *
     * @param Graph $graph
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function changes(Graph $graph)
    {
        $changes = $this->find_changes();

        $changes = $this->prepare_for_graph($changes, $graph);

        return $changes;
    }

    /**
     * @param array  $changes
     * @param Graph  $graph
     * @param string $migration_name
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare_for_graph(array $changes, Graph $graph, $migration_name = null)
    {
        $leaves = $graph->leaf_nodes();

        $leaf = (empty($leaves)) ? '' : $leaves[0];

        if (empty($changes)):
            return [];
        endif;

        foreach ($changes as $migration) :

            if (empty($leaf)):
                $migration_no = 1;
            else:
                $migration_no = $this->get_migration_number($leaf) + 1;

            endif;

            // set name for migration
            if (empty($leaf)):
                // this mean we don't have previous migrations
                $migration_name = $this->suggest_name();
            else:
                // first set previous as dependency of this
                // $migration->requires = [$leaf];
                array_push($migration->requires, $leaf);

                $migration_no = str_pad($migration_no, 4, '0', STR_PAD_LEFT);
                $migration_name = $this->suggest_name($migration->operations(), $migration_no);
//                $migration_name =  sprintf('%1$s_%2$s', $migration_no, $migration_name);
            endif;

            $migration->name = $migration_name;

        endforeach;

        return $changes;
    }

    /**
     * Trys to guess a name for the migration that is to be created.
     *
     * @param $operations
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function suggest_name($operations = null, $id = null)
    {
        $prefix = 'm';
        if ($operations === null):
            return sprintf('%s0001_initial', $prefix);
        endif;
        if (count($operations) == 1):
            return sprintf('%s%s_%s', $prefix, $id, $operations[0]->describe());
        else:
            return sprintf('%s%s_auto_%s', $prefix, $id, date('Ymd_hm'));
        endif;
    }

    public function fields_definitions($fields)
    {
        $field_defs = [];

        foreach ($fields as $name => $field) :
            $field_defs[] = [$name => $field->skeleton()];
        endforeach;

        return $field_defs;
    }

    public function get_migration_number($name)
    {
        $name = explode('_', $name);

        return (int) str_replace('m', '', $name[0]);
    }

    /**
     * returns of changes detected in the application models.
     *
     * @return array
     */
    public function find_changes()
    {
        $old_registry = $this->old_state->registry();
        $new_registry = $this->new_state->frozen_registry();

        $new_models_states = $this->new_state->models;
        $old_models_states = $this->old_state->models;

        // todo detect unmanaged and proxy changes
        $this->old_models_names = [];
        $this->old_proxy_keys = [];
        $this->old_unmanaged_keys = [];

        $this->new_models_names = [];
        $this->new_proxy_keys = [];
        $this->new_unmanaged_keys = [];

        $this->past_field_names = [];
        $this->new_field_names = [];

        // ******** first we find out Who was a proxy model, who was a managed model and who was a regular model
        $old_models_states_keys = array_keys($old_models_states);
        foreach ($old_models_states_keys as $prev_model_name) :
            $solid_prev_model = $old_registry->get_model($prev_model_name);

            if (!$solid_prev_model->meta->managed):
                $this->old_unmanaged_keys[] = $prev_model_name;
            elseif ($solid_prev_model->meta->proxy):
                $this->old_proxy_keys[] = $prev_model_name;
            else:
                $this->old_models_names[] = $prev_model_name;
            endif;
        endforeach;

        $new_models_states_keys = array_keys($new_models_states);
        foreach ($new_models_states_keys as $new_model_name) :

            $solid_new_model = $new_registry->get_model($new_model_name);

            if (!$solid_new_model->meta->managed):
                $this->new_unmanaged_keys[] = $new_model_name;
            elseif ($solid_new_model->meta->proxy):
                $this->new_proxy_keys[] = $new_model_name;
            else:
                $this->new_models_names[] = $new_model_name;
            endif;
        endforeach;

        // ********** Next, we learn about the fields in the models

        // models that exist in both states
        $this->common_model_names = array_intersect($this->new_models_names, $this->old_models_names);

        // get fields we use this to check for renamed fields
        // for each of the common models
        foreach ($this->common_model_names as $common_model_name) :
            $past_model_state = $this->old_state->get_model($common_model_name);
            $new_model_state = $this->new_state->get_model($common_model_name);

            $this->past_field_names[$common_model_name] = array_keys($past_model_state->fields);
            $this->new_field_names[$common_model_name] = array_keys($new_model_state->fields);
        endforeach;

        // ********* Learn about every nook and cranny that can be learnt from.

        // model
        $this->find_renamed_models();
        $this->find_dropped_models();
        $this->find_new_models();

        // fields
        $this->find_renamed_fields();
        $this->find_drop_fields();
        $this->find_added_fields();
        $this->find_altered_fields();

        // we need to sort the operation, this to ensure operations like DropModel which might depend a foreign keys
        // being dropped first come after the fields are dropped

        $changes = [];
        if (!empty($this->operations)):
            // then create a migration class for them.
            $migration_name = $this->suggest_name($this->operations);

            $migration = new Migration($migration_name);
            $migration->operations = $this->operations;

            $changes[] = $migration;
        endif;

        return $changes;
    }

    public function find_renamed_models()
    {
        $this->renamed_models = [];
        $this->renamed_models_to = [];

        // get new models
        $new_models_names = array_diff($this->new_models_names, $this->old_models_names);

        foreach ($new_models_names as $new_model) :
            $n_model_state = $this->new_state->get_model($new_model);
            $n_fields_def = $this->fields_definitions($n_model_state->fields);

            // get dropped models
            $dropped_models_names = array_diff($this->old_models_names, $this->new_models_names);
            foreach ($dropped_models_names as $dropped_model) :
                $o_model_state = $this->old_state->get_model($dropped_model);
                $o_fields_def = $this->fields_definitions($o_model_state->fields);

                if ($n_fields_def === $o_fields_def &&
                    $this->questioner->ask_rename_model($dropped_model, $new_model)
                ):

                    $this->add_operation(
                        new RenameModel([
                            'old_name' => $o_model_state->name,
                            'new_name' => $n_model_state->name,
                        ]),
                        []
                    );

                    $this->renamed_models[$n_model_state->name] = $o_model_state->name;
                    $this->renamed_models_to[$o_model_state->name] = $n_model_state->name;

                    $position = array_search($dropped_model, $this->old_models_names);
                    // remove old name
                    array_splice($this->old_models_names, $position, 1);

                    // set the new name
                    array_push($this->old_models_names, $new_model);
                endif;

            endforeach;

        endforeach;
    }

    public function find_new_models()
    {

        // get new models not in previous state
        $new_models_names = array_diff($this->new_models_names, $this->old_models_names);

        foreach ($new_models_names as $model_name) :
            // get meta info about the model
            $model_meta = $this->new_state->registry()->get_model($model_name)->meta;

            if (!empty($model_meta->local_fields)):
                $this->add_operation(
                    new CreateModel(['model' => $model_name, 'fields' => $model_meta->local_fields]),
                    [],
                    true
                );
            endif;

            // if its not managed no point in going on
            if (!$model_meta->managed):
                continue;
            endif;

            // create operations for related fields
            foreach ($model_meta->relations_fields as $field_name => $field) :
                // ignore inverse fields
                if ($field->is_inverse()):
                    continue;
                endif;

                $this->_add_field($field_name, $field, $model_name, false);
            endforeach;

        endforeach;
    }

    public function find_dropped_models()
    {

        // get models in previous state not in present state
        $dropped_models_names = array_diff($this->old_models_names, $this->new_models_names);

        foreach ($dropped_models_names as $model_name) :
            $model = $this->old_state->registry()->get_model($model_name);

            // remove the model
            $this->add_operation(
                new DropModel(['model' => $model->meta->model_name]),
                []
            );
        endforeach;
    }

    public function find_renamed_fields()
    {
        $this->renamed_fields = [];

        foreach ($this->common_model_names as $common_model_name) :
            $past_fields = $this->past_field_names[$common_model_name];
            $new_fields = $this->new_field_names[$common_model_name];

            // get new fields
            $new_fields_names = array_diff($new_fields, $past_fields);
            // get dropped fields
            $dropped_fields_names = array_diff($past_fields, $new_fields);
            // compare the field definitions, if similar enquire if its a rename
            foreach ($new_fields_names as $new_field_name) :
                $field = $this->new_state->registry()->get_model($common_model_name)->meta->get_field($new_field_name);
                $new_field = $this->new_state->get_model($common_model_name)->fields[$new_field_name];
                $new_field_def = $new_field->skeleton();

                foreach ($dropped_fields_names as $dropped_fields_name) :
                    $dropped_field = $this->old_state->get_model($common_model_name)->fields[$dropped_fields_name];
                    $drop_field_def = $dropped_field->skeleton();

                    if ($drop_field_def === $new_field_def):
                        if ($this->questioner->ask_rename($common_model_name, $dropped_fields_name, $new_field_name, $field)):
                            $this->add_operation(
                                new RenameField([
                                    'model' => $common_model_name,
                                    'old_name' => $dropped_fields_name,
                                    'new_name' => $new_field_name,
                                ]),
                                []
                            );

                            // replace old name with the new name in the list of past fields
                            array_splice($past_fields, array_search($dropped_fields_name, $past_fields), 1);
                            array_push($past_fields, $new_field_name);
                            $this->past_field_names[$common_model_name] = $past_fields;
                            $this->renamed_fields[$common_model_name][$new_field_name] = $dropped_fields_name;
                        endif;
                    endif;
                endforeach;

            endforeach;
        endforeach;
    }

    /**
     * Detect new fields.
     *
     * @internal
     */
    public function find_added_fields()
    {
        foreach ($this->common_model_names as $common_model_name) :
            $past_fields = $this->past_field_names[$common_model_name];
            $new_fields = $this->new_field_names[$common_model_name];

            // get new fields
            $new_fields_names = array_diff($new_fields, $past_fields);

            foreach ($new_fields_names as $field_name) :
                $model_meta = $this->new_state->registry()->get_model($common_model_name)->meta;

                $field = $model_meta->get_field($field_name);

                if ($field->is_inverse()):
                    continue;
                endif;

                $this->_add_field($field_name, $field, $common_model_name, $model_meta);
            endforeach;

        endforeach;
    }

    public function find_drop_fields()
    {
        foreach ($this->common_model_names as $common_model_name) :
            $past_fields = $this->past_field_names[$common_model_name];
            $new_fields = $this->new_field_names[$common_model_name];

            // get dropped fields
            $dropped_fields_names = array_diff($past_fields, $new_fields);

            foreach ($dropped_fields_names as $field_name) :
                $model_meta = $this->old_state->registry()->get_model($common_model_name)->meta;

                $field = $model_meta->get_field($field_name);

                if ($field->is_inverse()):
                    continue;
                endif;

                $this->_drop_field($field_name, $field, $common_model_name);
            endforeach;
        endforeach;
    }

    /**
     * Detects any field alterations.
     *
     * @internal
     */
    public function find_altered_fields()
    {
        foreach ($this->common_model_names as $common_model_name) :
            $past_fields = $this->past_field_names[$common_model_name];
            $new_fields = $this->new_field_names[$common_model_name];

            $common_fields = array_intersect($new_fields, $past_fields);

            foreach ($common_fields as $common_field_name) :
                // since the common models are got from the old model names which has been adjusted for renamed models
                // we need to get the initial model name before the rename, since that the name that will be present
                // in the past state as stored by the migrations files
                $past_model_name = $common_model_name;
                if ((array_key_exists($common_model_name, $this->renamed_models))):
                    $past_model_name = $this->renamed_models[$common_model_name];
                endif;

                // we also need to check for field renames
                $past_field_name = $common_field_name;
                if (array_key_exists($common_model_name, $this->renamed_fields)):

                    if (array_key_exists($common_field_name, $this->renamed_fields[$common_model_name])):
                        $past_field_name = $this->renamed_fields[$common_model_name][$common_field_name];
                    endif;
                endif;

                $old_field = $this->old_state->models[$past_model_name]->fields[$past_field_name];

                $new_field = $this->new_state->models[$common_model_name]->fields[$common_field_name];

                // look at this field in both state comparing them to seem if the differ i.e an alteration has occured

                $new_field_def = $new_field->skeleton();
                $old_field_def = $old_field->skeleton();

                if ($new_field_def !== $old_field_def):
                    $both_m2m = ($old_field->M2M && $new_field->M2M);
                    $neither_m2m = (!$old_field->M2M && !$new_field->M2M);
                    $preserve_default = true;

                    if (!$old_field->null &&
                        $new_field->null &&
                        !$new_field->has_default() &&
                        !$new_field->M2M
                    ):

                        $new_default = $this->questioner->ask_not_null_alteration($common_field_name, $common_model_name);

                        if ($new_default !== new NOT_PROVIDED()):
                            $new_field->default = $new_default;
                            $preserve_default = false;
                        endif;
                    endif;

                    if (!$both_m2m || $neither_m2m):

                        $this->add_operation(
                            new AlterField([
                                'model' => $common_model_name,
                                'field' => $new_field,
                                'name' => $common_field_name,
                                'preserve_default' => $preserve_default,
                            ]),
                            []
                        );
                    else:
                        $this->_drop_field($common_field_name, $old_field, $common_field_name);
                        $this->_add_field($common_field_name, $new_field, $common_field_name);
                    endif;

                endif;
            endforeach;
        endforeach;
    }

    public function add_dependency($item_name, $action, $type)
    {
        return ['item_name' => $this->standard_name($item_name), 'action' => $action, 'type' => $type];
    }

    /**
     * @param $field_name
     * @param $field
     * @param $model_name
     * @param bool|true $alter if true this method is being used to create a field that alters an existing model table.
     *                         otherwise its altering and existing model
     */
    public function _add_field($field_name, $field, $model_name, $alter = true)
    {
        $field_depends_on = [];

        $preserve_default = true;

        if (isset($field->related_model)):
            $field_depends_on = [
                // depend on the related model
                $this->add_dependency($this->standard_name($field->related_model->meta->model_name),
                    self::ACTION_CREATED, self::TYPE_MODEL),

                // depend on the model this field belongs to.
                $this->add_dependency($this->standard_name($model_name), self::ACTION_CREATED, self::TYPE_MODEL),
            ];
        endif;

        if ($field->null === false && $field->default instanceof NOT_PROVIDED && !$field instanceof ManyToManyField && $alter):
            $field->default = $this->questioner->ask_not_null_default($field->name, $model_name);

            // we don't want this default set permanently its just temporary
            $preserve_default = false;
        endif;

        $this->add_operation(
            new AddField([
                'model' => $model_name,
                'name' => $field_name,
                'field' => $field,
                'preserve_default' => $preserve_default,
            ]),
            $field_depends_on
        );
    }

    /**
     * @ignore
     *
     * @param $field
     * @param $model_name
     * @param $model_obj
     */
    public function _drop_field($field_name, $field, $model_name)
    {
        $field_depends_on = [];
        if (isset($field->related_model)):
            $field_depends_on = [ucwords($this->standard_name($field->related_model->meta->model_name)),
                ucwords($this->standard_name($model_name)), ];
        endif;

        $this->add_operation(
            new DropField([
                'model' => $model_name,
                'name' => $field_name,
            ]),
            $field_depends_on
        );
    }

    /**
     * @param $operation
     * @param array      $dependencies
     * @param bool|false $push_at_top  some operations should come before others, use this determine which
     */
    public function add_operation($operation, $dependencies = [], $push_at_top = false)
    {
        $operation->depends_on = $dependencies;

        if ($push_at_top):
            array_unshift($this->operations, $operation);
        else:
            array_push($this->operations, $operation);
        endif;
    }
}
