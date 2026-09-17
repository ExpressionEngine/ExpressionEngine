<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Model\Role\Role;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Template\Variables\LegacyParser;
use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once PATH_ADDONS . 'member/mod.member.php';
require_once PATH_ADDONS . 'member/mod.member_settings.php';
require_once APPPATH . 'libraries/Functions.php';

class MemberPublicProfileHarness extends Member_settings
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
     * Build public profile data for a focused test.
     *
     * @param object $member
     * @param iterable $member_fields
     * @return array
     */
    public function getPublicProfileDataForTest($member, $member_fields)
    {
        return $this->getPublicProfileData($member, $member_fields);
    }
}

class MemberPublicProfileMemberMock
{
    public $PrimaryRole;

    private $values;

    /**
     * @param array $values
     * @param string $role_name
     */
    public function __construct(array $values, $role_name = 'Members')
    {
        $this->values = $values;
        $this->PrimaryRole = new Role(array(
            'name' => $role_name,
            'short_name' => 'members',
            'highlight' => '0066cc',
            'description' => 'Internal role description',
        ));
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}

class MemberPublicProfileTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        if (! defined('QUERY_MARKER')) {
            define('QUERY_MARKER', '?');
        }
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Keep declared public values and omit unrelated model state.
     *
     * @return void
     */
    public function testProfileDataContainsOnlyDeclaredMemberValues()
    {
        $member = new MemberPublicProfileMemberMock(array(
            'member_id' => 42,
            'username' => 'sample-member',
            'screen_name' => 'Sample Member',
            'in_authorlist' => 'y',
            'sig_img_filename' => 'signature.png',
            'sig_img_width' => 240,
            'sig_img_height' => 80,
            'notepad' => 'Private preference',
        ));

        $profile = (new MemberPublicProfileHarness())->getPublicProfileDataForTest($member, array());

        $this->assertSame(42, $profile['member_id']);
        $this->assertSame('sample-member', $profile['username']);
        $this->assertSame('Sample Member', $profile['screen_name']);
        $this->assertSame('signature.png', $profile['sig_img_filename']);
        $this->assertSame(240, $profile['sig_img_width']);
        $this->assertSame(80, $profile['sig_img_height']);
        $this->assertSame('Members', $profile['group_title']);
        $this->assertSame('Members', $profile['primary_role_name']);
        $this->assertSame('members', $profile['short_name']);
        $this->assertSame('0066cc', $profile['highlight']);
        $this->assertSame('y', $profile['in_authorlist']);
        $this->assertArrayNotHasKey('description', $profile);
        $this->assertArrayNotHasKey('notepad', $profile);
    }

    /** @dataProvider defaultHighlightProvider */
    public function testProfileDataPreservesDefaultRoleHighlights($highlight)
    {
        $member = new MemberPublicProfileMemberMock(array('member_id' => 42));
        $member->PrimaryRole->setRawProperty('highlight', $highlight);
        $profile = (new MemberPublicProfileHarness())->getPublicProfileDataForTest($member, array());

        $this->assertSame('5D63F1', $profile['highlight']);
    }

    public function defaultHighlightProvider()
    {
        return array([null], ['']);
    }

    /**
     * Preserve custom field storage keys and template names.
     *
     * @return void
     */
    public function testProfileDataContainsCustomFieldKeysAndNames()
    {
        $member = new MemberPublicProfileMemberMock(array(
            'member_id' => 42,
            'm_field_id_7' => 'Profile text',
        ));
        $field = (object) array(
            'm_field_id' => 7,
            'm_field_name' => 'biography',
        );

        $profile = (new MemberPublicProfileHarness())->getPublicProfileDataForTest($member, array($field));

        $this->assertSame('Profile text', $profile['m_field_id_7']);
        $this->assertSame('Profile text', $profile['biography']);
    }

    /** @dataProvider profileViewerProvider */
    public function testPublicProfileRendersOnlyFieldsVisibleToTheViewer($viewer_id, $super_admin, $legacy)
    {
        $tagdata = '<h1>{screen_name}</h1>'
            . '<img src="{sig_img_filename}" width="{sig_img_width}" height="{sig_img_height}">'
            . '<p>{group_title}/{primary_role_name}</p>'
            . '<p>role:{short_name}|{highlight}|{in_authorlist}</p>'
            . '{if short_name == "members"}<p>member role</p>{/if}'
            . '{if highlight == "0066cc"}<p>highlighted role</p>{/if}'
            . '{if in_authorlist == "y"}<p>listed author</p>{/if}'
            . '<p>public:{biography}|{m_field_id_7}</p>'
            . '<p>private:{private_notes}|{m_field_id_8}</p>'
            . '{if member_id > 0}<p>has member</p>{/if}'
            . '{if biography != ""}<p>has biography</p>{/if}'
            . '{custom_profile_fields}{notepad}{description}';
        $member = new MemberPublicProfileMemberMock(array(
            'member_id' => 42,
            'username' => 'sample-member',
            'screen_name' => 'Sample Member',
            'in_authorlist' => 'y',
            'avatar_filename' => '',
            'timezone' => 'UTC',
            'sig_img_filename' => 'signature.png',
            'sig_img_width' => 240,
            'sig_img_height' => 80,
            'm_field_id_7' => 'Public profile text',
            'm_field_id_8' => 'Private profile text',
            'notepad' => 'Private preference',
        ));
        $public_field = $this->makeField(7, 'biography', 'y');
        $private_field = $this->makeField(8, 'private_notes', 'n');
        $private_field->expects($super_admin ? $this->atLeastOnce() : $this->never())->method('parse');

        $profile = $this->prepareRenderer(
            $member,
            new Collection(array($public_field, $private_field)),
            $tagdata,
            $viewer_id,
            $super_admin,
            $legacy
        );
        $content = $profile->public_profile();

        $this->assertStringContainsString('<h1>Sample Member</h1>', $content);
        $this->assertStringContainsString('<img src="signature.png" width="240" height="80">', $content);
        $this->assertStringContainsString('<p>Members/Members</p>', $content);
        $this->assertStringContainsString('<p>role:members|0066cc|y</p>', $content);
        $this->assertStringContainsString('<p>member role</p>', $content);
        $this->assertStringContainsString('<p>highlighted role</p>', $content);
        $this->assertStringContainsString('<p>listed author</p>', $content);
        $this->assertStringContainsString('<p>public:Public profile text|Public profile text</p>', $content);
        $this->assertStringContainsString('<p>has member</p>', $content);
        $this->assertStringContainsString('<p>has biography</p>', $content);
        $this->assertStringContainsString('<p>biography:Public profile text</p>', $content);
        $this->assertStringNotContainsString('Private preference', $content);
        $this->assertStringNotContainsString('Internal role description', $content);
        if ($super_admin) {
            $this->assertStringContainsString('<p>private:Private profile text|Private profile text</p>', $content);
            $this->assertStringContainsString('<p>private_notes:Private profile text</p>', $content);
        } else {
            $this->assertStringNotContainsString('Private profile text', $content);
        }
    }

