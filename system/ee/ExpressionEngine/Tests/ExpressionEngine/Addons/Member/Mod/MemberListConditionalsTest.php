<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once PATH_ADDONS . 'member/mod.member.php';
require_once PATH_ADDONS . 'member/mod.member_memberlist.php';
require_once APPPATH . 'libraries/Functions.php';

class MemberListConditionalsHarness extends Member_memberlist
{
    /**
     * Create the focused harness without booting the full member module.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Parse member-list conditionals for a focused test.
     *
     * @param string $template
     * @param array $row
     * @param array $fields
     * @return string
     */
    public function parseMemberListConditionalsForTest($template, array $row, array $fields)
    {
        return $this->parseMemberListConditionals($template, $row, $fields);
    }
}

class MemberListConditionalsTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->setMock('functions', new EE_Functions());
        ee()->setMock('TMPL', (object) array(
            'embed_vars' => array(),
            'protect_javascript' => false,
        ));
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Render named custom-field conditionals from their stored values.
     *
     * @dataProvider customFieldProvider
     * @param string $value
     * @param string $expected
     * @return void
     */
    public function testNamedCustomFieldConditionalsRender($value, $expected)
    {
        $template = '{if biography != ""}has profile{if:else}no profile{/if}';
        $row = array('m_field_id_7' => $value);
        $fields = array('biography' => 7);

        $result = (new MemberListConditionalsHarness())->parseMemberListConditionalsForTest(
            $template,
            $row,
            $fields
        );

        $this->assertSame($expected, $result);
    }

    /**
     * Preserve existing member-list variables when a custom field uses the same name.
     *
     * @return void
     */
    public function testExistingRowVariablesTakePrecedenceOverCustomFieldAliases()
    {
        $template = '{if role == "Members"}primary role{/if}';
        $row = array(
            'role' => 'Members',
            'm_field_id_7' => 'Profile text',
        );
        $fields = array('role' => 7);

        $result = (new MemberListConditionalsHarness())->parseMemberListConditionalsForTest(
            $template,
            $row,
            $fields
        );

        $this->assertSame('primary role', $result);
    }

    public function customFieldProvider()
    {
        return array(
            'populated field' => array('Profile text', 'has profile'),
            'empty field' => array('', 'no profile'),
        );
    }
}
