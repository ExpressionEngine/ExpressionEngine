<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateGetUserVarsTest extends EE_TemplateTestBase
{
    public function testGetUserVarsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'getUserVars'));
        $this->assertTrue(is_callable([$this->template, 'getUserVars']));
    }

    public function testGetUserVarsReturnsArray()
    {
        $result = $this->template->getUserVars();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetUserVarsReturnsExpectedVariables()
    {
        $result = $this->template->getUserVars();

        // Check that it contains the default user variables from constructor
        $expectedVars = [
            'member_id', 'group_id', 'group_description', 'group_title', 'primary_role_id',
            'primary_role_description', 'primary_role_name', 'primary_role_short_name',
            'username', 'screen_name', 'avatar_filename', 'avatar_width', 'avatar_height',
            'email', 'ip_address', 'total_entries', 'total_comments', 'private_messages',
            'total_forum_posts', 'total_forum_topics', 'total_forum_replies', 'mfa_enabled'
        ];

        foreach ($expectedVars as $var) {
            $this->assertContains($var, $result, "Expected user variable '$var' not found in result");
        }

        // Should have at least the expected number of variables
        $this->assertGreaterThanOrEqual(count($expectedVars), count($result));
    }
}

