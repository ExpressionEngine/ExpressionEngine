<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use ExpressionEngine\Addons\FluidField\Model\FluidField;
use ExpressionEngine\Service\Model\Collection;

require_once __DIR__ . '/../../../eeObjectMock.php';

class FluidFieldTestDouble
{
    public $ChannelField;
    public $ChannelFieldGroup;
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
        if (!defined('SYSPATH')) {
            define('SYSPATH', realpath(__DIR__ . '/../../../../../../system/') . '/');
        }

        if (!defined('BASEPATH')) {
            define('BASEPATH', SYSPATH . 'ee/legacy/');
        }

        if (!defined('LD')) {
            define('LD', '{');
        }

        if (!defined('RD')) {
            define('RD', '}');
        }

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

    public function testParseReturnsEmptyStringWhenTagdataIsEmpty(): void
    {
        $parser = $this->makeParser();

        $result = $parser->parse(['entry_id' => 1], 11, [], '');

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
