<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Checks;

/**
 * Class Debug.
 *
 * @since  1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CheckDebug extends CheckMessage
{
    public function __construct($msg, $hint = null, $context = null, $id = null)
    {
        parent::__construct(CheckMessage::DEBUG, $msg, $hint, $context, $id);
    }
}