    public function profileViewerProvider()
    {
        return array(
            'guest tag pair' => array(0, false, false),
            'member tag pair' => array(17, false, false),
            'super admin tag pair' => array(1, true, false),
            'guest legacy template' => array(0, false, true),
            'member legacy template' => array(17, false, true),
            'super admin legacy template' => array(1, true, true),
        );
    }

    private function makeField($id, $name, $public)
    {
        $field = $this->getMockBuilder(stdClass::class)->addMethods(['getId', 'getValues', 'parse'])->getMock();
        $values = array(
            'm_field_id' => $id,
            'm_field_name' => $name,
            'm_field_label' => $name,
            'm_field_description' => '',
            'm_field_public' => $public,
            'm_field_fmt' => 'none',
            'm_field_type' => 'text',
        );
        foreach ($values as $key => $value) {
            $field->$key = $value;
        }
        $field->method('getId')->willReturn($id);
        $field->method('getValues')->willReturn($values);
        // Fieldtype formatting is outside this test; use plain text replacement after visibility is checked.
        $field->method('parse')->willReturnCallback(function ($data, $id, $type, $variable, $tagdata) use ($name) {
            return str_replace('{' . $name . '}', $data, $tagdata);
        });

        return $field;
    }

    private function prepareRenderer($member, $fields, $tagdata, $viewer_id, $super_admin, $legacy)
    {
        $permission = $this->mockService('Permission', ['can', 'isSuperAdmin']);
        $permission->method('can')->with('view_profiles')->willReturn(true);
        $permission->method('isSuperAdmin')->willReturn($super_admin);
        ee()->setMock('session', (object) ['userdata' => ['member_id' => $viewer_id, 'ignore_list' => []]]);

        $query = $this->getMockBuilder(stdClass::class)->addMethods(['with', 'filter', 'first', 'all'])->getMock();
        $query->method('with')->willReturnSelf();
        $query->method('filter')->willReturnSelf();
        $query->method('first')->willReturn($member);
        $query->method('all')->willReturn($fields);
        $model = $this->mockService('Model', ['get']);
        $model->method('get')->willReturn($query);

        $template = $this->mockService('TMPL', ['fetch_param']);
        $template->tagdata = $legacy ? '' : $tagdata;
        $template->protect_javascript = false;
        $template->method('fetch_param')->with('member_id')->willReturn($legacy ? false : '42');
        $config = $this->mockService('Config', ['getFile', 'getBoolean']);
        $config->method('getFile')->willReturnSelf();
        $config->method('getBoolean')->with('legacy_member_templates')->willReturn($legacy);

        // Keep the real variable and conditional parsers while isolating URL/form generation and fieldtypes.
        ee()->setMock('Variables/Parser', new LegacyParser());
        $real_functions = new EE_Functions();
        $functions = $this->getMockBuilder(EE_Functions::class)
            ->onlyMethods(['fetch_site_index', 'fetch_action_id', 'form_declaration', 'encode_ee_tags', 'prep_conditionals'])
            ->getMock();
        $functions->method('fetch_site_index')->willReturn('/');
        $functions->method('fetch_action_id')->willReturn('1');
        $functions->method('form_declaration')->willReturn('');
        $functions->method('encode_ee_tags')->willReturnArgument(0);
        $functions->expects($this->atLeast(2))
            ->method('prep_conditionals')
            ->willReturnCallback(array($real_functions, 'prep_conditionals'));
        ee()->setMock('functions', $functions);
        $this->mockService('legacy_api', ['instantiate']);

        $profile = $this->getMockBuilder(MemberPublicProfileHarness::class)
            ->onlyMethods(['_load_element', '_member_path', 'list_js'])->getMock();
        $profile->cur_id = $legacy ? '42' : '';
        $profile->method('_load_element')->willReturnMap(array(
            ['public_profile', $tagdata],
            ['public_custom_profile_fields', '{if field_label != ""}<p>{field_name}:{field_data}</p>{/if}'],
        ));
        $profile->method('_member_path')->willReturn('/member/');
        $profile->method('list_js')->willReturn('');

        return $profile;
    }

    private function mockService($name, array $methods)
    {
        $mock = $this->getMockBuilder(stdClass::class)->addMethods($methods)->getMock();
        ee()->setMock($name, $mock);

        return $mock;
    }
}
