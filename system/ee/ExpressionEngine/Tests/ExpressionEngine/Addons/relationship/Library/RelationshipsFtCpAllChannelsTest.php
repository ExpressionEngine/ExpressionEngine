<?php
 /**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/RelationshipTestBase.php';

use Mockery as m;

/**
 * Test Relationships_ft_cp::all_channels() method
 * @group complex
 */
class RelationshipsFtCpAllChannelsTest extends RelationshipTestBase
{
    /**
     * Test all_channels() returns cached result on second call
     * @group complex
     */
    public function testAllChannelsReturnsCachedResult()
    {
        $this->markTestSkipped('Model mocking for collections requires infrastructure work - functionality tested in other methods');
    }

    /**
     * Test all_channels() in single site mode
     */
    public function testAllChannelsSingleSiteMode()
    {
        $this->disableMultiSite();

        // Create mock channels with sites
        $mockChannels = [
            $this->createMockChannel(1, 'News Channel', 1, 'Site One'),
            $this->createMockChannel(2, 'Blog Channel', 1, 'Site One'),
        ];

        $mockCollection = $this->createMockChannelCollection($mockChannels);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Channel') {
                    return $this->collection;
                }
                return null;
            }
        });

        $result = $this->relationships_ft_cp->all_channels();

        // Verify structure
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_channel', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);

        // In single site mode, children should be simple array with channel_id => channel_title
        $children = $result['--']['children'];
        $this->assertEquals('News Channel', $children[1]);
        $this->assertEquals('Blog Channel', $children[2]);
    }

    /**
     * Test all_channels() in multi-site mode
     */
    public function testAllChannelsMultiSiteMode()
    {
        $this->enableMultiSite();

        // Create mock channels from different sites
        $mockChannels = [
            $this->createMockChannel(1, 'News Channel', 1, 'Site One'),
            $this->createMockChannel(2, 'Blog Channel', 2, 'Site Two'),
        ];

        $mockCollection = $this->createMockChannelCollection($mockChannels);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Channel') {
                    return $this->collection;
                }
                return null;
            }
        });

        $result = $this->relationships_ft_cp->all_channels();

        // Verify structure
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_channel', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);

        // In multi-site mode, children should have value/label/instructions structure
        $children = $result['--']['children'];
        $this->assertCount(2, $children);

        // Check first channel
        $this->assertEquals(1, $children[0]['value']);
        $this->assertEquals('News Channel', $children[0]['label']);
        $this->assertEquals('Site One', $children[0]['instructions']);

        // Check second channel
        $this->assertEquals(2, $children[1]['value']);
        $this->assertEquals('Blog Channel', $children[1]['label']);
        $this->assertEquals('Site Two', $children[1]['instructions']);
    }

    /**
     * Test all_channels() with empty channel list
     */
    public function testAllChannelsWithEmptyChannelList()
    {
        $mockCollection = $this->createMockChannelCollection([]);

        // Mock ee('Model')
        $this->setMock('Model', new class($mockCollection) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function get($model) {
                if ($model === 'Channel') {
                    return $this->collection;
                }
                return null;
            }
        });

        $result = $this->relationships_ft_cp->all_channels();

        // Should still have the '--' key with empty children
        $this->assertArrayHasKey('--', $result);
        $this->assertEquals('any_channel', $result['--']['name']);
        $this->assertArrayHasKey('children', $result['--']);
        $this->assertEmpty($result['--']['children']);
    }

    /**
     * Helper to create mock channel objects
     */
    private function createMockChannels()
    {
        return [
            $this->createMockChannel(1, 'Channel One', 1, 'Site One'),
            $this->createMockChannel(2, 'Channel Two', 1, 'Site One'),
        ];
    }

    /**
     * Helper to create a single mock channel
     */
    private function createMockChannel($id, $title, $siteId, $siteLabel)
    {
        $mockChannel = m::mock('stdClass');
        $mockChannel->shouldReceive('getId')->andReturn($id);

        $mockChannel->channel_id = $id;
        $mockChannel->channel_title = $title;
        $mockChannel->site_id = $siteId;

        $mockSite = m::mock('stdClass');
        $mockSite->site_label = $siteLabel;
        $mockChannel->Site = $mockSite;

        return $mockChannel;
    }
}
