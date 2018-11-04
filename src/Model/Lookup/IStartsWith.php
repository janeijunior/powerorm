<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Lookup;

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;

class IStartsWith extends PatternLookup
{
    public static $lookupName = 'istartswith';

    public function processRHS(CompilerInterface $compiler, ConnectionInterface $connection)
    {
        $this->rhs = sprintf('%s%%', $this->rhs);

        return parent::processRHS($compiler, $connection);
    }
}
