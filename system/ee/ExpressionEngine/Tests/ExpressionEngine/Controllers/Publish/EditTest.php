<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Publish;

require_once(APPPATH . 'core/Controller.php');

use ExpressionEngine\Controller\Publish\AbstractPublish;
use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use PHPUnit\Framework\TestCase;

class EditTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
    }

    protected function setUp(): void
    {
        parent::setUp();
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
        parent::tearDown();
    }

    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Publish\Edit') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('entry', 'index'), $controller_methods);
    }

    public function testUnavailableStatusAddsValidationFailure()
    {
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor(
            'open',
            ['open', 'closed', 'pending'],
            ['closed', 'pending']
        );

        $this->assertTrue($result->hasErrors('status'));
        $this->assertSame(['status_not_available_desc', ['open']], $result->getFailed('status')[0]->getLanguageData());
    }

    public function testAvailableStatusDoesNotAddValidationFailure()
    {
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor(
            'pending',
            ['open', 'closed', 'pending'],
            ['closed', 'pending']
        );

        $this->assertFalse($result->hasErrors('status'));
    }

    /**
     * Verify array status input becomes a validation failure.
     *
     * @return void
     */
    public function testArrayStatusAddsValidationFailureWithoutFatal()
    {
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor(
            ['open'],
            ['open', 'closed'],
            ['open', 'closed']
        );

        $this->assertTrue($result->hasErrors('status'));
        $this->assertSame(['status_not_available_desc', ['invalid']], $result->getFailed('status')[0]->getLanguageData());
    }

    public function testUnavailableStatusEscapesValidationFailureParameter()
    {
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor(
            '<script>alert("x")</script>',
            ['closed', '<script>alert("x")</script>'],
            ['closed']
        );

        $this->assertSame(
            ['status_not_available_desc', ['&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;']],
            $result->getFailed('status')[0]->getLanguageData()
        );
    }

    public function testChannelStatusListDoesNotGrantUnassignedStatusAccess()
    {
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor(
            'open',
            ['open', 'closed'],
            ['closed']
        );

        $this->assertTrue($result->hasErrors('status'));
        $this->assertSame(['status_not_available_desc', ['open']], $result->getFailed('status')[0]->getLanguageData());
    }

    public function testSuperAdminCanUseAnyChannelStatus()
    {
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor(
            'open',
            ['open', 'closed'],
            [],
            true
        );

        $this->assertFalse($result->hasErrors('status'));
    }

    public function testAssignedStatusOutsideChannelDoesNotPass()
    {
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor(
            'open',
            ['closed'],
            ['open']
        );

        $this->assertTrue($result->hasErrors('status'));
        $this->assertSame(['status_not_available_desc', ['open']], $result->getFailed('status')[0]->getLanguageData());
    }
}

class PublishStatusAccessHarness extends AbstractPublish
{
    public function __construct()
    {
    }

    public function validateStatusAccessFor($status, array $channelStatuses, array $assignedStatuses, $isSuperAdmin = false)
    {
        ee()->setMock('session', new PublishStatusAccessSessionStub(new PublishStatusAccessMemberStub($assignedStatuses)));
        ee()->setMock('Permission', new PublishStatusAccessPermissionStub($isSuperAdmin));

        $entry = new PublishStatusAccessEntryStub($status, $channelStatuses);

        $result = new ValidationResult();
        $this->validateEntryStatusAccess($entry, $result);

        return $result;
    }
}

class PublishStatusAccessEntryStub extends ChannelEntry
{
    protected $status;
    private $channel;

    public function __construct($status, array $channelStatuses)
    {
        $this->status = $status;
        $this->channel = new PublishStatusAccessChannelStub($channelStatuses);
    }

    public function __get($key)
    {
        if ($key === 'status') {
            return $this->status;
        }

        if ($key === 'Channel') {
            return $this->channel;
        }

        return parent::__get($key);
    }
}

class PublishStatusAccessChannelStub
{
    public $Statuses;

    public function __construct(array $statuses)
    {
        $this->Statuses = array_map(function ($status) {
            return new PublishStatusAccessStatusStub($status);
        }, $statuses);
    }
}

class PublishStatusAccessStatusStub
{
    public $status;

    public function __construct($status)
    {
        $this->status = $status;
    }
}

class PublishStatusAccessMemberStub
{
    private $assignedStatuses;

    public function __construct(array $assignedStatuses)
    {
        $this->assignedStatuses = array_map(function ($status) {
            return new PublishStatusAccessStatusStub($status);
        }, $assignedStatuses);
    }

    public function getAssignedStatuses()
    {
        return $this->assignedStatuses;
    }
}

class PublishStatusAccessSessionStub
{
    private $member;

    public function __construct($member)
    {
        $this->member = $member;
    }

    public function getMember()
    {
        return $this->member;
    }
}

class PublishStatusAccessPermissionStub
{
    private $isSuperAdmin;

    public function __construct($isSuperAdmin = false)
    {
        $this->isSuperAdmin = $isSuperAdmin;
    }

    public function isSuperAdmin()
    {
        return $this->isSuperAdmin;
    }
}
