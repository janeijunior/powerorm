<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Delete;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ManyToOneRel;
use Eddmash\PowerOrm\Model\Model;

class ForeignKey extends RelatedField
{
    public $manyToOne = true;
    public $dbConstraint = true;
    public $dbIndex = true;

    /**
     * The field on the related object that the relation is to.
     * By default, The Orm uses the primary key of the related object.
     *
     * @var
     */
    public $toField;

    /**
     * points to the current field instance.
     *
     * @var string
     */
    public $fromField;

    /**
     * @var ManyToOneRel
     */
    public $relation;

    public function __construct($kwargs)
    {

        if (!isset($kwargs['rel']) || (isset($kwargs['rel']) && $kwargs['rel'] == null)):
            $kwargs['rel'] = ManyToOneRel::createObject([
                'fromField' => $this,
                'to' => ArrayHelper::getValue($kwargs, 'to'),
                'toField' => ArrayHelper::getValue($kwargs, 'toField'),
                'parentLink' => ArrayHelper::getValue($kwargs, 'parentLink'),
                'onDelete' => ArrayHelper::getValue($kwargs, 'onDelete', Delete::CASCADE),
            ]);
        endif;

        $this->toField = ArrayHelper::getValue($kwargs, 'toField');
        $this->fromField = 'this';

        parent::__construct($kwargs);

    }

    /**
     * Gets the field on the related model that is related to this one.
     *
     * @since 1.1.0
     *
     * @return Field
     *
     * @throws ValueError
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getRelatedField()
    {
        if (is_string($this->relation->getToModel())):
            throw new ValueError(sprintf('Related model %s cannot be resolved', $this->relation->getToModel()));
        endif;

        if (empty($this->toField)):
            return $this->relation->getToModel()->meta->primaryKey;
        endif;

        return $this->relation->getToModel()->meta->getField($this->toField);
    }

    /**
     * @param Model $related
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function contributeToRelatedClass($related)
    {
        if ($this->relation->fieldName == null):
            $this->relation->fieldName = $related->meta->primaryKey->name;
        endif;
    }

    /**
     * {@inheritdoc}
     */
    public function dbType($connection)
    {

        // The database column type of a ForeignKey is the column type
        // of the field to which it points.
        return $this->getRelatedField()->dbType($connection);
    }

}