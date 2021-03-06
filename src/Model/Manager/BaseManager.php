<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Manager;

use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * Class BaseManager.
 *
 * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
 */
class BaseManager extends BaseObject implements ManagerInterface
{
    /**
     * @var Model
     */
    public $model;

    /**
     * {@inheritdoc}
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * We don't proxy this method through the `QuerySet` like we do for the rest of the `QuerySet` methods.
     *
     * Reason is, invoking the `$querset->all()` triggers copying of the initial queryset into a new one, which
     * results in the loss of all the cached `prefetch_related` lookups.
     *
     * Done by managers that implement the RelationDescriptor
     *
     * @return Queryset
     *
     * @author Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
     */
    public function all()
    {
        return $this->getQueryset();
    }

    /**
     * @return Queryset
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getQueryset()
    {
        return Queryset::createObject(null, $this->model);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name)) {
            return call_user_func_array([$this->getQueryset(), $name], $arguments);
        }
    }

    public function __toString()
    {
        return sprintf('%s object', get_class($this));
    }

    public function getIterator()
    {
        throw new TypeError("'Manager' object is not iterable");
    }
}
