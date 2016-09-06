<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:09 PM.
 */
namespace eddmash\powerorm\model\field;

use eddmash\powerorm\checks\Checks;
use eddmash\powerorm\model\field\accessor\ForwardManyToOneAccessor;
use eddmash\powerorm\model\field\relation\ManyToOneObject;

/**
 * A Creates many-to-one relationship.
 *
 * Has one required argument:
 *
 * - model_name
 *
 *    The model to which current model is related to.
 *
 * To create a recursive relationship
 *
 * – an object that has a many-to-one relationship with itself – use ORM::ForeignKey('model_name').
 *
 * e.g. user_model that has self-refernce to itself
 *
 * ORM::ForeignKey('user_model')
 *
 * Option arguments:
 *
 * - db_index
 *
 *      You can choose if a database index is created on this field by setting db_index:
 *
 *          - FALSE - disable
 *          - TRUE - create index.
 *
 *      You may want to avoid the overhead of an index if you are creating a foreign key for consistency
 *      rather than joins.
 */
class ManyToOneField extends RelatedField
{
    /**
     * {@inheritdoc}
     */
    public $db_constraint = true;

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = [])
    {
        parent::__construct($field_options);
        $this->M2O = true;

        $this->relation = new ManyToOneObject([
            'model' => $field_options['model'],
            'field' => $this,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function db_column_name()
    {
        return sprintf('%s_id', $this->standard_name($this->name));
    }

    /**
     * {@inheritdoc}
     */
    public function constraint_name($prefix)
    {
        $prefix = 'fk';

        return parent::constraint_name($prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_unique_check());
        $checks = $this->add_check($checks, $this->_delete_check());

        return $checks;
    }

    /**
     * @ignore
     *
     * @return mixed
     */
    public function _unique_check()
    {
        if ($this->unique):
            return [
                Checks::warning([
                    'message' => 'Setting unique=True on a ForeignKey has the same effect as using a OneToOne.',
                    'hint' => 'use OneToOne field',
                    'context' => $this,
                    'id' => 'fields.W300',
                ]),
            ];
        endif;

        return [];
    }

    /**
     * @ignore
     */
    public function _delete_check()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @param $connection
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function db_type($connection)
    {
        return $this->related_field()->db_type($connection);
    }

    /**
     * The field on the relation model that this field links to.
     *
     * @return Field
     */
    public function related_field()
    {
        return $this->relation->get_model()->meta->primary_key;
    }

    public function contribute_to_class($name, $obj)
    {
        parent::contribute_to_class($name, $obj);
        $this->container_model->{$name} = ForwardManyToOneAccessor::instance($this->container_model, $this);
    }
}