<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Eddmash\PowerOrm\Model\Query;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Model;

/**
 * Class M2MQueryset.
 *
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
class M2MQueryset extends ParentQueryset
{
    /**
     * @var Model
     */
    private $instance;
    /**
     * @var Model
     */
    private $through;

    /**
     * @var \Eddmash\PowerOrm\Model\Field\Field
     */
    private $fromField;

    /**
     * @var bool
     */
    private $reverse;

    public function __construct(Connection $connection = null, Model $model = null, Query $query = null, $kwargs = [])
    {
        $this->instance = ArrayHelper::getValue($kwargs, 'instance');

        /** @var ForeignObjectRel $rel */
        $rel = ArrayHelper::getValue($kwargs, 'rel');
        $this->reverse = ArrayHelper::getValue($kwargs, 'reverse');

        if($this->reverse === false):
            $model = $rel->toModel;
            $this->queryName = $rel->fromField->getRelatedQueryName();
            $this->fromFieldName = call_user_func($rel->fromField->m2mField);
            $this->toFieldName = call_user_func($rel->fromField->m2mReverseField);
        else:
            $model = $rel->getFromModel();
            $this->queryName = $rel->fromField->name;
            $this->fromFieldName = call_user_func($rel->fromField->m2mReverseField);
            $this->toFieldName = call_user_func($rel->fromField->m2mField);
        endif;

        $this->through = $rel->through;

        $this->fromField = $this->through->meta->getField($this->fromFieldName);
        $this->toField = $this->through->meta->getField($this->toFieldName);
        $this->filters = [];

        foreach ([$this->fromField->getRelatedFields()] as $fields) :
            list($lhsField, $rhsField) = $fields;
            $key = sprintf('%s__%s', $this->queryName, $rhsField->name);
            $this->filters[$key] = $this->instance->{$rhsField->getAttrName()};
        endforeach;
        var_dump($this->filters);
        $this->relatedValues = $this->fromField->getForeignRelatedFieldsValues($this->instance);
        var_dump($this->relatedValues);
        if(empty($this->relatedValues)):
            throw new ValueError(
                sprintf('"%s" needs to have a value for field "%s" before this many-to-many relationship can be used.',
                    $this->instance->meta->modelName,
                    $this->fromFieldName));
        endif;

        parent::__construct(null, $model);
    }

    public function add()
    {
        func_get_args();
    }

}
