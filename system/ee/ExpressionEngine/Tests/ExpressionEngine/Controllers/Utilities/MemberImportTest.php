<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Utilities;

use PHPUnit\Framework\TestCase;

class MemberImportTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
    }

    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Utilities\MemberImport') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('createcustomfields', 'doimport', 'index', 'memberimportconfirm', 'processxml', 'validatexml'), $controller_methods);
    }
}
