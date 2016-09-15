<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

class StringsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $originalString
     * @param $expectedString
     * @dataProvider providerCamelToUnderscore
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function testCamelToUnderscore($originalString, $expectedString) {
        $returnedString = StringHelpercamelToUnderscore($originalString);

        $this->assertEquals(
            $expectedString,
            $returnedString,
            sprintf('StringHelpercamelToUnderscore() returned %s but we expected %s', $returnedString, $expectedString)
        );
    }

    public function providerCamelToUnderscore() {
        return [
            ['userModel', 'user_Model'],
            ['studentsModel', 'students_Model'],
            ['permissionRoleModel', 'permission_Role_Model'],
        ];
    }
}