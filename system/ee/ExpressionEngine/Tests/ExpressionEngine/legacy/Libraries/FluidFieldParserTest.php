<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use ExpressionEngine\Addons\FluidField\Model\FluidField;
use ExpressionEngine\Service\Model\Collection;

require_once __DIR__ . '/../../../eeObjectMock.php';

class FluidFieldTestDouble
{
    public $ChannelField;
    public $ChannelFieldGroup;
    public $field_id;
    public $entry_id;
    public $fluid_field_id;
    public $group;
    public $order;
    public $field_data_id;
    private $id;
    private $field;
    private $fieldData;

    public function __construct(int $id, string $type, string $name)
    {
        $this->id = $id;
        $this->ChannelField = (object) [
            'field_type' => $type,
            'field_name' => $name,
            'field_order' => $id,
            'field_label' => ucfirst($name),
        ];
        $this->ChannelFieldGroup = null;
        $this->field = new class($type) {
            public $items = [];
            private $type;

            public function __construct(string $type)
            {
                $this->type = $type;
            }

            public function setItem($key, $value)
            {
                $this->items[$key] = $value;

                return $this;
            }

            public function getType()
            {
                return $this->type;
            }
        };
        $this->setFieldData([]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setFieldData(array $values)
    {
        $this->fieldData = new class($values) {
            private $values;

            public function __construct(array $values)
            {
                $this->values = $values;
            }

            public function getValues()
            {
                return $this->values;
            }
        };
    }

    public function getFieldData()
    {
        return $this->fieldData;
    }
}

class FluidFieldParserTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(\ExpressionEngine\Addons\FluidField\Model\FluidField::class, false)) {
            class_alias(
                __NAMESPACE__ . '\FluidFieldTestDouble',
                \ExpressionEngine\Addons\FluidField\Model\FluidField::class
            );
        }

        require_once BASEPATH . 'libraries/Fluid_field_parser.php';
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testConstructInitializesReservedModifiersList(): void
    {
        $parser = $this->makeParser();

        $this->assertSame(
            [
                'first',
                'last',
                'count',
                'index',
                'current_field_name',
                'next_field_name',
                'prev_field_name',
                'current_field_type',
                'next_fieldtype',
                'prev_fieldtype',
                'length',
                'total_fields',
            ],
            $parser->modifiers
        );
    }

    public function testRewriteFluidTagsAsConditionalsRewritesOnlyProvidedFields(): void
    {
        $parser = $this->makeParser();
        $tagdata = '{fluid:text}Text{/fluid:text}{fluid:image}Image{/fluid:image}';

        $result = $this->invokePrivateMethod(
            $parser,
            'rewriteFluidTagsAsConditionals',
            [$tagdata, ['fluid:text']]
        );

        $this->assertSame('{if fluid:text}Text{/if}{fluid:image}Image{/fluid:image}', $result);
    }

    public function testRewriteFluidTagsAsConditionalsReturnsOriginalTagdataWhenFieldListIsEmpty(): void
    {
        $parser = $this->makeParser();
        $tagdata = '{fluid:text}Text{/fluid:text}';

        $result = $this->invokePrivateMethod(
            $parser,
            'rewriteFluidTagsAsConditionals',
            [$tagdata, []]
        );

        $this->assertSame($tagdata, $result);
    }

    public function testReplaceInnerFieldsPairsReplacesPairsWithStablePlaceholdersAndStoresMap(): void
    {
        $pairs = [
            [null, null, [], '{fields}{fluid:text}{/fields}'],
            [null, null, [], '{fields order="desc"}{fluid:image}{/fields}'],
        ];

        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock($pairs));

        $parser = $this->makeParser();
        $tagdata = 'A {fields}{fluid:text}{/fields} B {fields order="desc"}{fluid:image}{/fields} C';

        $result = $this->invokePrivateMethod($parser, 'replaceInnerFieldsPairs', [$tagdata]);

