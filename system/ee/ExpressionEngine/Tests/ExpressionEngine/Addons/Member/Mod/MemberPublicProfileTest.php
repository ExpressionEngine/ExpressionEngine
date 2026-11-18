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
     * Create a member fixture with public role metadata.
     *
     * @param array $values
     * @param string|null $role_name
     * @return void
     */
    public function __construct(array $values, $role_name = 'Members')
    {
        $this->values = $values;
        $this->PrimaryRole = new Role(array(
            'name' => $role_name,
            'short_name' => 'members',
            'highlight' => '0066cc',
            'description' => 'Public role description',
            'total_members' => 12,
            'is_locked' => 'y',
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
        $private_fields = array(
            'password', 'salt', 'unique_id', 'crypt_key', 'backup_mfa_code', 'authcode',
            'enable_mfa', 'pending_role_id', 'ignore_list', 'private_messages',
            'last_view_bulletins', 'last_bulletin_date', 'ip_address', 'last_email_date',
            'tracker', 'notepad', 'pmember_id', 'accept_admin_email', 'notify_by_default',
            'notify_of_pm', 'smart_notifications', 'template_size', 'notepad_size',
            'bookmarklets', 'quick_links', 'quick_tabs', 'show_sidebar', 'cp_homepage',
            'cp_homepage_channel', 'cp_homepage_custom', 'dismissed_banner',
        );
        $member = new MemberPublicProfileMemberMock(array_merge(array_fill_keys($private_fields, 'Private value'), array(
            'member_id' => 42,
            'username' => 'sample-member',
            'screen_name' => 'Sample Member',
            'in_authorlist' => 'y',
            'sig_img_filename' => 'signature.png',
            'sig_img_width' => 240,
            'sig_img_height' => 80,
        )));

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
        foreach ($private_fields as $field) {
            $this->assertArrayNotHasKey($field, $profile);
        }
        $this->assertArrayNotHasKey('include_in_memberlist', $profile);
    }

    /**
     * Preserve empty, null, and populated public values without changing their types.
     *
     * @dataProvider restoredValueProvider
     * @param string|null $value
     * @param int|string|null $total_members
     * @param string|null $locked_value
     * @param bool $is_locked
     * @return void
     */
    public function testRestoredProfileValuesPreserveTypes($value, $total_members, $locked_value, $is_locked)
    {
        $values = array_fill_keys(array(
            'display_signatures', 'parse_smileys', 'time_format', 'date_format',
            'week_start', 'include_seconds', 'profile_theme', 'forum_theme',
        ), $value);
        $member = new MemberPublicProfileMemberMock($values, $value);
        $member->PrimaryRole->setRawProperty('description', $value);
        $member->PrimaryRole->setRawProperty('total_members', $total_members);
        $member->PrimaryRole->setRawProperty('is_locked', $locked_value);
        $expected = array_merge($values, array(
            'name' => $value,
            'description' => $value,
            'total_members' => $total_members,
            'is_locked' => $is_locked,
        ));

        $profile = (new MemberPublicProfileHarness())->getPublicProfileDataForTest($member, array());

        foreach ($expected as $key => $expected_value) {
            $this->assertArrayHasKey($key, $profile);
            $this->assertSame($expected_value, $profile[$key]);
        }
        $functions = new EE_Functions();
        $this->assertSame(
            $is_locked ? 'locked' : 'unlocked',
            $functions->prep_conditionals('{if is_locked}locked{if:else}unlocked{/if}', $profile)
        );
        $this->assertSame(
            $value === null || $value === '' ? 'empty' : 'value',
            $functions->prep_conditionals('{if date_format == ""}empty{if:else}value{/if}', $profile)
        );
    }

    /**
     * Provide public values and the role model's boolean conversion.
     *
     * @return array
     */
    public static function restoredValueProvider()
    {
        return array(
            'populated' => array('Public value', 12, 'y', true),
            'empty' => array('', '', '', false),
            'null' => array(null, null, null, false),
            'zero' => array('0', 0, 'n', false),
        );
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
            'm_field_ft_7' => 'xhtml',
            'm_field_dt_7' => null,
            'm_field_extra_7' => 'Fieldtype data',
            'unrelated_column' => 'Private value',
        ));
        $field = $this->makeField(7, 'biography', 'y');

        $profile = (new MemberPublicProfileHarness())->getPublicProfileDataForTest($member, array($field));

        $this->assertSame('Profile text', $profile['m_field_id_7']);
        $this->assertSame('Profile text', $profile['biography']);
        $this->assertSame('xhtml', $profile['m_field_ft_7']);
        $this->assertArrayHasKey('m_field_dt_7', $profile);
        $this->assertNull($profile['m_field_dt_7']);
        $this->assertSame('Fieldtype data', $profile['m_field_extra_7']);
        $this->assertArrayNotHasKey('unrelated_column', $profile);
    }

    /**
     * Preserve the empty-value fallback without inventing absent storage columns.
     *
     * @return void
     */
    public function testMissingCustomFieldDataRemainsEmpty()
    {
        $member = new MemberPublicProfileMemberMock(array('member_id' => 42));
        $field = $this->makeField(7, 'biography', 'y');
        $profile = (new MemberPublicProfileHarness())->getPublicProfileDataForTest($member, array($field));

        $this->assertSame('', $profile['m_field_id_7']);
        $this->assertSame('', $profile['biography']);
        $this->assertArrayNotHasKey('m_field_ft_7', $profile);
    }

    /**
     * Render public variables and conditionals while enforcing field visibility.
     *
     * @dataProvider profileViewerProvider
     * @param int $viewer_id
     * @param bool $super_admin
     * @param bool $legacy
     * @return void
     */
    public function testPublicProfileRendersOnlyFieldsVisibleToTheViewer($viewer_id, $super_admin, $legacy)
    {
        $preferences = array(
            'display_signatures' => 'y',
            'parse_smileys' => 'n',
            'time_format' => '24',
            'date_format' => '%m/%d/%Y',
            'week_start' => 'monday',
            'include_seconds' => 'y',
            'profile_theme' => 'default',
            'forum_theme' => 'default',
        );
        $restored = array_merge($preferences, array(
            'name' => 'Members',
            'description' => 'Public role description',
            'total_members' => 12,
            'is_locked' => 1,
        ));
        $tagdata = '<h1>{screen_name}</h1>'
            . '<img src="{sig_img_filename}" width="{sig_img_width}" height="{sig_img_height}">'
            . '<p>{group_title}/{primary_role_name}</p>'
            . '<p>role:{short_name}|{highlight}|{in_authorlist}</p>'
            . '{if short_name == "members"}<p>member role</p>{/if}'
            . '{if highlight == "0066cc"}<p>highlighted role</p>{/if}'
            . '{if in_authorlist == "y"}<p>listed author</p>{/if}'
            . '<p>public:{biography}|{m_field_id_7}</p>'
            . '<p>public data:{m_field_ft_7}|{m_field_dt_7}|{m_field_extra_7}</p>'
            . '<p>private:{private_notes}|{m_field_id_8}</p>'
            . '<p>private data:{m_field_ft_8}|{m_field_dt_8}|{m_field_extra_8}</p>'
            . '{if member_id > 0}<p>has member</p>{/if}'
            . '{if biography != ""}<p>has biography</p>{/if}'
            . '{if m_field_ft_7 == "xhtml"}<p>formatted field</p>{/if}'
            . '{if m_field_dt_7 == "n"}<p>date setting</p>{/if}'
            . '{custom_profile_fields}{notepad}';
        foreach ($restored as $key => $value) {
            $tagdata .= '<p>' . $key . ':{' . $key . '}</p>'
                . '{if ' . $key . ' == "' . $value . '"}<p>' . $key . ' condition</p>{/if}';
        }
        $member = new MemberPublicProfileMemberMock(array_merge($preferences, array(
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
            'm_field_ft_7' => 'xhtml',
            'm_field_dt_7' => 'n',
            'm_field_extra_7' => 'Public fieldtype data',
            'm_field_id_8' => 'Private profile text',
            'm_field_ft_8' => 'Private formatting',
            'm_field_dt_8' => 'Private date setting',
            'm_field_extra_8' => 'Private fieldtype data',
            'notepad' => 'Private preference',
        )));
        $public_field = $this->makeField(7, 'biography', 'y');
        $private_field = $this->makeField(8, 'private_notes', 'n');
        $private_field->expects($super_admin ? $this->atLeastOnce() : $this->never())->method('parse');
        $private_field->expects($super_admin ? $this->once() : $this->never())->method('getColumnNames');

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
        $this->assertStringContainsString('<p>public data:xhtml|n|Public fieldtype data</p>', $content);
        $this->assertStringContainsString('<p>formatted field</p>', $content);
        $this->assertStringContainsString('<p>date setting</p>', $content);
        $this->assertStringContainsString('<p>has member</p>', $content);
        $this->assertStringContainsString('<p>has biography</p>', $content);
        $this->assertStringContainsString('<p>biography:Public profile text</p>', $content);
        $this->assertStringNotContainsString('Private preference', $content);
        foreach ($restored as $key => $value) {
            $display_value = $key === 'name' ? 'Sample Member' : $value;
            $this->assertStringContainsString('<p>' . $key . ':' . $display_value . '</p>', $content);
            $this->assertStringContainsString('<p>' . $key . ' condition</p>', $content);
        }
        if ($super_admin) {
            $this->assertStringContainsString('<p>private:Private profile text|Private profile text</p>', $content);
            $this->assertStringContainsString('<p>private_notes:Private profile text</p>', $content);
            $this->assertStringContainsString('<p>private data:Private formatting|Private date setting|Private fieldtype data</p>', $content);
        } else {
            $this->assertStringNotContainsString('Private profile text', $content);
            $this->assertStringNotContainsString('Private formatting', $content);
            $this->assertStringNotContainsString('Private date setting', $content);
            $this->assertStringNotContainsString('Private fieldtype data', $content);
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

    /**
     * Create a field fixture with its declared storage columns.
     *
     * @param int $id
     * @param string $name
     * @param string $public
     * @return stdClass
     */
    private function makeField($id, $name, $public)
    {
        $field = $this->getMockBuilder(stdClass::class)->addMethods(['getId', 'getValues', 'getColumnNames', 'parse'])->getMock();
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
        $field->method('getColumnNames')->willReturn(array(
            'm_field_id_' . $id, 'm_field_ft_' . $id, 'm_field_dt_' . $id, 'm_field_extra_' . $id,
        ));
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
