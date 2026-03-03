<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model\Relation;

use ExpressionEngine\Service\Model\Association\ToOne;
use ExpressionEngine\Service\Model\MetaDataReader;
use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Relation\BelongsTo;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BelongsToTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testCanSaveAcrossAndNoOpMethods()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'author_id',
            'to_key' => 'member_id',
        ));
        $source = new BelongsToSourceModelStub();
        $target = new BelongsToTargetModelStub();
        $target->fill(array('member_id' => 10));

        $this->assertFalse($relation->canSaveAcross());

        $relation->insert($source, array($target));
        $relation->drop($source, array($target));
        $relation->set($source, array($target));

        $this->assertTrue(true);
    }

    public function testCreateAssociationLinkAndMarkClean()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'author_id',
            'to_key' => 'member_id',
        ));

        $source = new BelongsToSourceModelStub();
        $target = new BelongsToTargetModelStub();
        $target->fill(array('member_id' => 55));

        $this->assertInstanceOf(ToOne::class, $relation->createAssociation());

        $relation->fillLinkIds($source, $target);
        $this->assertSame(55, $source->author_id);

        $source->author_id = 99;
        $relation->linkIds($source, $target);
        $this->assertSame(55, $source->author_id);

        $source->author_id = 42;
        $this->assertArrayHasKey('author_id', $source->getDirty());
        $relation->markLinkAsClean($source, $target);
        $this->assertArrayNotHasKey('author_id', $source->getDirty());
    }

    public function testUnlinkIdsCoversWeakAndStrongBranches()
    {
        $strong = $this->makeRelation(array(
            'from_key' => 'author_id',
            'to_key' => 'member_id',
            'weak' => false,
        ));
        $weak = $this->makeRelation(array(
            'from_key' => 'author_id',
            'to_key' => 'member_id',
            'weak' => true,
        ));

        $sourceStrong = new BelongsToSourceModelStub();
        $sourceStrong->author_id = 77;
        $strong->unlinkIds($sourceStrong, new BelongsToTargetModelStub());
        $this->assertNull($sourceStrong->author_id);

        $sourceWeak = new BelongsToSourceModelStub();
        $sourceWeak->author_id = 88;
        $weak->unlinkIds($sourceWeak, new BelongsToTargetModelStub());
        $this->assertNull($sourceWeak->author_id);
    }

    public function testDeriveKeysFallsBackToTargetPrimaryKeyWhenUnset()
    {
        $relation = $this->makeRelation(array(), 'entry_id', 'member_id');
        $this->assertSame(array('member_id', 'member_id'), $relation->getKeys());
    }

    private function makeRelation(array $options, $fromPrimary = 'entry_id', $toPrimary = 'member_id')
    {
        $from = m::mock(MetaDataReader::class);
        $to = m::mock(MetaDataReader::class);

        $from->shouldReceive('getPrimaryKey')->andReturn($fromPrimary);
        $to->shouldReceive('getPrimaryKey')->andReturn($toPrimary);
        $from->shouldReceive('getTableForField')->andReturn('exp_channel_titles');
        $to->shouldReceive('getTableForField')->andReturn('exp_members');
        $from->shouldReceive('getClass')->andReturn('BelongsToFromClass');
        $to->shouldReceive('getClass')->andReturn('BelongsToToClass');
        $from->shouldReceive('getName')->andReturn('ee:entry');
        $to->shouldReceive('getName')->andReturn('ee:member');

        return new BelongsTo($from, $to, 'Author', $options);
    }
}

class BelongsToSourceModelStub extends Model
{
    protected static $_primary_key = 'entry_id';

    protected $entry_id;
    protected $author_id;
}

class BelongsToTargetModelStub extends Model
{
    protected static $_primary_key = 'member_id';

    protected $member_id;
}

// EOF
