<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Model\Channel;

use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Service\Model\Model as BaseModel;
use PHPUnit\Framework\TestCase;

class ChannelEntryLastEditorTest extends TestCase
{
    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testShouldUpdateLastEditorReturnsFalseWhenChangedIsNotArray()
    {
        $entry = $this->makeEntryWithFacadeStub();

        $result = $this->invokePrivateMethod($entry, 'shouldUpdateLastEditor', ['not-an-array']);

        $this->assertFalse($result);
    }

    public function testShouldUpdateLastEditorReturnsFalseForCommentOnlyChanges()
    {
        $entry = $this->makeEntryWithFacadeStub();

        $result = $this->invokePrivateMethod($entry, 'shouldUpdateLastEditor', [[
            'comment_total' => 2,
            'recent_comment_date' => 1700000000,
            'edit_member_id' => 1,
        ]]);

        $this->assertFalse($result);
    }

    public function testShouldUpdateLastEditorReturnsTrueWhenRealEntryFieldsChange()
    {
        $entry = $this->makeEntryWithFacadeStub();

        $result = $this->invokePrivateMethod($entry, 'shouldUpdateLastEditor', [[
            'title' => 'Updated title',
            'comment_total' => 2,
        ]]);

        $this->assertTrue($result);
    }

    public function testGetActiveMemberIdReturnsZeroWhenSessionIsMissing()
    {
        $entry = $this->makeEntryWithFacadeStub();
        ee()->setMock('session', null);

        $result = $this->invokePrivateMethod($entry, 'getActiveMemberId');

        $this->assertSame(0, $result);
    }

    public function testGetActiveMemberIdReturnsIntegerFromSession()
    {
        $entry = $this->makeEntryWithFacadeStub();

        $session = new \eeSingletonSessionMock();
        $session->setUserdata('member_id', '77');
        ee()->setMock('session', $session);

        $result = $this->invokePrivateMethod($entry, 'getActiveMemberId');

        $this->assertSame(77, $result);
    }

    public function testOnBeforeUpdateSetsEditMemberIdForMeaningfulChanges()
    {
        $entry = $this->makeEntryWithFacadeStub();
        $entry->setProperty('status', 'open');

        $session = new \eeSingletonSessionMock();
        $session->setUserdata('member_id', 55);
        ee()->setMock('session', $session);

        $entry->onBeforeUpdate(['title' => 'New title']);

        $this->assertSame(55, $entry->getProperty('edit_member_id'));
    }

    public function testOnBeforeUpdateSkipsEditMemberIdForCommentOnlyChanges()
    {
        $entry = $this->makeEntryWithFacadeStub();
        $entry->setProperty('status', 'open');
        $entry->setProperty('edit_member_id', 12);

        $session = new \eeSingletonSessionMock();
        $session->setUserdata('member_id', 99);
        ee()->setMock('session', $session);

        $entry->onBeforeUpdate([
            'comment_total' => 3,
            'recent_comment_date' => 1700000000,
        ]);

        $this->assertSame(12, $entry->getProperty('edit_member_id'));
    }

    public function testOnBeforeUpdateUsesLoadedStatusWhenStatusFieldWasNotChanged()
    {
        $entry = $this->makeEntryWithFacadeStub();
        $entry->setProperty('status', 'draft');
        $entry->Status = new class {
            public $status = 'review';
        };

        $entry->onBeforeUpdate(['comment_total' => 2]);

        $this->assertSame('review', $entry->getProperty('status'));
    }

    public function testOnBeforeInsertUsesActiveMemberAsLastEditor()
    {
        $entry = $this->makeEntryWithFacadeStub();
        $entry->setProperty('status', 'open');
        $entry->setProperty('author_id', 3);

        $session = new \eeSingletonSessionMock();
        $session->setUserdata('member_id', 88);
        ee()->setMock('session', $session);

        $entry->onBeforeInsert();

        $this->assertSame(88, $entry->getProperty('edit_member_id'));
    }

    public function testOnBeforeInsertFallsBackToAuthorWhenNoActiveMember()
    {
        $entry = $this->makeEntryWithFacadeStub();
        $entry->setProperty('status', 'open');
        $entry->setProperty('author_id', 14);

        $session = new \eeSingletonSessionMock();
        $session->setUserdata('member_id', 0);
        ee()->setMock('session', $session);

        $entry->onBeforeInsert();

        $this->assertSame(14, $entry->getProperty('edit_member_id'));
    }

    private function makeEntryWithFacadeStub()
    {
        $entry = new ChannelEntryLastEditorTestDouble();

        $facadeProperty = new \ReflectionProperty(BaseModel::class, '_facade');
        \TestReflectionHelper::makePropertyAccessible($facadeProperty);
        $facadeProperty->setValue($entry, new ChannelEntryFacadeStub());

        return $entry;
    }

    private function invokePrivateMethod($object, $method, array $arguments = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        \TestReflectionHelper::makeMethodAccessible($reflection);

        return $reflection->invokeArgs($object, $arguments);
    }
}

class ChannelEntryFacadeStub
{
    public function get($modelName)
    {
        return new ChannelEntryStatusQueryStub();
    }
}

class ChannelEntryStatusQueryStub
{
    public function filter($field, $value)
    {
        return $this;
    }

    public function first()
    {
        return null;
    }
}

class ChannelEntryLastEditorTestDouble extends ChannelEntry
{
    public function markAsDirty($name = null)
    {
        return $this;
    }
}

// EOF
