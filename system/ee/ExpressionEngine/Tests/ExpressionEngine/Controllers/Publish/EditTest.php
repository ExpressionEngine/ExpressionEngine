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
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor('open', [
            'closed' => 'Closed',
            'pending' => 'Pending'
        ]);

        $this->assertTrue($result->hasErrors('status'));
        $this->assertSame(['status_not_available_desc', ['open']], $result->getFailed('status')[0]->getLanguageData());
    }

    public function testAvailableStatusDoesNotAddValidationFailure()
    {
        $result = (new PublishStatusAccessHarness())->validateStatusAccessFor('pending', [
            'closed' => 'Closed',
            'pending' => 'Pending'
        ]);

        $this->assertFalse($result->hasErrors('status'));
    }
}

class PublishStatusAccessHarness extends AbstractPublish
{
    public function __construct()
    {
    }

    public function validateStatusAccessFor($status, array $availableStatuses)
    {
        $entry = new ChannelEntry();
        $entry->setRawProperty('status', $status);

        $result = new ValidationResult();
        $this->validateEntryStatusAccess($entry, new PublishStatusAccessLayoutStub($availableStatuses), $result);

        return $result;
    }
}

class PublishStatusAccessLayoutStub
{
    private $availableStatuses;

    public function __construct(array $availableStatuses)
    {
        $this->availableStatuses = $availableStatuses;
    }

    public function getTabs()
    {
        return [
            new PublishStatusAccessTabStub($this->availableStatuses)
        ];
    }
}

class PublishStatusAccessTabStub
{
    private $availableStatuses;

    public function __construct(array $availableStatuses)
    {
        $this->availableStatuses = $availableStatuses;
    }

    public function getFields()
    {
        return [
            new PublishStatusAccessFieldStub($this->availableStatuses)
        ];
    }
}

class PublishStatusAccessFieldStub
{
    private $availableStatuses;

    public function __construct(array $availableStatuses)
    {
        $this->availableStatuses = $availableStatuses;
    }

    public function getId()
    {
        return 'status';
    }

    public function get($key)
    {
        return $key == 'field_list_items' ? $this->availableStatuses : null;
    }
}
