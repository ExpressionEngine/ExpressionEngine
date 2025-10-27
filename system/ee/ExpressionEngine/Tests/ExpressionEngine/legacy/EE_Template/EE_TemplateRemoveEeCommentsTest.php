<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateRemoveEeCommentsTest extends EE_TemplateTestBase
{
    public function testRemoveEeCommentsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'remove_ee_comments'));
        $this->assertTrue(is_callable([$this->template, 'remove_ee_comments']));
    }

    public function testRemoveEeCommentsHandlesNullInput()
    {
        $result = $this->template->remove_ee_comments(null);

        $this->assertEquals('', $result);
    }

    public function testRemoveEeCommentsHandlesNoComments()
    {
        $input = 'This is normal template content {variable} with no comments';
        $result = $this->template->remove_ee_comments($input);

        $this->assertEquals($input, $result);
    }

    public function testRemoveEeCommentsRemovesCommentTags()
    {
        $input = 'Before {!-- This is a comment --} After';
        $expected = 'Before  After';
        $result = $this->template->remove_ee_comments($input);

        $this->assertEquals($expected, $result);
    }

    public function testRemoveEeCommentsHandlesMultipleComments()
    {
        $input = '{!-- First comment --} Content {!-- Second comment --} More content';
        $expected = ' Content  More content';
        $result = $this->template->remove_ee_comments($input);

        $this->assertEquals($expected, $result);
    }

    public function testRemoveEeCommentsHandlesNestedComments()
    {
        // The regex uses non-greedy matching, so nested comments are handled by matching
        // from the first {!-- to the first --}, leaving any remaining content
        $input = 'Start {!-- Outer {!-- Inner --} Outer --} End';
        $expected = 'Start  Outer --} End';
        $result = $this->template->remove_ee_comments($input);

        $this->assertEquals($expected, $result);
    }

    public function testRemoveEeCommentsHandlesProFronteditCommentsWhenEnabled()
    {
        // Mock Pro permission as enabled
        $permissionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['canUsePro'])
            ->getMock();
        $permissionMock->method('canUsePro')->willReturn(true);
        ee()->setMock('Permission', $permissionMock);

        $input = 'Before {!-- disable frontedit --} After';
        $expected = 'Before <!-- disable frontedit --> After';
        $result = $this->template->remove_ee_comments($input);

        $this->assertEquals($expected, $result);
    }

    public function testRemoveEeCommentsHandlesProFronteditCommentsWhenDisabled()
    {
        // Mock Pro permission as disabled
        $permissionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['canUsePro'])
            ->getMock();
        $permissionMock->method('canUsePro')->willReturn(false);
        ee()->setMock('Permission', $permissionMock);

        $input = 'Before {!-- disable frontedit --} After';
        $expected = 'Before  After';
        $result = $this->template->remove_ee_comments($input);

        $this->assertEquals($expected, $result);
    }

    public function testRemoveEeCommentsPreservesContentWithoutComments()
    {
        $input = 'This is {variable} content with <!-- HTML comment --> and {another_var}';
        $expected = 'This is {variable} content with <!-- HTML comment --> and {another_var}';
        $result = $this->template->remove_ee_comments($input);

        $this->assertEquals($expected, $result);
    }
}
