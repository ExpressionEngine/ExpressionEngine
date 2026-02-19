<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model;

use ExpressionEngine\Service\Model\Association\Association;
use ExpressionEngine\Service\Model\Facade;
use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Relation\Relation;
use ExpressionEngine\Service\Validation\Validator;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ModelCoverageTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
        ee()->resetMocks();
        ModelStaticEmitStub::resetState();
        ModelStaticNoHookEmitStub::resetState();
    }

    public function testInitializeForwardsDeleteHooksWhenHookIdExists()
    {
        $model = new ModelInitializeProxy();
        $model->initializePublic();

        $this->assertSame(array('delete', 'delete'), $model->forwardedEvents);
    }

    public function testSetNameAndSetFacadeAndFrontend()
    {
        $facade = m::mock(Facade::class);
        $model = new ModelCoverageProxy();

        $model->setName('ee:Entry');
        $model->setFacade($facade);

        $this->assertSame($facade, $model->getFrontend());
        $this->assertSame($facade, $model->getModelFacade());
    }

    public function testSetNameThrowsOnSecondCall()
    {
        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');

        $this->expectException(\OverflowException::class);
        $this->expectExceptionMessage('Cannot modify name after it has been set.');
        $model->setName('ee:Other');
    }

    public function testSetFacadeThrowsOnSecondCall()
    {
        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');
        $model->setFacade(m::mock(Facade::class));

        $this->expectException(\OverflowException::class);
        $this->expectExceptionMessage('Cannot override existing model facade.');
        $model->setFacade(m::mock(Facade::class));
    }

    public function testValidateLifecycleAndValidatorAliases()
    {
        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');

        $this->assertTrue($model->validate());

        $validator = m::mock(Validator::class);
        $validator->shouldReceive('hasCustomRule')->twice()->with('unique')->andReturn(false, true);
        $validator->shouldReceive('defineRule')->once()->with('unique', array($model, 'validateUnique'));
        $validator->shouldReceive('defineRule')->once()->with('uniqueWithinSiblings', array($model, 'validateUniqueWithinSiblings'));
        $validator->shouldReceive('validate')->once()->with($model)->andReturn('valid-new');
        $validator->shouldReceive('validatePartial')->once()->with($model)->andReturn('valid-existing');

        $model->setValidator($validator);
        $this->assertSame($validator, $model->getValidator());
        $this->assertSame('valid-new', $model->validate());

        $model->setId(100);
        $this->assertSame('valid-existing', $model->validate());
    }

    public function testValidationDataRulesAndUniqueChecks()
    {
        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');
        $model->setProperty('title', 'Alpha');
        $model->setProperty('site_id', 7);

        $this->assertArrayHasKey('title', $model->getValidationData());
        $this->assertSame(array('title' => 'required'), $model->getValidationRules());

        $queryOne = m::mock();
        $queryOne->shouldReceive('filter')->once()->with('title', 'Alpha')->andReturnSelf();
        $queryOne->shouldReceive('filter')->once()->with('site_id', 7)->andReturnSelf();
        $queryOne->shouldReceive('count')->once()->andReturn(1);

        $queryTwo = m::mock();
        $queryTwo->shouldReceive('filter')->once()->with('title', 'Alpha')->andReturnSelf();
        $queryTwo->shouldReceive('filter')->once()->with('site_id', 7)->andReturnSelf();
        $queryTwo->shouldReceive('filter')->once()->with('id', '!=', 22)->andReturnSelf();
        $queryTwo->shouldReceive('count')->once()->andReturn(0);

        $facade = m::mock(Facade::class);
        $facade->shouldReceive('get')->twice()->with('ee:Entry')->andReturn($queryOne, $queryTwo);
        $model->setFacade($facade);

        $this->assertSame('unique', $model->validateUnique('title', 'Alpha', array('site_id')));

        $model->setId(22);
        $this->assertTrue($model->validateUnique('title', 'Alpha', array('site_id')));
    }

    public function testValidateUniqueWithinSiblingsScenarios()
    {
        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');

        $siblingsMany = new ModelSiblingCollectionStub(2);
        $parent = (object) array('siblings' => $siblingsMany);
        $model->setProperty('parent', $parent);

        $this->assertSame(
            'unique',
            $model->validateUniqueWithinSiblings('slug', 'value', array('parent', 'siblings'))
        );

        $siblingsOne = new ModelSiblingCollectionStub(1);
        $parent->siblings = $siblingsOne;
        $this->assertTrue(
            $model->validateUniqueWithinSiblings('slug', 'value', array('parent', 'siblings'))
        );

        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Class 'ExpressionEngine\\Service\\Model\\InvalidArgumentException' not found");
        $model->validateUniqueWithinSiblings('slug', 'value', array('parent'));
    }

    public function testHookShouldTriggerAndHookTriggerClosure()
    {
        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');

        $this->assertFalse($model->hookShouldTriggerPublic('before_channel_entry_delete'));
        $this->assertTrue($model->hookShouldTriggerPublic('after_channel_entry_save'));
        $this->assertFalse($model->hookShouldTriggerPublic('after_channel_entry_save'));
        $this->assertTrue($model->hookShouldTriggerPublic('after_members_save'));

        $extensions = m::mock();
        $extensions->shouldReceive('active_hook')->once()->with('member_hook')->andReturn(true);
        $extensions->shouldReceive('call')->once()->with('member_hook', 'arg');
        ee()->setMock('extensions', $extensions);

        $trigger = $model->getHookTriggerPublic();
        $trigger('member_hook', 'arg');

        $extensions2 = m::mock();
        $extensions2->shouldNotReceive('active_hook');
        ee()->setMock('extensions', $extensions2);
        $model->setInHookPublic(array('already_running_hook'));
        $trigger2 = $model->getHookTriggerPublic();
        $trigger2('already_running_hook');

        $this->assertTrue(true);
    }

    public function testForwardEventToHooksRegistersEventsAndCallsExtensions()
    {
        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');

        $extensions = m::mock();
        $extensions->shouldReceive('active_hook')->once()->with('before_member_save')->andReturn(true);
        $extensions->shouldReceive('call')->once()->with('before_member_save', $model, m::type('array'));
        $extensions->shouldReceive('active_hook')->once()->with('after_member_save')->andReturn(true);
        $extensions->shouldReceive('call')->once()->with('after_member_save', $model, m::type('array'), 'payload');
        ee()->setMock('extensions', $extensions);

        $model->forwardEventToHooksPublic('save');
        $model->emit('beforeSave');
        $model->emit('afterSave', 'payload');

        $this->assertTrue(true);
    }

    public function testForwardEventToHooksSkipsBlockedChannelEntryBeforeDelete()
    {
        $model = new ModelChannelHookProxy();
        $model->setName('ee:Entry');

        $extensions = m::mock();
        $extensions->shouldReceive('active_hook')->twice()->with('after_channel_entry_delete')->andReturn(true);
        $extensions->shouldReceive('call')->twice()->with('after_channel_entry_delete', $model, m::type('array'));
        ee()->setMock('extensions', $extensions);

        $model->forwardEventToHooksPublic('delete');
        $model->emit('beforeDelete');
        $model->emit('afterDelete');

        $this->assertTrue(true);
    }

    public function testEmitEmitStaticAssociationsAndAlias()
    {
        $model = new ModelEmitSubscriberStub();
        $model->setName('ee:Entry');
        $model->emit('custom');
        $this->assertSame(1, $model->customEventCalls);

        $booted = new ModelAssociationBootStub(true);
        $notBooted = new ModelAssociationBootStub(false);
        $model->setAssociationsRaw(array(
            'One' => $notBooted,
            'Two' => $booted,
        ));

        $all = $model->getAllAssociations();
        $this->assertSame($all['One'], $model->getAssociation('One'));
        $this->assertSame(1, $notBooted->bootCalls);

        $relation = m::mock(Relation::class);
        $relation->shouldReceive('getKeys')->andReturn(array('fk', 'id'));
        $association = m::mock(Association::class, array($relation))->makePartial();
        $association->shouldReceive('setFacade')->andReturnNull();
        $association->shouldReceive('getForeignKey')->andReturn('fk');
        $model->setFacade(m::mock(Facade::class));
        $model->setAssociation('Parent:Child', $association);
        $this->assertSame($model, $model->alias('Parent:Child', 'ChildAlias'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot alias relationship.');
        $model->alias('InvalidAlias', 'x');
    }

    public function testEmitStaticAndSerializationAndCacheHelpers()
    {
        $extensions = m::mock();
        $extensions->shouldReceive('active_hook')->once()->with('before_member_bulk_delete')->andReturn(true);
        $extensions->shouldReceive('call')->once()->with('before_member_bulk_delete', array(1, 2));
        $extensions->shouldReceive('active_hook')->once()->with('after_member_bulk_delete')->andReturn(true);
        $extensions->shouldReceive('call')->once()->with('after_member_bulk_delete', array(3, 4));
        ee()->setMock('extensions', $extensions);

        ModelStaticNoHookEmitStub::emitStatic('bulkDelete', array(9));
        ModelStaticEmitStub::emitStatic('beforeBulkDelete', array(1, 2));
        ModelStaticEmitStub::emitStatic('afterBulkDelete', array(3, 4));
        $this->assertSame(1, ModelStaticNoHookEmitStub::$bulkDeleteCalls);
        $this->assertSame(1, ModelStaticEmitStub::$beforeBulkDeleteCalls);
        $this->assertSame(1, ModelStaticEmitStub::$afterBulkDeleteCalls);

        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');
        $model->setProperty('title', 'Serialized');
        $serialize = $model->getSerializeDataPublic();
        $this->assertSame('ee:Entry', $serialize['name']);

        $service = new ModelServiceMakeStub();
        ee()->setMock('Model', $service);

        $other = new ModelCoverageProxy();
        $other->setSerializeData(array(
            'name' => 'ee:Entry',
            'values' => array('id' => 33, 'title' => 'Hydrated'),
        ));
        $this->assertSame('ee:Entry', $other->getName());
        $this->assertSame(33, $other->getId());
        $this->assertSame(1, $service->makeCalls);

        $cacheModel = new ModelCoverageProxy();
        $cacheModel->setName('ee:Entry');
        $cacheModel->saveToCachePublic('k', 'v');
        $this->assertFalse($cacheModel->getFromCachePublic('k'));

        $core = m::mock();
        $core->shouldReceive('set_cache')->once()->with(ModelCoverageProxy::class, 'k2', 'v2');
        $core->shouldReceive('cache')->once()->with(ModelCoverageProxy::class, 'k2', false)->andReturn('v2');
        ee()->setMock('core', $core);
        $cacheModel->saveToCachePublic('k2', 'v2');
        $this->assertSame('v2', $cacheModel->getFromCachePublic('k2'));
    }

    public function testSaveDeleteAndDbExceptionHandling()
    {
        $insertException = new \Exception("Incorrect string value: '\\xF0\\x9F'");
        $qbInsert = m::mock();
        $qbInsert->shouldReceive('insert')->once()->andThrow($insertException);

        $facade = m::mock(Facade::class);
        $facade->shouldReceive('get')->once()->with(m::type(ModelCoverageProxy::class))->andReturn($qbInsert);

        $logger = m::mock();
        $logger->shouldReceive('developer')->once();
        ee()->setMock('logger', $logger);

        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');
        $model->setFacade($facade);

        try {
            $model->save();
            $this->fail('Expected insert exception');
        } catch (\Exception $e) {
            $this->assertSame($insertException, $e);
        }

        $new = new ModelCoverageProxy();
        $new->setName('ee:Entry');
        $this->assertSame($new, $new->delete());

        $deleteQb = m::mock();
        $deleteQb->shouldReceive('filter')->once()->with('id', 44);
        $deleteQb->shouldReceive('delete')->once();
        $facade2 = m::mock(Facade::class);
        $facade2->shouldReceive('get')->once()->with(m::type(ModelCoverageProxy::class))->andReturn($deleteQb);

        $assoc = new ModelAssociationSetStub();
        $existing = new ModelCoverageProxy();
        $existing->setName('ee:Entry');
        $existing->setFacade($facade2);
        $existing->setId(44);
        $existing->setAssociationsRaw(array(
            'A' => $assoc,
        ));
        $existing->delete();

        $this->assertSame(1, $assoc->setNullCalls);
        $this->assertTrue($existing->isNew());
    }

    public function testSaveUpdateCallsAssociationSave()
    {
        $qb = m::mock();
        $qb->shouldReceive('filter')->once()->with('id', 55);
        $qb->shouldReceive('update')->once();

        $facade = m::mock(Facade::class);
        $facade->shouldReceive('get')->once()->with(m::type(ModelCoverageProxy::class))->andReturn($qb);

        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');
        $model->setFacade($facade);
        $model->setId(55);

        $assoc = new ModelAssociationSetStub();
        $model->setAssociationsRaw(array('A' => $assoc));

        $model->save();
        $this->assertSame(1, $assoc->saveCalls);
    }

    public function testSaveUpdateExceptionLoadsLoggerWhenMissing()
    {
        ee()->setMock('logger', null);

        $updateException = new \Exception("Incorrect string value: '\\xF0\\x9F'");
        $qb = m::mock();
        $qb->shouldReceive('filter')->once()->with('id', 77);
        $qb->shouldReceive('update')->once()->andThrow($updateException);

        $facade = m::mock(Facade::class);
        $facade->shouldReceive('get')->once()->with(m::type(ModelCoverageProxy::class))->andReturn($qb);

        $logger = m::mock();
        $logger->shouldReceive('developer')->once();

        $load = m::mock();
        $load->shouldReceive('library')->once()->with('logger')->andReturnUsing(function () use ($logger) {
            ee()->setMock('logger', $logger);
        });
        ee()->setMock('load', $load);

        $model = new ModelCoverageProxy();
        $model->setName('ee:Entry');
        $model->setFacade($facade);
        $model->setId(77);

        try {
            $model->save();
            $this->fail('Expected update exception');
        } catch (\Exception $e) {
            $this->assertSame($updateException, $e);
        }
    }
}

class ModelCoverageProxy extends Model
{
    protected static $_primary_key = 'id';
    protected static $_validation_rules = array('title' => 'required');
    protected static $_events = array('custom');
    protected static $_hook_id = 'member';

    protected $id;
    protected $title;
    protected $site_id;
    protected $parent;

    public function initializePublic()
    {
        $this->initialize();
    }

    public function forwardEventToHooksPublic($event)
    {
        $this->forwardEventToHooks($event);
    }

    public function hookShouldTriggerPublic($hook)
    {
        return $this->hookShouldTrigger($hook);
    }

    public function getHookTriggerPublic()
    {
        return $this->getHookTrigger();
    }

    public function getSerializeDataPublic()
    {
        return $this->getSerializeData();
    }

    public function saveToCachePublic($key, $data)
    {
        $this->saveToCache($key, $data);
    }

    public function getFromCachePublic($key)
    {
        return $this->getFromCache($key);
    }

    public function setAssociationsRaw(array $associations)
    {
        $this->_associations = $associations;
    }

    public function setInHookPublic(array $hooks)
    {
        $this->_in_hook = $hooks;
    }

    public function onCustom()
    {
    }
}

class ModelInitializeProxy extends ModelCoverageProxy
{
    public $forwardedEvents = array();

    protected function forwardEventToHooks($event)
    {
        $this->forwardedEvents[] = $event;
    }
}

class ModelChannelHookProxy extends ModelCoverageProxy
{
    protected static $_hook_id = 'channel_entry';
}

class ModelEmitSubscriberStub extends ModelCoverageProxy
{
    public $customEventCalls = 0;

    public function onCustom()
    {
        $this->customEventCalls++;
    }
}

class ModelStaticEmitStub extends ModelCoverageProxy
{
    protected static $_events = array('bulkDelete', 'beforeBulkDelete', 'afterBulkDelete');
    protected static $_hook_id = 'member';

    public static $bulkDeleteCalls = 0;
    public static $beforeBulkDeleteCalls = 0;
    public static $afterBulkDeleteCalls = 0;

    public static function onBulkDelete($ids)
    {
        self::$bulkDeleteCalls++;
    }

    public static function onBeforeBulkDelete($ids)
    {
        self::$beforeBulkDeleteCalls++;
    }

    public static function onAfterBulkDelete($ids)
    {
        self::$afterBulkDeleteCalls++;
    }

    public static function resetState()
    {
        self::$bulkDeleteCalls = 0;
        self::$beforeBulkDeleteCalls = 0;
        self::$afterBulkDeleteCalls = 0;
    }
}

class ModelStaticNoHookEmitStub extends ModelCoverageProxy
{
    protected static $_events = array('bulkDelete');
    protected static $_hook_id = null;

    public static $bulkDeleteCalls = 0;

    public static function onBulkDelete($ids)
    {
        self::$bulkDeleteCalls++;
    }

    public static function resetState()
    {
        self::$bulkDeleteCalls = 0;
    }
}

class ModelSiblingCollectionStub
{
    private $countValue;

    public function __construct($countValue)
    {
        $this->countValue = $countValue;
    }

    public function filter($key, $value)
    {
        return $this;
    }

    public function count()
    {
        return $this->countValue;
    }
}

class ModelAssociationBootStub
{
    private $booted;
    public $bootCalls = 0;

    public function __construct($booted)
    {
        $this->booted = $booted;
    }

    public function isBooted()
    {
        return $this->booted;
    }

    public function boot($model)
    {
        $this->booted = true;
        $this->bootCalls++;
    }
}

class ModelAssociationSetStub
{
    public $setNullCalls = 0;
    public $saveCalls = 0;
    public $booted = true;

    public function set($value)
    {
        if ($value === null) {
            $this->setNullCalls++;
        }
    }

    public function isBooted()
    {
        return $this->booted;
    }

    public function boot($model)
    {
        $this->booted = true;
    }

    public function idHasChanged()
    {
    }

    public function save()
    {
        $this->saveCalls++;
    }
}

class ModelServiceMakeStub
{
    public $makeCalls = 0;

    public function make($model)
    {
        $this->makeCalls++;

        return $model;
    }
}

// EOF
