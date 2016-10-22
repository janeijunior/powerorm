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

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Delete;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\OneToOneRel;

/**
 * A OneToOneField is essentially the same as a ForeignKey, with the exception that
 * always carries a "unique" constraint with it and the reverse relation always returns the object pointed
 * to (since there will only ever be one), rather than returning a list.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class OneToOneField extends ForeignKey
{
    /**
     * @var bool
     */
    public $manyToOne = false;
    public $oneToOne = true;

    public function __construct($kwargs)
    {
        $kwargs['unique'] = true;

        if (!isset($kwargs['rel']) || (isset($kwargs['rel']) && $kwargs['rel'] == null)):
            $kwargs['rel'] = OneToOneRel::createObject([
                'fromField' => $this,
                'to' => ArrayHelper::getValue($kwargs, 'to'),
                'toField' => ArrayHelper::getValue($kwargs, 'toField'),
                'parentLink' => ArrayHelper::getValue($kwargs, 'parentLink'),
                'onDelete' => ArrayHelper::getValue($kwargs, 'onDelete', Delete::CASCADE),
            ]);
        endif;

        parent::__construct($kwargs);
    }

    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        if ($this->relation->parentLink):
            return;
        endif;

        return parent::formField($kwargs);
    }

}