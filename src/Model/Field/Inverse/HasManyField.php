<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Eddmash\PowerOrm\Model\Field\Inverse;


use Eddmash\PowerOrm\Model\Field\Field;

class HasManyField extends Field
{

    public $inverse = true;

}