        $this->assertSame('A {!-- ff:fields:0 --} B {!-- ff:fields:1 --} C', $result);
        $this->assertSame(
            [
                0 => '{fields}{fluid:text}{/fields}',
                1 => '{fields order="desc"}{fluid:image}{/fields}',
            ],
            $this->getPrivateProperty($parser, 'replacements')
        );
    }

    public function testReplaceInnerFieldsPairsReturnsOriginalTagdataWhenNoPairsExist(): void
    {
        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock([]));

        $parser = $this->makeParser();
        $tagdata = '{fluid:text}Text{/fluid:text}';

        $result = $this->invokePrivateMethod($parser, 'replaceInnerFieldsPairs', [$tagdata]);

        $this->assertSame($tagdata, $result);
        $this->assertSame([], $this->getPrivateProperty($parser, 'replacements'));
    }

    public function testRestoreInnerFieldsPairsRestoresStoredChunks(): void
    {
        ee()->setMock(
            'api_channel_fields',
            $this->buildApiChannelFieldsMock([
                [null, null, [], '{fields}{fluid:text}{/fields}'],
            ])
        );

        $parser = $this->makeParser();
        $replaced = $this->invokePrivateMethod($parser, 'replaceInnerFieldsPairs', ['X {fields}{fluid:text}{/fields} Y']);

        $result = $this->invokePrivateMethod($parser, 'restoreInnerFieldsPairs', [$replaced]);

        $this->assertSame('X {fields}{fluid:text}{/fields} Y', $result);
    }

    public function testRestoreInnerFieldsPairsReturnsOriginalWhenThereAreNoReplacements(): void
    {
        $parser = $this->makeParser();
        $tagdata = 'no placeholders here';

        $result = $this->invokePrivateMethod($parser, 'restoreInnerFieldsPairs', [$tagdata]);

        $this->assertSame($tagdata, $result);
    }

    public function testPreProcessReturnsTrueAndStoresStateWhenFluidFieldTagsMatch(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $field_name = null)
            {
                return ['field_name' => $field_name];
            }
        });

        $preParser = new class {
            public function prefix()
            {
                return 'fluid:';
            }

            public function entry_ids()
            {
                return [];
            }
        };

        $parser = $this->makeParser();

        $result = $parser->pre_process(
            '{fluid:content}Body{/fluid:content}',
            $preParser,
            ['content' => 11]
        );

        $this->assertTrue($result);
        $this->assertSame([11 => 'content'], $this->getPrivateProperty($parser, 'fluid_fields'));
        $this->assertSame('fluid:', $this->getPrivateProperty($parser, '_prefix'));
        $this->assertSame([], $this->getPrivateProperty($parser, 'data')->asArray());
    }

    public function testPreProcessReturnsFalseWhenNoFluidFieldTagsExist(): void
    {
        $preParser = new class {
            public function prefix()
            {
                return 'fluid:';
            }
        };

        $parser = $this->makeParser();

        $result = $parser->pre_process('plain text only', $preParser, ['content' => 11]);

        $this->assertFalse($result);
    }

    public function testPreProcessIgnoresClosingTagsWhenCollectingFluidFieldIds(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $field_name = null)
            {
                return ['field_name' => $field_name];
            }
        });

        $preParser = new class {
            public function prefix()
            {
                return 'fluid:';
            }

            public function entry_ids()
            {
                return [];
            }
        };

        $parser = $this->makeParser();

        $result = $parser->pre_process('{/fluid:content}', $preParser, ['content' => 11]);

        $this->assertTrue($result);
        $this->assertSame([], $this->getPrivateProperty($parser, 'data')->asArray());
    }

    public function testPreProcessSkipsVariableTagsForNonReservedModifiers(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $field_name = null)
            {
                return ['field_name' => 'custom_modifier'];
            }
        });

        $preParser = new class {
            public function prefix()
            {
                return 'fluid:';
            }

            public function entry_ids()
            {
                return [];
            }
        };

        $parser = $this->makeParser();

        $result = $parser->pre_process(
            '{fluid:content:foo}Body{/fluid:content:foo}',
            $preParser,
            ['content' => 11]
        );

        $this->assertTrue($result);
        $this->assertSame([], $this->getPrivateProperty($parser, 'data')->asArray());
    }

    public function testPreProcessDeduplicatesFluidFieldIdsBeforeFetchingData(): void
    {
        $queryMock = new class {
            public $filters = [];

            public function with()
            {
                return $this;
            }

            public function filter(...$args)
            {
                $this->filters[] = $args;

                return $this;
            }

            public function order()
            {
                return $this;
            }

            public function all()
            {
                return new Collection([]);
            }
        };

        $modelMock = new class($queryMock) {
            public $resources = [];
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                $this->resources[] = $resource;

                return $this->queryMock;
            }
        };

        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $field_name = null)
            {
                return ['field_name' => $field_name];
            }
        });
        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }
        });
        ee()->setMock('Model', $modelMock);

        $preParser = new class {
            public function prefix()
            {
                return 'fluid:';
            }

            public function entry_ids()
            {
                return [5];
            }
        };

        $parser = $this->makeParser();
        $result = $parser->pre_process(
            '{fluid:content}{/fluid:content}{fluid:summary}{/fluid:summary}',
            $preParser,
            ['content' => 11, 'summary' => 11]
        );

        $this->assertTrue($result);
        $this->assertSame(['fluid_field:FluidField'], $modelMock->resources);
        $this->assertSame(
            [
                ['fluid_field_id', 'IN', [11]],
                ['entry_id', 'IN', [5]],
            ],
            $queryMock->filters
        );
        $this->assertSame([], $this->getPrivateProperty($parser, 'data')->asArray());
    }

    public function testPreProcessKeepsReservedModifierTagsWhenCollectingIds(): void
    {
        $queryMock = new class {
            public $filters = [];

            public function with()
            {
                return $this;
            }

            public function filter(...$args)
            {
                $this->filters[] = $args;

                return $this;
            }

            public function order()
            {
                return $this;
            }

            public function all()
            {
                return new Collection([]);
            }
        };

        $modelMock = new class($queryMock) {
            public $resources = [];
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                $this->resources[] = $resource;

                return $this->queryMock;
            }
        };

        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $field_name = null)
            {
                return ['field_name' => 'count'];
            }
        });
        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }
        });
        ee()->setMock('Model', $modelMock);

        $preParser = new class {
            public function prefix()
            {
                return 'fluid:';
            }

            public function entry_ids()
            {
                return [5];
            }
        };

        $parser = $this->makeParser();
        $result = $parser->pre_process(
            '{fluid:content:count}Body{/fluid:content:count}',
            $preParser,
            ['content' => 11]
        );

        $this->assertTrue($result);
        $this->assertSame(['fluid_field:FluidField'], $modelMock->resources);
        $this->assertSame(
            [
                ['fluid_field_id', 'IN', [11]],
                ['entry_id', 'IN', [5]],
            ],
            $queryMock->filters
        );
    }

    public function testPreProcessReturnsFalseWhenMatchedFieldResolvesToUnknownConfiguredKey(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $field_name = null)
            {
                return ['field_name' => 'count'];
            }
        });

        $preParser = new class {
            public function prefix()
            {
                return 'fluid:';
            }
        };

        $parser = $this->makeParser();

        $result = $parser->pre_process(
            '{fluid:content:}Body{/fluid:content:}',
            $preParser,
            ['content:' => 11]
        );

        $this->assertFalse($result);
    }

    public function testOverrideWithPreviewDataReturnsOriginalCollectionWhenNoPreviewDataExists(): void
    {
        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }
        });

        $parser = $this->makeParser();
        $row = (object) ['entry_id' => 3, 'name' => 'keep'];
        $result = $parser->overrideWithPreviewData(new Collection([$row]), [11]);

        $this->assertSame([$row], array_values($result->asArray()));
    }

    public function testOverrideWithPreviewDataFiltersExistingPreviewEntryWhenNoFieldPayloadExists(): void
    {
        $queryMock = new class {
            public $filters = [];

            public function filter(...$args)
            {
                $this->filters[] = $args;

                return $this;
            }

            public function all()
            {
                return new class {
                    public function indexBy($column)
                    {
                        if ($column !== 'id') {
                            throw new \RuntimeException('Unexpected index column');
                        }

                        return [];
                    }
                };
            }
        };

        $modelMock = new class($queryMock) {
            public $resource = null;
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                $this->resource = $resource;

                return $this->queryMock;
            }
        };

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return ['entry_id' => 7];
            }
        });
        ee()->setMock('Model', $modelMock);

        $parser = $this->makeParser();
        $previewRow = (object) ['entry_id' => 7, 'name' => 'preview'];
        $keptRow = (object) ['entry_id' => 9, 'name' => 'keep'];

        $result = $parser->overrideWithPreviewData(new Collection([$previewRow, $keptRow]), [11]);

        $this->assertSame('fluid_field:FluidField', $modelMock->resource);
        $this->assertSame(
            [
                ['fluid_field_id', 'IN', [11]],
                ['entry_id', 7],
            ],
            $queryMock->filters
        );
        $this->assertSame([$keptRow], array_values($result->asArray()));
    }

    public function testGetPossibleFieldsLoadsFromModelAndCachesOnCacheMiss(): void
    {
        $cachedResult = [
            10 => (object) ['field_id' => 10, 'field_name' => 'title', 'field_type' => 'text'],
            11 => (object) ['field_id' => 11, 'field_name' => 'summary', 'field_type' => 'textarea'],
        ];

        $queryMock = new class($cachedResult) {
            public $fieldsArgs = [];
            private $possibleFields;

            public function __construct(array $possibleFields)
            {
                $this->possibleFields = $possibleFields;
            }

            public function fields(...$fields)
            {
                $this->fieldsArgs = $fields;

                return $this;
            }

            public function all()
            {
                return new class($this->possibleFields) {
                    private $possibleFields;

                    public function __construct(array $possibleFields)
                    {
                        $this->possibleFields = $possibleFields;
                    }

                    public function indexBy($column)
                    {
                        if ($column !== 'field_id') {
                            throw new \RuntimeException('Unexpected index column');
                        }

                        return $this->possibleFields;
                    }
                };
            }
        };

        $modelMock = new class($queryMock) {
            public $resource;
            public $ids;
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource, $ids)
            {
                $this->resource = $resource;
                $this->ids = $ids;

                return $this->queryMock;
            }
        };

        $session = new class {
            public $store = [];

            public function cache($class, $key, $value = false)
            {
                if ($value === false) {
                    return $this->store[$class][$key] ?? false;
                }

                $this->store[$class][$key] = $value;

                return true;
            }

            public function set_cache($class, $key, $value)
            {
                return $this->cache($class, $key, $value);
            }
        };

        ee()->setMock('session', $session);
        ee()->setMock('Model', $modelMock);

        $parser = $this->makeParser();

        $result = $this->invokePrivateMethod($parser, 'getPossibleFields', [[10, 11]]);

        $this->assertSame('ChannelField', $modelMock->resource);
        $this->assertSame([10, 11], $modelMock->ids);
        $this->assertSame(['field_id', 'field_name', 'field_type'], $queryMock->fieldsArgs);
        $this->assertSame($cachedResult, $result);
        $this->assertSame(
            $cachedResult,
            $session->cache('Fluid_field_parser', 'ChannelFields/10,11/field_name', false)
        );
    }

    public function testGetPossibleFieldsReturnsCachedDataWithoutModelLookup(): void
    {
        $cachedResult = [
            99 => (object) ['field_id' => 99, 'field_name' => 'cached', 'field_type' => 'text'],
        ];

        $session = new class {
            public $store = [];

            public function cache($class, $key, $value = false)
            {
                if ($value === false) {
                    return $this->store[$class][$key] ?? false;
                }

                $this->store[$class][$key] = $value;

                return true;
            }

            public function set_cache($class, $key, $value)
            {
                return $this->cache($class, $key, $value);
            }
        };
        $session->set_cache('Fluid_field_parser', 'ChannelFields/99/field_name', $cachedResult);

        ee()->setMock('session', $session);
        ee()->setMock('Model', new class {
            public function get()
            {
                throw new \RuntimeException('Model should not be queried when cache hit exists');
            }
        });

        $parser = $this->makeParser();

        $result = $this->invokePrivateMethod($parser, 'getPossibleFields', [[99]]);

        $this->assertSame($cachedResult, $result);
    }

    public function testFetchFluidFieldsReturnsEmptyCollectionWhenInputsAreMissing(): void
    {
        $parser = $this->makeParser();

        $emptyEntries = $this->invokePrivateMethod($parser, 'fetchFluidFields', [[], [11]]);
        $emptyFields = $this->invokePrivateMethod($parser, 'fetchFluidFields', [[7], []]);

        $this->assertSame([], $emptyEntries->asArray());
        $this->assertSame([], $emptyFields->asArray());
    }

    public function testFetchFluidFieldsLoadsRowsByFieldAndHydratesFieldData(): void
    {
        $first = $this->makeFluidFieldDouble(201, 'text', 'title');
        $first->field_id = 101;
        $first->field_data_id = 9001;
        $first->entry_id = 5;
        $first->fluid_field_id = 11;

        $second = $this->makeFluidFieldDouble(202, 'textarea', 'body');
        $second->field_id = 202;
        $second->field_data_id = 9002;
        $second->entry_id = 5;
        $second->fluid_field_id = 11;

        $queryMock = new class(new Collection([$first, $second])) {
            public $withCalls = [];
            public $filters = [];
            public $orders = [];
            private $result;

            public function __construct(Collection $result)
            {
                $this->result = $result;
            }

            public function with($name)
            {
                $this->withCalls[] = $name;

                return $this;
            }

            public function filter(...$args)
            {
                $this->filters[] = $args;

                return $this;
            }

            public function order($column)
            {
                $this->orders[] = $column;

                return $this;
            }

            public function all()
            {
                return $this->result;
            }
        };

        $modelMock = new class($queryMock) {
            public $resources = [];
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                $this->resources[] = $resource;

                return $this->queryMock;
            }
        };

        $dbMock = new class {
            public $whereInCalls = [];
            public $tables = [];
            private $rowsByTable;

            public function __construct()
            {
                $this->rowsByTable = [
                    'channel_data_field_101' => [
                        ['id' => 9001, 'field_id_101' => 'alpha'],
                    ],
                    'channel_data_field_202' => [
                        ['id' => 9002, 'field_id_202' => 'beta'],
                    ],
                ];
            }

            public function where_in($column, $ids)
            {
                $this->whereInCalls[] = [$column, $ids];

                return $this;
            }

            public function get($table)
            {
                $this->tables[] = $table;
                $rows = $this->rowsByTable[$table] ?? [];

                return new class($rows) {
                    private $rows;

                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        return $this->rows;
                    }
                };
            }
        };

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }
        });
        ee()->setMock('Model', $modelMock);
        ee()->setMock('db', $dbMock);

        $parser = $this->makeParser();

        $result = $this->invokePrivateMethod($parser, 'fetchFluidFields', [[5], [11]]);

        $this->assertSame(['fluid_field:FluidField'], $modelMock->resources);
        $this->assertSame(['ChannelField', 'ChannelFieldGroup'], $queryMock->withCalls);
        $this->assertSame(
            [
                ['fluid_field_id', 'IN', [11]],
                ['entry_id', 'IN', [5]],
            ],
            $queryMock->filters
        );
        $this->assertSame(['fluid_field_id', 'entry_id', 'order'], $queryMock->orders);
        $this->assertSame(
            [
                ['id', [9001]],
                ['id', [9002]],
            ],
            $dbMock->whereInCalls
        );
        $this->assertSame(['channel_data_field_101', 'channel_data_field_202'], $dbMock->tables);
        $this->assertSame('alpha', $first->getFieldData()->getValues()['field_id_101']);
        $this->assertSame('beta', $second->getFieldData()->getValues()['field_id_202']);
        $this->assertCount(2, $result->asArray());
    }

    public function testFetchFluidFieldsUsesOneTableQueryPerFieldIdWhenMultipleRowsShareField(): void
    {
        $first = $this->makeFluidFieldDouble(201, 'text', 'title');
        $first->field_id = 101;
        $first->field_data_id = 9001;
        $first->entry_id = 5;
        $first->fluid_field_id = 11;

        $second = $this->makeFluidFieldDouble(202, 'textarea', 'body');
        $second->field_id = 101;
        $second->field_data_id = 9002;
        $second->entry_id = 5;
        $second->fluid_field_id = 11;

        $queryMock = new class(new Collection([$first, $second])) {
            private $result;

            public function __construct(Collection $result)
            {
                $this->result = $result;
            }

            public function with()
            {
                return $this;
            }

            public function filter()
            {
                return $this;
            }

            public function order()
            {
                return $this;
            }

            public function all()
            {
                return $this->result;
            }
        };

        $modelMock = new class($queryMock) {
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                if ($resource !== 'fluid_field:FluidField') {
                    throw new \RuntimeException('Unexpected model resource: ' . $resource);
                }

                return $this->queryMock;
            }
        };

        $dbMock = new class {
            public $whereInCalls = [];
            public $tables = [];

            public function where_in($column, $ids)
            {
                $this->whereInCalls[] = [$column, $ids];

                return $this;
            }

            public function get($table)
            {
                $this->tables[] = $table;

                return new class {
                    public function result_array()
                    {
                        return [
                            ['id' => 9001, 'field_id_101' => 'alpha'],
                            ['id' => 9002, 'field_id_101' => 'beta'],
                        ];
                    }
                };
            }
        };

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }
        });
        ee()->setMock('Model', $modelMock);
        ee()->setMock('db', $dbMock);

        $parser = $this->makeParser();
        $result = $this->invokePrivateMethod($parser, 'fetchFluidFields', [[5], [11]]);

        $this->assertSame([['id', [9001, 9002]]], $dbMock->whereInCalls);
        $this->assertSame(['channel_data_field_101'], $dbMock->tables);
        $this->assertSame('alpha', $first->getFieldData()->getValues()['field_id_101']);
        $this->assertSame('beta', $second->getFieldData()->getValues()['field_id_101']);
        $this->assertCount(2, $result->asArray());
    }

    public function testFetchFluidFieldsExcludesPreviewEntryIdBeforeModelQuery(): void
    {
        $queryMock = new class {
            public $filters = [];

            public function with()
            {
                return $this;
            }

            public function filter(...$args)
            {
                $this->filters[] = $args;

                return $this;
            }

            public function order()
            {
                return $this;
            }

            public function all()
            {
                return new Collection([]);
            }
        };

        $modelMock = new class($queryMock) {
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                if ($resource !== 'fluid_field:FluidField') {
                    throw new \RuntimeException('Unexpected model resource: ' . $resource);
                }

                return $this->queryMock;
            }
        };

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return ['entry_id' => 5];
            }
        });
        ee()->setMock('Model', $modelMock);

        $parser = new class extends \Fluid_field_parser {
            public $overrideCalls = [];

            public function overrideWithPreviewData(Collection $fluid_field_data, array $fluid_field_ids)
            {
                $this->overrideCalls[] = [$fluid_field_data, $fluid_field_ids];

                return $fluid_field_data;
            }
        };

        $result = $this->invokePrivateMethod($parser, 'fetchFluidFields', [[5, 6], [11]]);

        $this->assertSame(
            [
                ['fluid_field_id', 'IN', [11]],
                ['entry_id', 'IN', [1 => 6]],
            ],
            $queryMock->filters
        );
        $this->assertCount(1, $parser->overrideCalls);
        $this->assertSame([11], $parser->overrideCalls[0][1]);
        $this->assertSame([], $result->asArray());
    }

    public function testFetchFluidFieldsSkipsModelQueryWhenPreviewRemovesAllEntryIds(): void
    {
        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return ['entry_id' => 5];
            }
        });
        ee()->setMock('Model', new class {
            public function get()
            {
                throw new \RuntimeException('Model should not be queried when preview filters all entry IDs');
            }
        });

        $parser = new class extends \Fluid_field_parser {
            public $overrideCalls = [];

            public function overrideWithPreviewData(Collection $fluid_field_data, array $fluid_field_ids)
            {
                $this->overrideCalls[] = [$fluid_field_data, $fluid_field_ids];

                return $fluid_field_data;
            }
        };

        $result = $this->invokePrivateMethod($parser, 'fetchFluidFields', [[5], [11]]);

        $this->assertCount(1, $parser->overrideCalls);
        $this->assertSame([], $parser->overrideCalls[0][0]->asArray());
        $this->assertSame([11], $parser->overrideCalls[0][1]);
        $this->assertSame([], $result->asArray());
    }

    public function testEvaluateSingleVariableSupportsFirstLastCountAndIndexModifiers(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($var)
            {
                return ['params' => [], 'modifier' => $var];
            }
        });

        $parser = $this->makeParser();
        $first = $this->makeFluidFieldDouble(10, 'text', 'title');
        $second = $this->makeFluidFieldDouble(11, 'text', 'summary');
        $third = $this->makeFluidFieldDouble(12, 'textarea', 'body');
        $data = new Collection([$first, $second, $third]);

        $this->assertSame(1, $this->invokePrivateMethod($parser, 'evaluateSingleVariable', ['first', $data, $first]));
        $this->assertSame(1, $this->invokePrivateMethod($parser, 'evaluateSingleVariable', ['last', $data, $third]));
        $this->assertSame(2, $this->invokePrivateMethod($parser, 'evaluateSingleVariable', ['count', $data, $second]));
        $this->assertSame(1, $this->invokePrivateMethod($parser, 'evaluateSingleVariable', ['index', $data, $second]));
    }

    public function testEvaluateSingleVariableReturnsZeroWhenFiltersExcludeAllFields(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($var)
            {
                return [
                    'params' => ['type' => 'grid', 'name' => 'missing_field'],
                    'modifier' => 'count'
                ];
            }
        });

        $parser = $this->makeParser();
        $first = $this->makeFluidFieldDouble(10, 'text', 'title');
        $second = $this->makeFluidFieldDouble(11, 'textarea', 'body');
        $data = new Collection([$first, $second]);

        $this->assertSame(
            0,
            $this->invokePrivateMethod($parser, 'evaluateSingleVariable', ['{fluid:count type="grid" name="missing_field"}', $data, $first])
        );
    }

    public function testEvaluateSingleVariableReturnsNullWhenModifierIsUnsupported(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($var)
            {
                return [
                    'params' => ['type' => 'text'],
                    'modifier' => 'unknown_modifier'
                ];
            }
        });

        $parser = $this->makeParser();
        $first = $this->makeFluidFieldDouble(10, 'text', 'title');
        $second = $this->makeFluidFieldDouble(11, 'text', 'summary');
        $data = new Collection([$first, $second]);

        $this->assertNull(
            $this->invokePrivateMethod($parser, 'evaluateSingleVariable', ['{fluid:unknown type="text"}', $data, $first])
        );
    }

    public function testEvaluateSingleVariableReturnsSentinelForCountAndIndexWhenFieldNotFound(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($var)
            {
                return ['params' => [], 'modifier' => $var];
            }
        });

        $parser = $this->makeParser();
        $first = $this->makeFluidFieldDouble(10, 'text', 'title');
        $second = $this->makeFluidFieldDouble(11, 'text', 'summary');
        $missing = $this->makeFluidFieldDouble(99, 'text', 'missing');
        $data = new Collection([$first, $second]);

        $this->assertSame("''", $this->invokePrivateMethod($parser, 'evaluateSingleVariable', ['count', $data, $missing]));
        $this->assertSame("''", $this->invokePrivateMethod($parser, 'evaluateSingleVariable', ['index', $data, $missing]));
    }

    public function testParseReturnsEmptyStringWhenTagdataIsEmpty(): void
    {
        $parser = $this->makeParser();

        $result = $parser->parse(['entry_id' => 1], 11, [], '');

        $this->assertSame('', $result);
    }

    public function testParseReturnsEmptyStringWhenNoFluidRowsMatchEntryAndField(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function extractVariables($tagdata)
            {
                return [
                    'var_single' => [],
                    'var_pair' => ['fluid:content:title' => []],
                ];
            }
        });
        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock([]));
        ee()->setMock('fluid_field:Tag', new class {
            public function parse()
            {
                throw new \RuntimeException('Tag parser should not be invoked when no matching fluid rows exist');
            }

            public function setTag()
            {
                throw new \RuntimeException('Tag parser should not be invoked when no matching fluid rows exist');
            }
        });

        $field = $this->makeFluidFieldDouble(7, 'text', 'title');
        $field->entry_id = 8;
        $field->fluid_field_id = 99;

        $parser = $this->makeParser();
        $this->setPrivateProperty($parser, '_prefix', 'fluid:');
        $this->setPrivateProperty($parser, 'fluid_fields', [11 => 'content']);
        $this->setPrivateProperty($parser, 'data', new Collection([$field]));

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{fluid:content:title}Body{/fluid:content:title}'
        );

        $this->assertSame('', $result);
    }

    public function testParseRendersSingleFieldWhenDataMatchesEntryAndField(): void
    {
        $variablesParser = new class {
            public $tagdata = null;

            public function extractVariables($tagdata)
            {
                $this->tagdata = $tagdata;

                return [
                    'var_single' => [],
                    'var_pair' => ['fluid:content:title' => []],
                ];
            }
        };

        $functions = new class {
            public $calls = [];

            public function prep_conditionals($tagdata, $cond)
            {
                $this->calls[] = ['tagdata' => $tagdata, 'cond' => $cond];

                return $tagdata;
            }
        };

        $tagMock = new class {
            public $parseCalls = [];

            public function parse($field, $meta = [])
            {
                $this->parseCalls[] = ['field' => $field, 'meta' => $meta];

                return '[parsed:' . $meta['fluid:content:current_field_name'] . ']';
            }

            public function setTag($tag)
            {
                return $this;
            }
        };

        ee()->setMock('Variables/Parser', $variablesParser);
        ee()->setMock('functions', $functions);
        ee()->setMock('fluid_field:Tag', $tagMock);
        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock([]));

        $fluidField = $this->makeFluidFieldDouble(101, 'text', 'title');
        $fluidField->entry_id = 5;
        $fluidField->fluid_field_id = 11;
        $fluidField->group = null;
        $fluidField->order = 1;
        $fluidField->field_data_id = 200;
        $fluidField->setFieldData(['field_id_101' => 'alpha']);

        $parser = $this->makeParser();
        $this->setPrivateProperty($parser, '_prefix', 'fluid:');
        $this->setPrivateProperty($parser, 'fluid_fields', [11 => 'content']);
        $this->setPrivateProperty($parser, 'data', new Collection([$fluidField]));

        $result = $parser->parse(
            ['entry_id' => 5, 'title' => 'Row'],
            11,
            [],
            '{fluid:content:title}Body{/fluid:content:title}'
        );

        $this->assertSame('[parsed:title]', $result);
        $this->assertSame('{fluid:content:title}Body{/fluid:content:title}', $variablesParser->tagdata);
        $this->assertCount(2, $functions->calls);
        $this->assertCount(1, $tagMock->parseCalls);
        $this->assertSame(1, $tagMock->parseCalls[0]['meta']['fluid:content:count']);
        $this->assertSame(5, $tagMock->parseCalls[0]['field']->items['row']['entry_id']);
        $this->assertSame('alpha', $tagMock->parseCalls[0]['field']->items['row']['field_id_101']);
    }

    public function testParseAddsEvaluatedSingleVariablesAndSkipsUnsupportedSingles(): void
    {
        $variablesParser = new class {
            public function extractVariables($tagdata)
            {
                return [
                    'var_single' => [
                        'fluid:content:length' => 'fluid:content:count type="text"',
                        'fluid:content:unknown_calc' => 'fluid:content:unknown type="text"',
                    ],
                    'var_pair' => [
                        'fluid:content:title' => [],
                        'fluid:content:summary' => [],
                    ],
                ];
            }

            public function parseVariableProperties($var)
            {
                if (strpos($var, ':count ') !== false) {
                    return [
                        'params' => ['type' => 'text'],
                        'modifier' => 'count',
                    ];
                }

                return [
                    'params' => ['type' => 'text'],
                    'modifier' => 'unsupported',
                ];
            }
        };

        $functions = new class {
            public function prep_conditionals($tagdata, $cond)
            {
                return $tagdata;
            }
        };

        $tagMock = new class {
            public $parseCalls = [];

            public function parse($field, $meta = [])
            {
                $this->parseCalls[] = ['field' => $field, 'meta' => $meta];

                return '[row:' . $meta['fluid:content:count'] . ']';
            }

            public function setTag($tag)
            {
                return $this;
            }
        };

        ee()->setMock('Variables/Parser', $variablesParser);
        ee()->setMock('functions', $functions);
        ee()->setMock('fluid_field:Tag', $tagMock);
        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock([]));

        $title = $this->makeFluidFieldDouble(1, 'text', 'title');
        $title->entry_id = 5;
        $title->fluid_field_id = 11;
        $title->group = null;
        $title->order = 1;
        $title->field_data_id = 101;

        $summary = $this->makeFluidFieldDouble(2, 'text', 'summary');
        $summary->entry_id = 5;
        $summary->fluid_field_id = 11;
        $summary->group = null;
        $summary->order = 2;
        $summary->field_data_id = 102;

        $parser = $this->makeParser();
        $this->setPrivateProperty($parser, '_prefix', 'fluid:');
        $this->setPrivateProperty($parser, 'fluid_fields', [11 => 'content']);
        $this->setPrivateProperty($parser, 'data', new Collection([$title, $summary]));

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{fluid:content:title}Title{/fluid:content:title}{fluid:content:summary}Summary{/fluid:content:summary}'
        );

        $this->assertSame('[row:1][row:2]', $result);
        $this->assertCount(2, $tagMock->parseCalls);
        $this->assertSame(1, $tagMock->parseCalls[0]['meta']['fluid:content:length']);
        $this->assertSame(2, $tagMock->parseCalls[1]['meta']['fluid:content:length']);
        $this->assertArrayNotHasKey('fluid:content:unknown_calc', $tagMock->parseCalls[0]['meta']);
        $this->assertArrayNotHasKey('fluid:content:unknown_calc', $tagMock->parseCalls[1]['meta']);
    }

    public function testParseSkipsEvaluatingSingleVariableWhenMetaKeyAlreadyExists(): void
    {
        $variablesParser = new class {
            public function extractVariables($tagdata)
            {
                return [
                    'var_single' => [
                        'fluid:content:count' => 'fluid:content:count',
                    ],
                    'var_pair' => [
                        'fluid:content:title' => [],
                    ],
                ];
            }

            public function parseVariableProperties()
            {
                throw new \RuntimeException('parseVariableProperties should not be called for existing meta keys');
            }
        };

        $functions = new class {
            public function prep_conditionals($tagdata, $cond)
            {
                return $tagdata;
            }
        };

        $tagMock = new class {
            public $parseCalls = [];

            public function parse($field, $meta = [])
            {
                $this->parseCalls[] = ['field' => $field, 'meta' => $meta];

                return '[count:' . $meta['fluid:content:count'] . ']';
            }

            public function setTag($tag)
            {
                return $this;
            }
        };

        ee()->setMock('Variables/Parser', $variablesParser);
        ee()->setMock('functions', $functions);
        ee()->setMock('fluid_field:Tag', $tagMock);
        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock([]));

        $field = $this->makeFluidFieldDouble(1, 'text', 'title');
        $field->entry_id = 5;
        $field->fluid_field_id = 11;
        $field->group = null;
        $field->order = 1;
        $field->field_data_id = 101;

        $parser = $this->makeParser();
        $this->setPrivateProperty($parser, '_prefix', 'fluid:');
        $this->setPrivateProperty($parser, 'fluid_fields', [11 => 'content']);
        $this->setPrivateProperty($parser, 'data', new Collection([$field]));

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{fluid:content:title}Title{/fluid:content:title}'
        );

        $this->assertSame('[count:1]', $result);
        $this->assertCount(1, $tagMock->parseCalls);
    }

    public function testParseSetsPrevAndNextFieldNamesAcrossGroupBoundaries(): void
    {
        $variablesParser = new class {
            public function extractVariables($tagdata)
            {
                return [
                    'var_single' => [],
                    'var_pair' => [
                        'fluid:content:first' => [],
                        'fluid:content:second' => [],
                    ],
                ];
            }
        };

        $functions = new class {
            public function prep_conditionals($tagdata, $cond)
            {
                return $tagdata;
            }
        };

        $tagMock = new class {
            public $parseCalls = [];

            public function parse($field, $meta = [])
            {
                $this->parseCalls[] = ['field' => $field, 'meta' => $meta];

                return '[' . $meta['fluid:content:current_field_name'] . ']';
            }

            public function setTag($tag)
            {
                return $this;
            }
        };

        ee()->setMock('Variables/Parser', $variablesParser);
        ee()->setMock('functions', $functions);
        ee()->setMock('fluid_field:Tag', $tagMock);
        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock([]));

        $first = $this->makeFluidFieldDouble(1, 'text', 'first');
        $first->entry_id = 5;
        $first->fluid_field_id = 11;
        $first->group = null;
        $first->order = 1;
        $first->field_data_id = 101;

        $second = $this->makeFluidFieldDouble(2, 'textarea', 'second');
        $second->entry_id = 5;
        $second->fluid_field_id = 11;
        $second->group = null;
        $second->order = 2;
        $second->field_data_id = 102;

        $parser = $this->makeParser();
        $this->setPrivateProperty($parser, '_prefix', 'fluid:');
        $this->setPrivateProperty($parser, 'fluid_fields', [11 => 'content']);
        $this->setPrivateProperty($parser, 'data', new Collection([$first, $second]));

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{fluid:content:first}{fluid:content:second}'
        );

        $this->assertSame('[first][second]', $result);
        $this->assertCount(2, $tagMock->parseCalls);
        $this->assertNull($tagMock->parseCalls[0]['meta']['fluid:content:prev_field_name']);
        $this->assertSame('second', $tagMock->parseCalls[0]['meta']['fluid:content:next_field_name']);
        $this->assertSame('first', $tagMock->parseCalls[1]['meta']['fluid:content:prev_field_name']);
        $this->assertNull($tagMock->parseCalls[1]['meta']['fluid:content:next_field_name']);
        $this->assertSame(1, $tagMock->parseCalls[0]['meta']['fluid:content:first']);
        $this->assertSame(0, $tagMock->parseCalls[0]['meta']['fluid:content:last']);
        $this->assertSame(0, $tagMock->parseCalls[1]['meta']['fluid:content:first']);
        $this->assertSame(1, $tagMock->parseCalls[1]['meta']['fluid:content:last']);
    }

    public function testParseFallsBackWhenGroupedOutputHasNoFieldsChunksAndPrioritizesRelationshipTagReplacement(): void
    {
        $variablesParser = new class {
            public function extractVariables($tagdata)
            {
                return [
                    'var_single' => [],
                    'var_pair' => [
                        'fluid:content:hero' => [],
                    ],
                ];
            }
        };

        $functions = new class {
            public function prep_conditionals($tagdata, $cond)
            {
                return $tagdata;
            }
        };

        $tagMock = new class {
            public $parseCalls = [];
            public $setTagCalls = [];
            private $currentTag = null;
            private $output = '{if fluid:content:hero}1|{hero:title}|{hero:related}{/if}';

            public function parse($field, $meta = [])
            {
                $this->parseCalls[] = ['field' => $field, 'meta' => $meta, 'tag' => $this->currentTag];

                $this->output = str_replace(
                    '{' . $this->currentTag . '}',
                    '[tag:' . $this->currentTag . ']',
                    $this->output
                );

                return $this->output;
            }

            public function setTag($tag)
            {
                $this->currentTag = $tag;
                $this->setTagCalls[] = $tag;

                return $this;
            }
        };

        ee()->setMock('Variables/Parser', $variablesParser);
        ee()->setMock('functions', $functions);
        ee()->setMock('fluid_field:Tag', $tagMock);
        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock([]));

        $title = $this->makeFluidFieldDouble(1, 'text', 'title');
        $title->entry_id = 5;
        $title->fluid_field_id = 11;
        $title->group = 1;
        $title->order = 1;
        $title->field_data_id = 301;
        $title->ChannelFieldGroup = (object) ['group_name' => 'Hero Group', 'short_name' => 'hero'];

        $related = $this->makeFluidFieldDouble(2, 'relationship', 'related');
        $related->entry_id = 5;
        $related->fluid_field_id = 11;
        $related->group = 1;
        $related->order = 2;
        $related->field_data_id = 302;
        $related->ChannelFieldGroup = (object) ['group_name' => 'Hero Group', 'short_name' => 'hero'];

        $summary = $this->makeFluidFieldDouble(3, 'text', 'summary');
        $summary->entry_id = 5;
        $summary->fluid_field_id = 11;
        $summary->group = 1;
        $summary->order = 3;
        $summary->field_data_id = 303;
        $summary->ChannelFieldGroup = (object) ['group_name' => 'Hero Group', 'short_name' => 'hero'];

        $parser = $this->makeParser();
        $this->setPrivateProperty($parser, '_prefix', 'fluid:');
        $this->setPrivateProperty($parser, 'fluid_fields', [11 => 'content']);
        $this->setPrivateProperty($parser, 'data', new Collection([$title, $related, $summary]));

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{fluid:content:hero}{fluid:content:count_group}|{hero:title}|{hero:related}{/fluid:content:hero}'
        );

        $this->assertStringContainsString('1|[tag:hero:title]|[tag:hero:related]', $result);
        $this->assertSame('hero:related', $tagMock->setTagCalls[0]);
        $this->assertContains('hero:title', $tagMock->setTagCalls);
        $this->assertCount(2, $tagMock->setTagCalls);
        $this->assertCount(2, $tagMock->parseCalls);
    }

    public function testOverrideWithPreviewDataBuildsPreviewRowsAndKeepsGroupedOrdering(): void
    {
        $existingGroupMap = [
            50 => (object) ['group' => 9],
        ];

        $queryMock = new class($existingGroupMap) {
            public $filters = [];
            private $existingGroupMap;

            public function __construct(array $existingGroupMap)
            {
                $this->existingGroupMap = $existingGroupMap;
            }

            public function filter(...$args)
            {
                $this->filters[] = $args;

                return $this;
            }

            public function all()
            {
                return new class($this->existingGroupMap) {
                    private $existingGroupMap;

                    public function __construct(array $existingGroupMap)
                    {
                        $this->existingGroupMap = $existingGroupMap;
                    }

                    public function indexBy($column)
                    {
                        if ($column !== 'id') {
                            throw new \RuntimeException('Unexpected index column');
                        }

                        return $this->existingGroupMap;
                    }
                };
            }
        };

        $modelMock = new class($queryMock) {
            public $resources = [];
            public $makeResources = [];
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                $this->resources[] = $resource;

                return $this->queryMock;
            }

            public function make($resource)
            {
                $this->makeResources[] = $resource;

                return new class {
                    public $id;
                    public $fluid_field_id;
                    public $entry_id;
                    public $field_id;
                    public $field_group_id;
                    public $group;
                    public $order;
                    public $field_data_id;
                    private $fieldData;

                    public function setId($id)
                    {
                        $this->id = $id;
                    }

                    public function setFieldData(array $values)
                    {
                        $this->fieldData = new class($values) {
                            private $values;

                            public function __construct(array $values)
                            {
                                $this->values = $values;
                            }

                            public function getValues()
                            {
                                return $this->values;
                            }
                        };
                    }

                    public function getFieldData()
                    {
                        return $this->fieldData;
                    }
                };
            }
        };

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return [
                    'entry_id' => 7,
                    'field_id_11' => [
                        'fields' => [
                            'new_field_0' => [],
                            'field_50' => [
                                'row' => ['field_id_101' => 'alpha'],
                                'field_group_id_2' => 2,
                            ],
                            'new_field_for_group_9' => [
                                'row' => ['field_id_102' => 'beta'],
                                'field_group_id_2' => 2,
                            ],
                            'new_field_for_group_10' => [
                                'row' => ['field_id_103' => 'gamma'],
                                'field_group_id_2' => 2,
                            ],
                        ],
                    ],
                ];
            }
        });
        ee()->setMock('Model', $modelMock);

        $parser = $this->makeParser();

        $previewExisting = (object) ['entry_id' => 7];
        $persisted = (object) ['entry_id' => 9, 'name' => 'persisted'];
        $result = $parser->overrideWithPreviewData(new Collection([$previewExisting, $persisted]), [11]);

        $rows = array_values($result->asArray());
        $this->assertSame($persisted, $rows[0]);
        $this->assertCount(4, $rows);

        $first = $rows[1];
        $second = $rows[2];
        $third = $rows[3];

        $this->assertSame(7, $first->entry_id);
        $this->assertSame(11, $first->fluid_field_id);
        $this->assertSame(101, $first->field_id);
        $this->assertSame(2, $first->field_group_id);
        $this->assertSame(1, $first->group);
        $this->assertSame(1, $first->order);
        $this->assertSame(1, $first->field_data_id);
        $this->assertSame(7, $first->getFieldData()->getValues()['entry_id']);
        $this->assertSame('alpha', $first->getFieldData()->getValues()['field_id_101']);

        $this->assertSame(102, $second->field_id);
        $this->assertSame(1, $second->group);
        $this->assertSame(2, $second->order);
        $this->assertSame(2, $second->field_data_id);

        $this->assertSame(103, $third->field_id);
        $this->assertSame(2, $third->group);
        $this->assertSame(3, $third->order);
        $this->assertSame(3, $third->field_data_id);

        $this->assertSame(['fluid_field:FluidField'], $modelMock->resources);
        $this->assertSame(
            [
                ['fluid_field_id', 'IN', [11]],
                ['entry_id', 7],
            ],
            $queryMock->filters
        );
        $this->assertSame(
            ['fluid_field:FluidField', 'fluid_field:FluidField', 'fluid_field:FluidField'],
            $modelMock->makeResources
        );
    }

    public function testOverrideWithPreviewDataHandlesNullFieldGroupIdsAndGroupKeyChanges(): void
    {
        $existingGroupMap = [
            50 => (object) ['group' => 9],
            51 => (object) ['group' => 10],
        ];

        $queryMock = new class($existingGroupMap) {
            public $filters = [];
            private $existingGroupMap;

            public function __construct(array $existingGroupMap)
            {
                $this->existingGroupMap = $existingGroupMap;
            }

            public function filter(...$args)
            {
                $this->filters[] = $args;

                return $this;
            }

            public function all()
            {
                return new class($this->existingGroupMap) {
                    private $existingGroupMap;

                    public function __construct(array $existingGroupMap)
                    {
                        $this->existingGroupMap = $existingGroupMap;
                    }

                    public function indexBy($column)
                    {
                        if ($column !== 'id') {
                            throw new \RuntimeException('Unexpected index column');
                        }

                        return $this->existingGroupMap;
                    }
                };
            }
        };

        $modelMock = new class($queryMock) {
            public $resources = [];
            public $makeResources = [];
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                $this->resources[] = $resource;

                return $this->queryMock;
            }

            public function make($resource)
            {
                $this->makeResources[] = $resource;

                return new class {
                    public $id;
                    public $fluid_field_id;
                    public $entry_id;
                    public $field_id;
                    public $field_group_id;
                    public $group;
                    public $order;
                    public $field_data_id;
                    private $fieldData;

                    public function setId($id)
                    {
                        $this->id = $id;
                    }

                    public function setFieldData(array $values)
                    {
                        $this->fieldData = new class($values) {
                            private $values;

                            public function __construct(array $values)
                            {
                                $this->values = $values;
                            }

                            public function getValues()
                            {
                                return $this->values;
                            }
                        };
                    }

                    public function getFieldData()
                    {
                        return $this->fieldData;
                    }
                };
            }
        };

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return [
                    'entry_id' => 7,
                    'field_id_11' => [
                        'fields' => [
                            'field_50' => [
                                'row' => ['field_id_101' => 'alpha'],
                                'field_group_id_2' => 2,
                            ],
                            'field_51' => [
                                'row' => ['field_id_102' => 'beta'],
                                'field_group_id_2' => 2,
                            ],
                            'new_field_for_group_10' => [
                                'row' => ['field_id_103' => 'gamma'],
                            ],
                            'new_field_for_group_9' => [
                                'row' => ['field_id_104' => 'delta'],
                            ],
                        ],
                    ],
                ];
            }
        });
        ee()->setMock('Model', $modelMock);

        $previewExisting = (object) ['entry_id' => 7];
        $persisted = (object) ['entry_id' => 9, 'name' => 'persisted'];

        $parser = $this->makeParser();
        $result = $parser->overrideWithPreviewData(new Collection([$previewExisting, $persisted]), [11]);

        $rows = array_values($result->asArray());
        $previewRows = array_slice($rows, 1);

        $this->assertCount(5, $rows);
        $this->assertSame($persisted, $rows[0]);
        $this->assertSame([1, 2, 3, 4], array_map(function ($row) {
            return $row->group;
        }, $previewRows));
        $this->assertSame([2, 2, null, null], array_map(function ($row) {
            return $row->field_group_id;
        }, $previewRows));
        $this->assertSame(
            [
                'field_id_11,field_50',
                'field_id_11,field_51',
                'field_id_11,new_field_for_group_10',
                'field_id_11,new_field_for_group_9',
            ],
            array_map(function ($row) {
                return $row->id;
            }, $previewRows)
        );
        $this->assertSame([101, 102, 103, 104], array_map(function ($row) {
            return $row->field_id;
        }, $previewRows));
        $this->assertSame([1, 2, 3, 4], array_map(function ($row) {
            return $row->order;
        }, $previewRows));
        $this->assertSame(['fluid_field:FluidField'], $modelMock->resources);
        $this->assertSame(
            [
                ['fluid_field_id', 'IN', [11]],
                ['entry_id', 7],
            ],
            $queryMock->filters
        );
        $this->assertSame(
            ['fluid_field:FluidField', 'fluid_field:FluidField', 'fluid_field:FluidField', 'fluid_field:FluidField'],
            $modelMock->makeResources
        );
    }

    public function testOverrideWithPreviewDataSkipsRowsWithoutFieldIdPayload(): void
    {
        $queryMock = new class {
            public $filters = [];

            public function filter(...$args)
            {
                $this->filters[] = $args;

                return $this;
            }

            public function all()
            {
                return new class {
                    public function indexBy($column)
                    {
                        if ($column !== 'id') {
                            throw new \RuntimeException('Unexpected index column');
                        }

                        return [];
                    }
                };
            }
        };

        $modelMock = new class($queryMock) {
            public $resources = [];
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                $this->resources[] = $resource;

                return $this->queryMock;
            }

            public function make()
            {
                throw new \RuntimeException('Model::make should not run without any field_id_* payload');
            }
        };

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return [
                    'entry_id' => 7,
                    'field_id_11' => [
                        'fields' => [
                            'new_field_for_group_9' => [
                                'row' => ['foo' => 'bar'],
                                'field_group_id_2' => 2,
                            ],
                        ],
                    ],
                ];
            }
        });
        ee()->setMock('Model', $modelMock);

        $previewRow = (object) ['entry_id' => 7];
        $keptRow = (object) ['entry_id' => 9, 'name' => 'keep'];

        $parser = $this->makeParser();
        $result = $parser->overrideWithPreviewData(new Collection([$previewRow, $keptRow]), [11]);

        $this->assertSame(['fluid_field:FluidField'], $modelMock->resources);
        $this->assertSame(
            [
                ['fluid_field_id', 'IN', [11]],
                ['entry_id', 7],
            ],
            $queryMock->filters
        );
        $this->assertSame([$keptRow], array_values($result->asArray()));
    }

    public function testOverrideWithPreviewDataCreatesUngroupedPreviewRowsForUnknownFieldKeys(): void
    {
        $queryMock = new class {
            public function filter()
            {
                return $this;
            }

            public function all()
            {
                return new class {
                    public function indexBy($column)
                    {
                        if ($column !== 'id') {
                            throw new \RuntimeException('Unexpected index column');
                        }

                        return [];
                    }
                };
            }
        };

        $modelMock = new class($queryMock) {
            public $makeResources = [];
            private $queryMock;

            public function __construct($queryMock)
            {
                $this->queryMock = $queryMock;
            }

            public function get($resource)
            {
                if ($resource !== 'fluid_field:FluidField') {
                    throw new \RuntimeException('Unexpected model resource: ' . $resource);
                }

                return $this->queryMock;
            }

            public function make($resource)
            {
                $this->makeResources[] = $resource;

                return new class {
                    public $id;
                    public $fluid_field_id;
                    public $entry_id;
                    public $field_id;
                    public $field_group_id;
                    public $group;
                    public $order;
                    public $field_data_id;
                    private $fieldData;

                    public function setId($id)
                    {
                        $this->id = $id;
                    }

                    public function setFieldData(array $values)
                    {
                        $this->fieldData = new class($values) {
                            private $values;

                            public function __construct(array $values)
                            {
                                $this->values = $values;
                            }

                            public function getValues()
                            {
                                return $this->values;
                            }
                        };
                    }

                    public function getFieldData()
                    {
                        return $this->fieldData;
                    }
                };
            }
        };

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return [
                    'entry_id' => 7,
                    'field_id_11' => [
                        'fields' => [
                            'field_95' => [
                                'row' => ['field_id_401' => 'alpha'],
                            ],
                            'field_96' => [
                                'row' => ['field_id_402' => 'beta'],
                            ],
                        ],
                    ],
                ];
            }
        });
        ee()->setMock('Model', $modelMock);

        $previewRow = (object) ['entry_id' => 7, 'name' => 'preview'];
        $keptRow = (object) ['entry_id' => 9, 'name' => 'keep'];

        $parser = $this->makeParser();
        $result = $parser->overrideWithPreviewData(new Collection([$previewRow, $keptRow]), [11]);

        $rows = array_values($result->asArray());

        $this->assertCount(3, $rows);
        $this->assertSame($keptRow, $rows[0]);
        $this->assertSame(['fluid_field:FluidField', 'fluid_field:FluidField'], $modelMock->makeResources);
        $this->assertSame('field_id_11,field_95', $rows[1]->id);
        $this->assertNull($rows[1]->field_group_id);
        $this->assertSame(1, $rows[1]->group);
        $this->assertSame('field_id_11,field_96', $rows[2]->id);
        $this->assertNull($rows[2]->field_group_id);
        $this->assertSame(2, $rows[2]->group);
    }

    public function testParseHandlesFieldGroupsChunksAndTagReplacementPass(): void
    {
        $fieldsChunk = '{fields fixed_order="title|body" order="desc"}{fluid:content:title}{fluid:content:body}{/fields}';

        $variablesParser = new class {
            public function extractVariables($tagdata)
            {
                return [
                    'var_single' => [],
                    'var_pair' => [
                        'fluid:content:hero' => [],
                    ],
                ];
            }
        };

        $functions = new class {
            public $calls = [];

            public function prep_conditionals($tagdata, $cond)
            {
                $this->calls[] = ['tagdata' => $tagdata, 'cond' => $cond];

                return $tagdata;
            }
        };

        $tagMock = new class {
            public $parseCalls = [];
            public $setTagCalls = [];
            private $currentTag = '';

            public function parse($field, $meta = [])
            {
                $this->parseCalls[] = ['field' => $field, 'meta' => $meta, 'tag' => $this->currentTag];

                if (isset($meta['fluid:content:current_field_name'])) {
                    return '[' . $meta['fluid:content:current_field_name'] . ']';
                }

                return '{repl:' . $this->currentTag . '}';
            }

            public function setTag($tag)
            {
                $this->currentTag = $tag;
                $this->setTagCalls[] = $tag;

                return $this;
            }
        };

        ee()->setMock('Variables/Parser', $variablesParser);
        ee()->setMock('functions', $functions);
        ee()->setMock('fluid_field:Tag', $tagMock);
        ee()->setMock(
            'api_channel_fields',
            $this->buildApiChannelFieldsMock([
                [
                    null,
                    '{fluid:content:title}{fluid:content:body}',
                    ['fixed_order' => 'title|body', 'order' => 'desc'],
                    $fieldsChunk,
                ],
            ])
        );

        $title = $this->makeFluidFieldDouble(1, 'text', 'title');
        $title->entry_id = 5;
        $title->fluid_field_id = 11;
        $title->group = 1;
        $title->order = 1;
        $title->field_data_id = 100;
        $title->ChannelFieldGroup = (object) ['group_name' => 'Hero Group', 'short_name' => 'hero'];

        $body = $this->makeFluidFieldDouble(2, 'text', 'body');
        $body->entry_id = 5;
        $body->fluid_field_id = 11;
        $body->group = 1;
        $body->order = 2;
        $body->field_data_id = 101;
        $body->ChannelFieldGroup = (object) ['group_name' => 'Hero Group', 'short_name' => 'hero'];

        $parser = $this->makeParser();
        $this->setPrivateProperty($parser, '_prefix', 'fluid:');
        $this->setPrivateProperty($parser, 'fluid_fields', [11 => 'content']);
        $this->setPrivateProperty($parser, 'data', new Collection([$title, $body]));

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{fluid:content:hero}{fluid:content:current_group_name}|{hero:title}|' . $fieldsChunk . '{/fluid:content:hero}'
        );

        $this->assertSame('{repl:hero:title}', $result);
        $this->assertGreaterThanOrEqual(2, count($functions->calls));
        $this->assertSame('hero:title', $tagMock->setTagCalls[0]);
        $this->assertSame('body', $tagMock->parseCalls[0]['meta']['fluid:content:current_field_name']);
        $this->assertSame('title', $tagMock->parseCalls[1]['meta']['fluid:content:current_field_name']);
    }

    public function testParseHandlesComplexFixedOrderWithMixedFieldTypes(): void
    {
        $fieldsChunk = '{fields fixed_order="summary|title|related" order="desc"}{fluid:content:title}{fluid:content:related}{fluid:content:summary}{/fields}';

        $variablesParser = new class {
            public function extractVariables($tagdata)
            {
                return [
                    'var_single' => [],
                    'var_pair' => [
                        'fluid:content:hero' => [],
                    ],
                ];
            }
        };

        $functions = new class {
            public function prep_conditionals($tagdata, $cond)
            {
                return $tagdata;
            }
        };

        $tagMock = new class {
            public $parseCalls = [];
            public $setTagCalls = [];
            private $currentTag = '';
            private $output = '[related][title][summary]|{hero:title}|{hero:related}|{hero:summary}';

            public function parse($field, $meta = [])
            {
                $this->parseCalls[] = ['field' => $field, 'meta' => $meta, 'tag' => $this->currentTag];

                if (isset($meta['fluid:content:current_field_name'])) {
                    return '[' . $meta['fluid:content:current_field_name'] . ']';
                }

                $this->output = str_replace('{' . $this->currentTag . '}', '[repl:' . $this->currentTag . ']', $this->output);

                return $this->output;
            }

            public function setTag($tag)
            {
                $this->currentTag = $tag;
                $this->setTagCalls[] = $tag;

                return $this;
            }
        };

        ee()->setMock('Variables/Parser', $variablesParser);
        ee()->setMock('functions', $functions);
        ee()->setMock('fluid_field:Tag', $tagMock);
        ee()->setMock(
            'api_channel_fields',
            $this->buildApiChannelFieldsMock([
                [
                    null,
                    '{fluid:content:title}{fluid:content:related}{fluid:content:summary}',
                    ['fixed_order' => 'summary|title|related', 'order' => 'desc'],
                    $fieldsChunk,
                ],
            ])
        );

        $title = $this->makeFluidFieldDouble(1, 'text', 'title');
        $title->entry_id = 5;
        $title->fluid_field_id = 11;
        $title->group = 1;
        $title->order = 1;
        $title->field_data_id = 201;
        $title->ChannelFieldGroup = (object) ['group_name' => 'Hero Group', 'short_name' => 'hero'];

        $related = $this->makeFluidFieldDouble(2, 'relationship', 'related');
        $related->entry_id = 5;
        $related->fluid_field_id = 11;
        $related->group = 1;
        $related->order = 2;
        $related->field_data_id = 202;
        $related->ChannelFieldGroup = (object) ['group_name' => 'Hero Group', 'short_name' => 'hero'];

        $summary = $this->makeFluidFieldDouble(3, 'textarea', 'summary');
        $summary->entry_id = 5;
        $summary->fluid_field_id = 11;
        $summary->group = 1;
        $summary->order = 3;
        $summary->field_data_id = 203;
        $summary->ChannelFieldGroup = (object) ['group_name' => 'Hero Group', 'short_name' => 'hero'];

        $parser = $this->makeParser();
        $this->setPrivateProperty($parser, '_prefix', 'fluid:');
        $this->setPrivateProperty($parser, 'fluid_fields', [11 => 'content']);
        $this->setPrivateProperty($parser, 'data', new Collection([$title, $related, $summary]));

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{fluid:content:hero}' . $fieldsChunk . '|{hero:title}|{hero:related}|{hero:summary}{/fluid:content:hero}'
        );

        $fieldPasses = array_values(array_filter($tagMock->parseCalls, function ($call) {
            return isset($call['meta']['fluid:content:current_field_name']);
        }));

        $this->assertSame(
            ['related', 'title', 'summary'],
            array_map(function ($call) {
                return $call['meta']['fluid:content:current_field_name'];
            }, $fieldPasses)
        );
        $this->assertCount(3, $tagMock->setTagCalls);
        $this->assertSame('hero:related', $tagMock->setTagCalls[0]);
        $this->assertContains('hero:title', $tagMock->setTagCalls);
        $this->assertContains('hero:summary', $tagMock->setTagCalls);
        $this->assertSame(
            '[related][title][summary]|[repl:hero:title]|[repl:hero:related]|[repl:hero:summary]',
            $result
        );
    }

    private function makeParser(): \Fluid_field_parser
    {
        return new \Fluid_field_parser();
    }

    private function makeFluidFieldDouble(int $id, string $type, string $name): FluidField
    {
        return new FluidField($id, $type, $name);
    }

    private function buildApiChannelFieldsMock(array $pairs)
    {
        return new class($pairs) {
            private $pairs;

            public function __construct(array $pairs)
            {
                $this->pairs = $pairs;
            }

            public function get_pair_field($tagdata, $name)
            {
                return $this->pairs;
            }
        };
    }

    private function invokePrivateMethod($object, string $method, array $args = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($object, $args);
    }

    private function getPrivateProperty($object, string $property)
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }

    private function setPrivateProperty($object, string $property, $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
