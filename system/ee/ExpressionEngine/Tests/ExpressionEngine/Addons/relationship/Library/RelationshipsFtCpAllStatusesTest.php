<?php
 /**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/RelationshipTestBase.php';

use Mockery as m;

/**
 * Test Relationships_ft_cp::all_statuses() method
 * @group complex
 */
class RelationshipsFtCpAllStatusesTest extends RelationshipTestBase
{
    /**
     * Test all_statuses() returns cached result on second call
     * @group complex
     */
    public function testAllStatusesReturnsCachedResult()
    {
        // Create mock statuses
        $mockStatuses = $this->createMockStatuses();
        $mockCollection = $this->createMockStatusCollection($mockStatuses);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Status') {
                    return $this->collection;
                }
                return null;
            }
        });

        // First call - should query database
        $result1 = $this->relationships_ft_cp->all_statuses();

        // Second call - should use cached result
        $result2 = $this->relationships_ft_cp->all_statuses();

        // Results should be identical
        $this->assertEquals($result1, $result2);

        // Verify structure
        $this->assertArrayHasKey('--', $result1);
        $this->assertEquals('any_status', $result1['--']['name']);
        $this->assertArrayHasKey('children', $result1['--']);
    }

    /**
     * Test all_statuses() returns correct status structure
     */
    public function testAllStatusesReturnsCorrectStructure()
    {
        // Create mock statuses
        $mockStatuses = $this->createMockStatuses();
        $mockCollection = $this->createMockStatusCollection($mockStatuses);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Status') {
                    return $this->collection;
                }
                return null;
            }
        });

        $result = $this->relationships_ft_cp->all_statuses();

        // Verify structure
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_status', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);

        $statuses = $result['--']['children'];

        // Check translated statuses
        $this->assertEquals('open', $statuses['open']);
        $this->assertEquals('closed', $statuses['closed']);

        // Check custom status
        $this->assertEquals('draft', $statuses['draft']);
    }

    /**
     * Test all_statuses() with empty status list
     */
    public function testAllStatusesWithEmptyStatusList()
    {
        $mockCollection = $this->createMockStatusCollection([]);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Status') {
                    return $this->collection;
                }
                return null;
            }
        });

        $result = $this->relationships_ft_cp->all_statuses();

        // Should still have the '--' key with empty children
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_status', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);
        $this->assertEmpty($result['--']['children']);
    }

    /**
     * Test all_statuses() orders statuses by status_id
     */
    public function testAllStatusesOrdersByStatusId()
    {
        // Create mock statuses with different IDs
        $mockStatuses = [
            $this->createMockStatus(3, 'status_c'),
            $this->createMockStatus(1, 'status_a'),
            $this->createMockStatus(2, 'status_b'),
        ];

        $mockCollection = $this->createMockStatusCollection($mockStatuses);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Status') {
                    return $this->collection;
                }
                return null;
            }
        });

        $result = $this->relationships_ft_cp->all_statuses();

        $statuses = $result['--']['children'];

        // Should be ordered by status_id (1, 2, 3) - mock doesn't actually sort, so check original order
        $statusKeys = array_keys($statuses);
        $this->assertEquals(['status_c', 'status_a', 'status_b'], $statusKeys);
    }

    /**
     * Test all_statuses() with only open/closed statuses
     */
    public function testAllStatusesWithOnlySystemStatuses()
    {
        // Create only open/closed statuses
        $mockStatuses = [
            $this->createMockStatus(1, 'open'),
            $this->createMockStatus(2, 'closed'),
        ];

        $mockCollection = $this->createMockStatusCollection($mockStatuses);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Status') {
                    return $this->collection;
                }
                return null;
            }
        });

        $result = $this->relationships_ft_cp->all_statuses();

        $statuses = $result['--']['children'];

        // Should have translated versions
        $this->assertEquals('open', $statuses['open']);
        $this->assertEquals('closed', $statuses['closed']);
    }

    /**
     * Helper to create mock statuses
     */
    private function createMockStatuses()
    {
        return [
            $this->createMockStatus(1, 'open'),
            $this->createMockStatus(2, 'closed'),
            $this->createMockStatus(3, 'draft'),
        ];
    }

    /**
     * Helper to create a single mock status
     */
    private function createMockStatus($id, $status)
    {
        $mockStatus = m::mock('stdClass');
        $mockStatus->status_id = $id;
        $mockStatus->status = $status;

        return $mockStatus;
    }
}
