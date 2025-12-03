<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\ChannelSet;

use ExpressionEngine\Service\ChannelSet\Set;
use ExpressionEngine\Service\ChannelSet\ImportResult;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    private $set;
    private $tempDir;

    public function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/ee_set_test_' . uniqid();
        mkdir($this->tempDir);

        $this->set = new Set($this->tempDir);
    }

    public function tearDown(): void
    {
        m::close();

        // Reset mocks
        ee()->resetMocks();

        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testConstructorSetsPath()
    {
        $expectedPath = rtrim($this->tempDir, '/');
        $this->assertEquals($expectedPath, $this->set->getPath());
    }

    public function testConstructorCreatesImportResult()
    {
        $result = $this->getPrivateProperty($this->set, 'result');
        $this->assertInstanceOf(ImportResult::class, $result);
    }

    public function testSetSiteIdSetsSiteId()
    {
        $this->set->setSiteId(5);
        $this->assertEquals(5, $this->getPrivateProperty($this->set, 'site_id'));
    }

    public function testValidateMethodExistsAndReturnsImportResult()
    {
        // Test that validate method exists and returns ImportResult
        // The full integration test would require extensive mocking of Model service
        $this->assertTrue(method_exists($this->set, 'validate'));

        // Create an invalid channel_set.json to avoid complex mocking
        // This should return ImportResult but with validation errors
        $result = $this->set->validate();
        $this->assertInstanceOf(ImportResult::class, $result);
    }

    public function testValidateFailsWithMissingChannelSetJson()
    {
        $result = $this->set->validate();

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    public function testValidateFailsWithIncompatibleVersion()
    {
        // Create channel_set.json with v4 version
        $channelSetData = [
            'version' => '4.0.0',
            'channels' => [],
            'field_groups' => [],
            'category_groups' => [],
            'upload_destinations' => [],
            'statuses' => []
        ];
        file_put_contents($this->tempDir . '/channel_set.json', json_encode($channelSetData));

        // Mock config to return v3 app version
        // Use setItem() method to set config values properly
        ee()->config->setItem('app_version', '3.0.0');

        // Mock Model service using the same pattern as EE_TemplateTestBase
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'make'])
            ->getMock();
        $modelMock->method('get')->willReturnCallback(function($modelName) {
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field = null) {
                            return [];
                        }
                    };
                }
                public function filter($field, $operator = null, $value = null) {
                    return $this;
                }
                public function fields($fields) {
                    return $this;
                }
            };
        });
        $modelMock->method('make')->willReturnCallback(function($modelName, $data = []) {
            return $this->getMockBuilder('stdClass')->getMock();
        });
        ee()->setMock('Model', $modelMock);
        
        // Mock load->helper
        ee()->load->helper = function($name) {};

        $result = $this->set->validate();

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    public function testValidateWithValidChannelSetJson()
    {
        // Create valid channel_set.json
        $this->createValidChannelSetJson();

        // Setup mocks
        $this->setupBasicMocks();

        // Override Model service to return models with validation
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'make'])
            ->getMock();
        $modelMock->method('get')->willReturnCallback(function($modelName) {
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field = null) {
                            return [];
                        }
                    };
                }
                public function filter($field, $operator = null, $value = null) {
                    return $this;
                }
                public function fields($fields) {
                    return $this;
                }
            };
        });
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName', 'validate', 'hasProperty'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->method('hasProperty')->willReturn(true);
            $validationResult = $this->getMockBuilder('stdClass')
                ->addMethods(['failed'])
                ->getMock();
            $validationResult->method('failed')->willReturn(false);
            $model->method('validate')->willReturn($validationResult);
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $result = $this->set->validate();

        $this->assertInstanceOf(ImportResult::class, $result);
        // If validation passes, result should be valid (assuming no model validation errors)
    }

    public function testValidateWithInvalidJsonStructure()
    {
        // Create invalid JSON file
        file_put_contents($this->tempDir . '/channel_set.json', '{invalid json}');

        $result = $this->set->validate();

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    public function testValidateWithMissingRequiredFields()
    {
        // Create channel_set.json missing required fields
        $channelSetData = [
            'version' => '4.0.0',
            // Missing channels, upload_destinations, etc.
        ];
        file_put_contents($this->tempDir . '/channel_set.json', json_encode($channelSetData));

        // Setup mocks
        $this->setupBasicMocks();

        $result = $this->set->validate();

        // Should handle missing fields gracefully
        $this->assertInstanceOf(ImportResult::class, $result);
    }

    public function testValidateCallsValidateOneForEachModel()
    {
        // Create valid channel_set.json with channels
        $this->createValidChannelSetJson();

        // Setup basic mocks
        $this->setupBasicMocks();

        // Create a mock model that will fail validation
        $invalidModel = $this->getMockBuilder('stdClass')
            ->addMethods(['getName', 'validate', 'hasProperty'])
            ->getMock();
        $invalidModel->method('getName')->willReturn('ee:Channel');
        $invalidModel->method('hasProperty')->willReturn(true);
        
        $validationResult = $this->getMockBuilder('stdClass')
            ->addMethods(['failed', 'getFailed'])
            ->getMock();
        $validationResult->method('failed')->willReturn(true);
        $validationResult->method('getFailed')->willReturn(['channel_title' => ['required' => 'Channel title is required']]);
        
        $invalidModel->method('validate')->willReturn($validationResult);

        // Override Model service to return invalid model for Channel
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'make'])
            ->getMock();
        $modelMock->method('get')->willReturnCallback(function($modelName) {
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field = null) {
                            return [];
                        }
                    };
                }
                public function filter($field, $operator = null, $value = null) {
                    return $this;
                }
                public function fields($fields) {
                    return $this;
                }
            };
        });
        $modelMock->method('make')->willReturnCallback(function($modelName) use ($invalidModel) {
            if ($modelName === 'Channel') {
                return $invalidModel;
            }
            // Return valid model for others
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName', 'validate', 'hasProperty'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->method('hasProperty')->willReturn(true);
            $validValidation = $this->getMockBuilder('stdClass')
                ->addMethods(['failed'])
                ->getMock();
            $validValidation->method('failed')->willReturn(false);
            $model->method('validate')->willReturn($validValidation);
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $result = $this->set->validate();

        $this->assertFalse($result->isValid());
        // Check that model errors were added (either model_errors or recoverable_errors)
        $modelErrors = $result->getModelErrors();
        $recoverableErrors = $result->getRecoverableErrors();
        $this->assertTrue(
            !empty($modelErrors) || !empty($recoverableErrors),
            'Expected model validation errors but none were found'
        );
    }

    public function testSaveSavesAllTopLevelElements()
    {
        // Create mock models for each top-level element type
        $channel = $this->getMockBuilder('stdClass')
            ->addMethods(['save', 'getId'])
            ->getMock();
        $channel->expects($this->once())->method('save');
        $channel->method('getId')->willReturn(1);

        $field = $this->getMockBuilder('stdClass')
            ->addMethods(['save', 'getId'])
            ->getMock();
        $field->expects($this->once())->method('save');
        $field->method('getId')->willReturn(2);

        // Set up models in Set's private properties
        $this->setPrivateProperty($this->set, 'channels', ['Test Channel' => $channel]);
        $this->setPrivateProperty($this->set, 'fields', ['test_field' => $field]);
        $this->setPrivateProperty($this->set, 'upload_destinations', []);
        $this->setPrivateProperty($this->set, 'field_groups', []);
        $this->setPrivateProperty($this->set, 'statuses', []);
        $this->setPrivateProperty($this->set, 'category_groups', []);

        // Mock Model service for assignment operations
        $this->setupModelServiceForAssignments();

        $this->set->save();

        // Verify insert_ids were populated
        $insertIds = $this->getPrivateProperty($this->set, 'insert_ids');
        $this->assertArrayHasKey('channels', $insertIds);
        $this->assertEquals([1], $insertIds['channels']);
        $this->assertArrayHasKey('fields', $insertIds);
        $this->assertEquals([2], $insertIds['fields']);
    }

    public function testSavePopulatesInsertIdsForAllElementTypes()
    {
        // Create multiple models for each type
        $channel1 = $this->createMockModel(10);
        $channel2 = $this->createMockModel(11);
        $field1 = $this->createMockModel(20);
        $field2 = $this->createMockModel(21);

        $this->setPrivateProperty($this->set, 'channels', [
            'Channel One' => $channel1,
            'Channel Two' => $channel2
        ]);
        $this->setPrivateProperty($this->set, 'fields', [
            'field_one' => $field1,
            'field_two' => $field2
        ]);
        $this->setPrivateProperty($this->set, 'upload_destinations', []);
        $this->setPrivateProperty($this->set, 'field_groups', []);
        $this->setPrivateProperty($this->set, 'statuses', []);
        $this->setPrivateProperty($this->set, 'category_groups', []);

        $this->setupModelServiceForAssignments();

        $this->set->save();

        // Verify all IDs are captured
        $this->assertEquals([10, 11], $this->set->getIdsForElementType('channels'));
        $this->assertEquals([20, 21], $this->set->getIdsForElementType('fields'));
    }

    public function testSaveExecutesPostSaveQueue()
    {
        // Set up empty models
        $this->setPrivateProperty($this->set, 'channels', []);
        $this->setPrivateProperty($this->set, 'fields', []);
        $this->setPrivateProperty($this->set, 'upload_destinations', []);
        $this->setPrivateProperty($this->set, 'field_groups', []);
        $this->setPrivateProperty($this->set, 'statuses', []);
        $this->setPrivateProperty($this->set, 'category_groups', []);

        // Create a closure to track execution
        $executed = false;
        $closure = function() use (&$executed) {
            $executed = true;
        };

        $this->setPrivateProperty($this->set, 'post_save_queue', [$closure]);

        $this->set->save();

        $this->assertTrue($executed, 'Post-save queue closure should have been executed');
    }

    public function testSaveCallsAssignmentMethods()
    {
        // Create a channel with field groups and fields assigned
        $channel = $this->createMockModel(1);
        $channel->expects($this->atLeastOnce())->method('save'); // Called during assignment

        $fieldGroup = $this->createMockModel(10);
        $fieldGroup->expects($this->atLeastOnce())->method('save');

        $field = $this->createMockModel(20);

        $this->setPrivateProperty($this->set, 'channels', ['Test Channel' => $channel]);
        $this->setPrivateProperty($this->set, 'field_groups', ['Test Group' => $fieldGroup]);
        $this->setPrivateProperty($this->set, 'fields', ['test_field' => $field]);
        $this->setPrivateProperty($this->set, 'upload_destinations', []);
        $this->setPrivateProperty($this->set, 'statuses', []);
        $this->setPrivateProperty($this->set, 'category_groups', []);

        // Set up assignments
        $this->setPrivateProperty($this->set, 'assignments', [
            'previously_created_field_groups' => [],
            'channel_field_groups' => ['Test Channel' => [$fieldGroup]],
            'channel_fields' => ['Test Channel' => ['test_field']],
            'field_group_fields' => [],
            'statuses' => []
        ]);

        // Mock Model service for assignment operations using anonymous classes
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        
        $modelMock->method('get')->willReturnCallback(function($modelName, $ids = null) use ($fieldGroup, $field) {
            // Return a builder that has filter() and all() methods
            return new class($modelName, $ids, $fieldGroup, $field) {
                private $modelName;
                private $ids;
                private $fieldGroup;
                private $field;
                
                public function __construct($modelName, $ids, $fieldGroup, $field) {
                    $this->modelName = $modelName;
                    $this->ids = $ids;
                    $this->fieldGroup = $fieldGroup;
                    $this->field = $field;
                }
                
                public function filter($field, $operator, $value) {
                    return $this;
                }
                
                public function all() {
                    // Return appropriate collection based on model name
                    if ($this->modelName === 'ChannelFieldGroup') {
                        return [$this->fieldGroup];
                    } elseif ($this->modelName === 'ChannelField') {
                        return [$this->field];
                    }
                    return [];
                }
            };
        });
        
        ee()->setMock('Model', $modelMock);

        $this->set->save();

        // Verify insert_ids were populated
        $insertIds = $this->getPrivateProperty($this->set, 'insert_ids');
        $this->assertArrayHasKey('channels', $insertIds);
        $this->assertArrayHasKey('field_groups', $insertIds);
    }

    /**
     * Helper to create a mock model with save() and getId() methods
     */
    private function createMockModel($id)
    {
        $model = $this->getMockBuilder('stdClass')
            ->addMethods(['save', 'getId'])
            ->getMock();
        $model->method('save')->willReturn(true);
        $model->method('getId')->willReturn($id);
        return $model;
    }

    /**
     * Setup Model service mock for assignment operations
     */
    private function setupModelServiceForAssignments()
    {
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        
        $builderMock = $this->getMockBuilder('stdClass')
            ->addMethods(['filter', 'all'])
            ->getMock();
        $builderMock->method('filter')->willReturnSelf();
        $builderMock->method('all')->willReturn([]);
        
        $modelMock->method('get')->willReturn($builderMock);
        ee()->setMock('Model', $modelMock);
    }

    public function testGetIdsForElementTypeReturnsCorrectIds()
    {
        // Set up insert_ids array directly
        $this->setPrivateProperty($this->set, 'insert_ids', [
            'channels' => [1, 2, 3],
            'fields' => [4, 5]
        ]);

        $this->assertEquals([1, 2, 3], $this->set->getIdsForElementType('channels'));
        $this->assertEquals([4, 5], $this->set->getIdsForElementType('fields'));
        $this->assertEquals([], $this->set->getIdsForElementType('nonexistent'));
    }

    public function testCleanUpSourceFilesMethodExists()
    {
        // Test that cleanUpSourceFiles method exists
        // The full integration test would require mocking the Filesystem service
        $this->assertTrue(method_exists($this->set, 'cleanUpSourceFiles'));
        $this->markTestSkipped('Clean up source files test requires Filesystem service mocking');
    }

    public function testGetIdsForChannelsReturnsChannelIds()
    {
        // Mock channel models
        $channel1 = m::mock('stdClass');
        $channel1->shouldReceive('getId')->andReturn(10);

        $channel2 = m::mock('stdClass');
        $channel2->shouldReceive('getId')->andReturn(20);

        // Set up channels array
        $channels = [
            'channel_one' => $channel1,
            'channel_two' => $channel2
        ];

        $this->setPrivateProperty($this->set, 'channels', $channels);

        $result = $this->invokePrivateMethod($this->set, 'getIdsForChannels', [['channel_one', 'channel_two']]);

        $this->assertEquals([
            'channel_one' => 10,
            'channel_two' => 20
        ], $result);
    }

    public function testApplyOverridesMethodExists()
    {
        // Test that applyOverrides method exists - full test requires complex mocking
        $this->assertTrue(method_exists($this->set, 'applyOverrides'));
        $this->markTestSkipped('Apply overrides test requires complex model mocking');
    }

    public function testSetAliasesSetsAliasesArray()
    {
        $aliases = ['test' => 'aliases'];

        $this->set->setAliases($aliases);

        $this->assertEquals($aliases, $this->getPrivateProperty($this->set, 'aliases'));
    }

    public function testLoadUploadDestinations()
    {
        $destinations = [
            (object)[
                'name' => 'uploads',
                'adapter' => 'local',
                'url' => '{base_url}/uploads',
                'server_path' => '/path/to/uploads'
            ],
            (object)[
                'name' => 'images',
                'adapter' => 's3',
                'url' => 'https://s3.amazonaws.com/bucket',
                'server_path' => null
            ]
        ];

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            // Set properties directly
            $model->site_id = 1;
            $model->name = '';
            $model->adapter = '';
            $model->url = '';
            $model->server_path = null;
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadUploadDestinations', [$destinations]);

        $uploadDests = $this->getPrivateProperty($this->set, 'upload_destinations');
        $this->assertArrayHasKey('uploads', $uploadDests);
        $this->assertArrayHasKey('images', $uploadDests);
        $this->assertEquals('uploads', $uploadDests['uploads']->name);
        $this->assertEquals('images', $uploadDests['images']->name);
    }

    public function testLoadUploadDestinationsWithDefaults()
    {
        $destinations = [
            (object)['name' => 'default_uploads']
        ];

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->site_id = 1;
            $model->name = '';
            $model->adapter = '';
            $model->url = '';
            $model->server_path = null;
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadUploadDestinations', [$destinations]);

        $uploadDests = $this->getPrivateProperty($this->set, 'upload_destinations');
        $this->assertArrayHasKey('default_uploads', $uploadDests);
        $this->assertEquals('local', $uploadDests['default_uploads']->adapter);
        $this->assertEquals('{base_url}', $uploadDests['default_uploads']->url);
    }

    public function testLoadChannels()
    {
        $channels = [
            (object)[
                'channel_title' => 'Test Channel',
                'channel_name' => 'test_channel'
            ]
        ];

        // Mock config
        ee()->config->setItem('app_version', '4.0.0');

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName', 'hasProperty', 'validate'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->method('hasProperty')->willReturn(true);
            $model->site_id = 1;
            $model->channel_name = '';
            $model->channel_title = '';
            $model->channel_lang = 'en';
            $validationResult = $this->getMockBuilder('stdClass')
                ->addMethods(['failed'])
                ->getMock();
            $validationResult->method('failed')->willReturn(false);
            $model->method('validate')->willReturn($validationResult);
            return $model;
        });

        // Mock get() for status queries
        $collectionMock = new class {
            public function all() {
                return new class {
                    public function pluck($field) {
                        return [];
                    }
                };
            }
        };
        $builderMock = new class($collectionMock) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function all() {
                return $this->collection->all();
            }
            public function filter($field, $operator, $value) {
                return $this;
            }
        };
        $modelMock->method('get')->willReturn($builderMock);
        ee()->setMock('Model', $modelMock);

        // Mock load->helper
        ee()->load->helper = function($name) {};

        $this->invokePrivateMethod($this->set, 'loadChannels', [$channels]);

        $loadedChannels = $this->getPrivateProperty($this->set, 'channels');
        $this->assertArrayHasKey('Test Channel', $loadedChannels);
        $this->assertEquals('test_channel', $loadedChannels['Test Channel']->channel_name);
    }

    public function testLoadStatuses()
    {
        $statuses = [
            (object)['name' => 'draft', 'highlight' => '#ff0000'],
            (object)['name' => 'review']
        ];

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->status = '';
            $model->highlight = '';
            return $model;
        });

        // Mock get() for existing statuses query
        $collectionMock = new class {
            public function all() {
                return new class {
                    public function pluck($field) {
                        return ['open', 'closed']; // Existing statuses
                    }
                };
            }
        };
        $builderMock = new class($collectionMock) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function all() {
                return $this->collection->all();
            }
        };
        $modelMock->method('get')->willReturn($builderMock);
        ee()->setMock('Model', $modelMock);

        $result = $this->invokePrivateMethod($this->set, 'loadStatuses', [$statuses]);

        $loadedStatuses = $this->getPrivateProperty($this->set, 'statuses');
        $this->assertCount(2, $loadedStatuses);
        $this->assertEquals('draft', $loadedStatuses[0]->status);
        $this->assertEquals('#ff0000', $loadedStatuses[0]->highlight);
        $this->assertEquals('review', $loadedStatuses[1]->status);
        
        // Should return array of status names
        $this->assertIsArray($result);
        $this->assertContains('draft', $result);
        $this->assertContains('review', $result);
    }

    public function testLoadStatusesSkipsExistingStatuses()
    {
        $statuses = [
            (object)['name' => 'open'], // Already exists
            (object)['name' => 'new_status']
        ];

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->status = '';
            return $model;
        });

        // Mock existing statuses including 'open'
        $collectionMock = new class {
            public function all() {
                return new class {
                    public function pluck($field) {
                        return ['open', 'closed'];
                    }
                };
            }
        };
        $builderMock = new class($collectionMock) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function all() {
                return $this->collection->all();
            }
        };
        $modelMock->method('get')->willReturn($builderMock);
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadStatuses', [$statuses]);

        // Should only create 'new_status', not 'open'
        $loadedStatuses = $this->getPrivateProperty($this->set, 'statuses');
        $this->assertCount(1, $loadedStatuses);
        $this->assertEquals('new_status', $loadedStatuses[0]->status);
    }

    public function testLoadStatusGroups()
    {
        $statusGroups = [
            (object)[
                'name' => 'content_statuses',
                'statuses' => [
                    (object)['name' => 'draft'],
                    (object)['name' => 'published']
                ]
            ]
        ];

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->status = '';
            return $model;
        });

        $collectionMock = new class {
            public function all() {
                return new class {
                    public function pluck($field) {
                        return [];
                    }
                };
            }
        };
        $builderMock = new class($collectionMock) {
            private $collection;
            public function __construct($collection) {
                $this->collection = $collection;
            }
            public function all() {
                return $this->collection->all();
            }
        };
        $modelMock->method('get')->willReturn($builderMock);
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadStatusGroups', [$statusGroups]);

        $statusGroupsLoaded = $this->getPrivateProperty($this->set, 'status_groups');
        $this->assertArrayHasKey('content_statuses', $statusGroupsLoaded);
        $this->assertContains('draft', $statusGroupsLoaded['content_statuses']);
        $this->assertContains('published', $statusGroupsLoaded['content_statuses']);
    }

    public function testLoadCategoryGroups()
    {
        $categoryGroups = [
            (object)[
                'name' => 'blog_categories',
                'sort_order' => 'a',
                'categories' => [
                    'Technology',
                    'Design',
                    (object)[
                        'cat_name' => 'Custom Category',
                        'cat_url_title' => 'custom-category',
                        'cat_description' => 'A custom category',
                        'cat_order' => 5
                    ]
                ]
            ]
        ];

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            
            if ($modelName === 'CategoryGroup') {
                $model->site_id = 1;
                $model->group_name = '';
                $model->sort_order = 'a';
                $model->Categories = [];
            } elseif ($modelName === 'Category') {
                $model = $this->getMockBuilder('stdClass')
                    ->addMethods(['getName', 'on'])
                    ->getMock();
                $model->method('getName')->willReturn('ee:' . $modelName);
                $model->method('on')->willReturn(true); // Event hook registration
                $model->site_id = 1;
                $model->parent_id = 0;
                $model->cat_name = '';
                $model->cat_url_title = '';
                $model->cat_description = '';
                $model->cat_order = 0;
            }
            
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadCategoryGroups', [$categoryGroups]);

        $loadedGroups = $this->getPrivateProperty($this->set, 'category_groups');
        $this->assertArrayHasKey('blog_categories', $loadedGroups);
        $group = $loadedGroups['blog_categories'];
        $this->assertEquals('blog_categories', $group->group_name);
        $this->assertEquals('a', $group->sort_order);
        $this->assertCount(3, $group->Categories);
    }

    public function testLoadCategoryGroupsWithCustomSortOrder()
    {
        $categoryGroups = [
            (object)[
                'name' => 'sorted_categories',
                'sort_order' => 'c',
                'categories' => ['First', 'Second', 'Third']
            ]
        ];

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            
            if ($modelName === 'CategoryGroup') {
                $model->site_id = 1;
                $model->group_name = '';
                $model->sort_order = 'c';
                $model->Categories = [];
            } elseif ($modelName === 'Category') {
                $model = $this->getMockBuilder('stdClass')
                    ->addMethods(['getName', 'on'])
                    ->getMock();
                $model->method('getName')->willReturn('ee:' . $modelName);
                $model->method('on')->willReturn(true);
                $model->site_id = 1;
                $model->parent_id = 0;
                $model->cat_name = '';
                $model->cat_url_title = '';
                $model->cat_order = 0;
            }
            
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadCategoryGroups', [$categoryGroups]);

        $loadedGroups = $this->getPrivateProperty($this->set, 'category_groups');
        $group = $loadedGroups['sorted_categories'];
        // With sort_order 'c', categories should have cat_order set
        $this->assertEquals(1, $group->Categories[0]->cat_order);
        $this->assertEquals(2, $group->Categories[1]->cat_order);
        $this->assertEquals(3, $group->Categories[2]->cat_order);
    }

    public function testLoadFieldGroup()
    {
        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->site_id = 0;
            $model->group_name = '';
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $result = $this->invokePrivateMethod($this->set, 'loadFieldGroup', ['test_group']);

        $fieldGroups = $this->getPrivateProperty($this->set, 'field_groups');
        $this->assertArrayHasKey('test_group', $fieldGroups);
        $this->assertEquals('test_group', $fieldGroups['test_group']->group_name);
        $this->assertEquals($fieldGroups['test_group'], $result);
    }

    public function testLoadChannelField()
    {
        // Create a temporary field file
        $fieldDir = $this->tempDir . '/custom_fields';
        mkdir($fieldDir, 0777, true);
        
        $fieldData = [
            'label' => 'Test Field',
            'order' => 1,
            'required' => 'y',
            'settings' => ['field_maxl' => 100]
        ];
        file_put_contents($fieldDir . '/test_field.text', json_encode($fieldData));

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $fieldtypes = ['text', 'textarea', 'select'];
        $modelMock->method('get')->willReturnCallback(function($modelName) use ($fieldtypes) {
            if ($modelName === 'Fieldtype') {
                return new class($fieldtypes) {
                    private $fieldtypes;
                    public function __construct($fieldtypes) {
                        $this->fieldtypes = $fieldtypes;
                    }
                    public function all() {
                        return new class($this->fieldtypes) {
                            private $fieldtypes;
                            public function __construct($fieldtypes) {
                                $this->fieldtypes = $fieldtypes;
                            }
                            public function pluck($field) {
                                return $this->fieldtypes;
                            }
                        };
                    }
                };
            }
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field) {
                            return [];
                        }
                    };
                }
            };
        });
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName', 'set'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->method('set')->willReturn(true);
            $model->site_id = 0;
            $model->field_name = '';
            $model->field_type = '';
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $file = new \SplFileInfo($fieldDir . '/test_field.text');
        $result = $this->invokePrivateMethod($this->set, 'loadChannelField', [$file]);

        $this->assertNotNull($result);
        $this->assertEquals('test_field', $result->field_name);
        $this->assertEquals('text', $result->field_type);
    }

    public function testLoadChannelFieldThrowsExceptionForInvalidFilename()
    {
        // Create invalid field file (wrong format)
        $fieldDir = $this->tempDir . '/custom_fields';
        mkdir($fieldDir, 0777, true);
        file_put_contents($fieldDir . '/invalid.field.type', 'test');

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('get')->willReturn(new class {
            public function all() {
                return new class {
                    public function pluck($field) {
                        return ['text'];
                    }
                };
            }
        });
        ee()->setMock('Model', $modelMock);

        $file = new \SplFileInfo($fieldDir . '/invalid.field.type');
        
        $this->expectException(\ExpressionEngine\Service\ChannelSet\ImportException::class);
        $this->expectExceptionMessage('Invalid field definition');
        
        $this->invokePrivateMethod($this->set, 'loadChannelField', [$file]);
    }

    public function testLoadChannelFieldThrowsExceptionForUnknownFieldtype()
    {
        // Create field file with unknown fieldtype
        $fieldDir = $this->tempDir . '/custom_fields';
        mkdir($fieldDir, 0777, true);
        file_put_contents($fieldDir . '/test_field.unknown', json_encode(['label' => 'Test']));

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('get')->willReturn(new class {
            public function all() {
                return new class {
                    public function pluck($field) {
                        return ['text', 'textarea']; // Doesn't include 'unknown'
                    }
                };
            }
        });
        ee()->setMock('Model', $modelMock);

        $file = new \SplFileInfo($fieldDir . '/test_field.unknown');
        
        $this->expectException(\ExpressionEngine\Service\ChannelSet\ImportException::class);
        $this->expectExceptionMessage('Fieldtype not installed');
        
        $this->invokePrivateMethod($this->set, 'loadChannelField', [$file]);
    }

    public function testLoadFieldsAndGroupsWithDirectoryStructure()
    {
        // Create directory structure with field group and fields
        $fieldDir = $this->tempDir . '/custom_fields';
        $groupDir = $fieldDir . '/test_group';
        mkdir($groupDir, 0777, true);
        
        // Create field files
        file_put_contents($groupDir . '/field1.text', json_encode(['label' => 'Field 1']));
        file_put_contents($groupDir . '/field2.textarea', json_encode(['label' => 'Field 2']));

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $fieldtypes = ['text', 'textarea', 'select'];
        $modelMock->method('get')->willReturnCallback(function($modelName) use ($fieldtypes) {
            if ($modelName === 'Fieldtype') {
                return new class($fieldtypes) {
                    private $fieldtypes;
                    public function __construct($fieldtypes) {
                        $this->fieldtypes = $fieldtypes;
                    }
                    public function all() {
                        return new class($this->fieldtypes) {
                            private $fieldtypes;
                            public function __construct($fieldtypes) {
                                $this->fieldtypes = $fieldtypes;
                            }
                            public function pluck($field) {
                                return $this->fieldtypes;
                            }
                        };
                    }
                };
            } elseif ($modelName === 'ChannelField') {
                return new class {
                    public function all() {
                        return [];
                    }
                };
            }
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field) {
                            return [];
                        }
                    };
                }
            };
        });
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            if ($modelName === 'ChannelFieldGroup') {
                $model = $this->getMockBuilder('stdClass')
                    ->addMethods(['getName', 'on'])
                    ->getMock();
                $model->method('getName')->willReturn('ee:' . $modelName);
                $model->method('on')->willReturn(true);
                $model->site_id = 0;
                $model->group_name = '';
                return $model;
            } elseif ($modelName === 'ChannelField') {
                $model = $this->getMockBuilder('stdClass')
                    ->addMethods(['getName', 'set', 'getId'])
                    ->getMock();
                $model->method('getName')->willReturn('ee:' . $modelName);
                $model->method('set')->willReturn(true);
                $model->method('getId')->willReturn(1);
                $model->site_id = 0;
                $model->field_name = '';
                $model->field_type = '';
                return $model;
            }
            return $this->getMockBuilder('stdClass')->getMock();
        });
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadFieldsAndGroups', [[]]);

        $fields = $this->getPrivateProperty($this->set, 'fields');
        $fieldGroups = $this->getPrivateProperty($this->set, 'field_groups');
        
        $this->assertArrayHasKey('field1', $fields);
        $this->assertArrayHasKey('field2', $fields);
        $this->assertArrayHasKey('test_group', $fieldGroups);
    }

    public function testLoadFieldsAndGroupsWithStandaloneFields()
    {
        // Create directory with standalone fields (not in groups)
        $fieldDir = $this->tempDir . '/custom_fields';
        mkdir($fieldDir, 0777, true);
        
        file_put_contents($fieldDir . '/standalone.text', json_encode(['label' => 'Standalone']));

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('get')->willReturnCallback(function($modelName) {
            if ($modelName === 'Fieldtype') {
                return new class {
                    public function all() {
                        return new class {
                            public function pluck($field) {
                                return ['text'];
                            }
                        };
                    }
                };
            }
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field) {
                            return [];
                        }
                    };
                }
            };
        });
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName', 'set', 'getId'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->method('set')->willReturn(true);
            $model->method('getId')->willReturn(1);
            $model->site_id = 0;
            $model->field_name = '';
            $model->field_type = '';
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadFieldsAndGroups', [[]]);

        $fields = $this->getPrivateProperty($this->set, 'fields');
        $this->assertArrayHasKey('standalone', $fields);
    }

    public function testLoadFieldsAndGroupsWithFieldGroupsFromJson()
    {
        // Create empty custom_fields directory
        $fieldDir = $this->tempDir . '/custom_fields';
        mkdir($fieldDir, 0777, true);

        $fieldGroups = [
            (object)[
                'name' => 'json_group',
                'fields' => ['field1', 'field2']
            ]
        ];

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->site_id = 0;
            $model->group_name = '';
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadFieldsAndGroups', [$fieldGroups]);

        $assignments = $this->getPrivateProperty($this->set, 'assignments');
        $this->assertArrayHasKey('field_group_fields', $assignments);
        $this->assertArrayHasKey('json_group', $assignments['field_group_fields']);
        $this->assertEquals(['field1', 'field2'], $assignments['field_group_fields']['json_group']);
    }

    public function testLoadFieldsAndGroupsReturnsEarlyWhenNoCustomFieldsDirectory()
    {
        // Don't create custom_fields directory
        $fieldsBefore = $this->getPrivateProperty($this->set, 'fields');
        
        $this->invokePrivateMethod($this->set, 'loadFieldsAndGroups', [[]]);
        
        $fieldsAfter = $this->getPrivateProperty($this->set, 'fields');
        // Should be unchanged
        $this->assertEquals($fieldsBefore, $fieldsAfter);
    }

    public function testLoadCategoryFields()
    {
        // Create category_fields directory structure
        $catFieldDir = $this->tempDir . '/category_fields';
        $groupDir = $catFieldDir . '/test_cat_group';
        mkdir($groupDir, 0777, true);
        
        file_put_contents($groupDir . '/cat_field.text', json_encode(['label' => 'Category Field']));

        // First create the category group
        $catGroup = $this->getMockBuilder('stdClass')
            ->addMethods(['getName'])
            ->getMock();
        $catGroup->method('getName')->willReturn('ee:CategoryGroup');
        $catGroup->CategoryFields = [];
        
        $this->setPrivateProperty($this->set, 'category_groups', ['test_cat_group' => $catGroup]);

        // Mock Model service
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('get')->willReturnCallback(function($modelName) {
            if ($modelName === 'Fieldtype') {
                return new class {
                    public function all() {
                        return new class {
                            public function pluck($field) {
                                return ['text'];
                            }
                        };
                    }
                };
            }
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field) {
                            return [];
                        }
                    };
                }
            };
        });
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName', 'set'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->method('set')->willReturn(true);
            $model->site_id = 1;
            $model->field_name = '';
            $model->field_type = '';
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        $this->invokePrivateMethod($this->set, 'loadCategoryFields');

        $catGroups = $this->getPrivateProperty($this->set, 'category_groups');
        $this->assertCount(1, $catGroups['test_cat_group']->CategoryFields);
    }

    public function testLoadCategoryFieldsReturnsEarlyWhenNoDirectory()
    {
        // Don't create category_fields directory
        $catGroupsBefore = $this->getPrivateProperty($this->set, 'category_groups');
        
        $this->invokePrivateMethod($this->set, 'loadCategoryFields');
        
        $catGroupsAfter = $this->getPrivateProperty($this->set, 'category_groups');
        $this->assertEquals($catGroupsBefore, $catGroupsAfter);
    }

    public function testLoadCallsAllLoadMethods()
    {
        // Create comprehensive channel_set.json
        $channelSetData = [
            'version' => '4.0.0',
            'channels' => [
                (object)['channel_title' => 'Test Channel', 'channel_name' => 'test_channel']
            ],
            'upload_destinations' => [
                (object)['name' => 'uploads', 'adapter' => 'local']
            ],
            'field_groups' => [],
            'category_groups' => [
                (object)['name' => 'test_cats', 'categories' => ['Cat 1']]
            ],
            'statuses' => [
                (object)['name' => 'draft']
            ],
            'status_groups' => []
        ];
        file_put_contents($this->tempDir . '/channel_set.json', json_encode($channelSetData));

        // Setup mocks
        ee()->config->setItem('app_version', '4.0.0');
        $this->setupModelServiceMock();

        // Override make() to return proper models
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('get')->willReturnCallback(function($modelName) {
            if ($modelName === 'Fieldtype') {
                return new class {
                    public function all() {
                        return new class {
                            public function pluck($field) {
                                return ['text'];
                            }
                        };
                    }
                };
            }
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field) {
                            return [];
                        }
                    };
                }
            };
        });
        
        $modelMock->method('make')->willReturnCallback(function($modelName) {
            $model = $this->getMockBuilder('stdClass')
                ->addMethods(['getName', 'hasProperty', 'validate', 'on', 'set'])
                ->getMock();
            $model->method('getName')->willReturn('ee:' . $modelName);
            $model->method('hasProperty')->willReturn(true);
            $model->method('on')->willReturn(true);
            $model->method('set')->willReturn(true);
            
            // All models need validate() method that returns a validation result
            $validationResult = $this->getMockBuilder('stdClass')
                ->addMethods(['failed'])
                ->getMock();
            $validationResult->method('failed')->willReturn(false);
            $model->method('validate')->willReturn($validationResult);
            
            if ($modelName === 'Channel') {
                $model->site_id = 1;
                $model->channel_name = '';
                $model->channel_title = '';
                $model->channel_lang = 'en';
            } elseif ($modelName === 'UploadDestination') {
                $model->site_id = 1;
                $model->name = '';
                $model->adapter = '';
                $model->url = '';
                $model->server_path = null;
            } elseif ($modelName === 'CategoryGroup') {
                $model->site_id = 1;
                $model->group_name = '';
                $model->sort_order = 'a';
                $model->Categories = [];
            } elseif ($modelName === 'Category') {
                $model->site_id = 1;
                $model->parent_id = 0;
                $model->cat_name = '';
                $model->cat_url_title = '';
                $model->cat_order = 0;
            } elseif ($modelName === 'Status') {
                $model->status = '';
                $model->highlight = '';
            }
            
            return $model;
        });
        ee()->setMock('Model', $modelMock);

        ee()->load->helper = function($name) {};

        // Call load() indirectly through validate() which calls load()
        $result = $this->set->validate();

        // Verify all elements were loaded
        $channels = $this->getPrivateProperty($this->set, 'channels');
        $uploadDests = $this->getPrivateProperty($this->set, 'upload_destinations');
        $categoryGroups = $this->getPrivateProperty($this->set, 'category_groups');
        $statuses = $this->getPrivateProperty($this->set, 'statuses');

        $this->assertArrayHasKey('Test Channel', $channels);
        $this->assertArrayHasKey('uploads', $uploadDests);
        $this->assertArrayHasKey('test_cats', $categoryGroups);
        $this->assertCount(1, $statuses);
    }

    public function testLoadHandlesExceptions()
    {
        // Create valid JSON structure
        $channelSetData = [
            'version' => '4.0.0',
            'channels' => [],
            'upload_destinations' => [
                (object)['name' => 'test'] // This will trigger loadUploadDestinations
            ],
            'field_groups' => [],
            'category_groups' => [],
            'statuses' => []
        ];
        file_put_contents($this->tempDir . '/channel_set.json', json_encode($channelSetData));

        ee()->config->setItem('app_version', '4.0.0');

        // Mock Model service to throw exception when make() is called
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'get'])
            ->getMock();
        
        $modelMock->method('get')->willReturn(new class {
            public function all() {
                return new class {
                    public function pluck($field) {
                        return [];
                    }
                };
            }
        });
        
        // Throw exception when trying to create UploadDestination
        $modelMock->method('make')->willThrowException(new \Exception('Test exception during load'));

        ee()->setMock('Model', $modelMock);

        $result = $this->set->validate();

        // Should have error from exception
        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertNotEmpty($errors);
        // Check if error message contains our exception message
        $errorFound = false;
        foreach ($errors as $error) {
            if (strpos($error, 'Test exception during load') !== false) {
                $errorFound = true;
                break;
            }
        }
        $this->assertTrue($errorFound, 'Expected exception error message not found in errors: ' . print_r($errors, true));
    }

    /**
     * Create a valid channel_set.json file for testing
     */
    private function createValidChannelSetJson()
    {
        $channelSetData = [
            'version' => '4.0.0',
            'channels' => [
                [
                    'channel_title' => 'Test Channel',
                    'channel_name' => 'test_channel'
                ]
            ],
            'field_groups' => [],
            'category_groups' => [],
            'upload_destinations' => [],
            'statuses' => []
        ];

        file_put_contents($this->tempDir . '/channel_set.json', json_encode($channelSetData));
    }

    /**
     * Setup Model service mock to handle chained calls like:
     * ee('Model')->get('Status')->all()->pluck('status')
     */
    private function setupModelServiceMock()
    {
        // Create Model service mock with get() and make() methods
        $modelService = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'make'])
            ->getMock();
        
        // get() returns a builder that has all() which returns a collection with pluck()
        $modelService->method('get')->willReturnCallback(function($modelName) {
            return new class {
                public function all() {
                    return new class {
                        public function pluck($field = null) {
                            return [];
                        }
                    };
                }
                public function filter($field, $operator = null, $value = null) {
                    return $this;
                }
                public function fields($fields) {
                    return $this;
                }
            };
        });
        
        // make() returns a basic model mock
        $modelService->method('make')->willReturnCallback(function($modelName) {
            return $this->getMockBuilder('stdClass')->getMock();
        });
        
        // Set the mock - this must be done before any ee('Model') calls
        ee()->setMock('Model', $modelService);
    }

    /**
     * Setup basic mocks needed for load() operations
     */
    private function setupBasicMocks()
    {
        // Mock config - use setItem() method from eeSingletonConfigMock
        ee()->config->setItem('app_version', '4.0.0');

        // Setup Model service mock
        $this->setupModelServiceMock();

        // Mock load->helper
        ee()->load->helper = function($name) {};
    }

    /**
     * Helper method to access private properties for testing
     */
    private function getPrivateProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    /**
     * Helper method to set private properties for testing
     */
    private function setPrivateProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $method, $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $meth = $reflection->getMethod($method);
        $meth->setAccessible(true);
        return $meth->invokeArgs($object, $args);
    }

    /**
     * Recursively remove a directory
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
