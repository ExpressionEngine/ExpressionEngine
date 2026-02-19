<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\RelationshipParser;

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/libraries/datastructures/Tree.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Exceptions.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Nodes.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Iterators.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/VariableFinder.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Tree_builder.php';

use PHPUnit\Framework\TestCase;

class RelationshipTreeBuilderShim extends \EE_relationship_tree_builder
{
    public $forceBuildTree = false;
    public $forcedRoot = null;
    public $forcePropagateIds = false;
    public $propagateReturn = [];

    protected function _build_tree($str)
    {
        if ($this->forceBuildTree) {
            return $this->forcedRoot;
        }

        return parent::_build_tree($str);
    }

    protected function _propagate_ids(\QueryNode $root, array $db_result)
    {
        if ($this->forcePropagateIds) {
            return $this->propagateReturn;
        }

        return parent::_propagate_ids($root, $db_result);
    }

    public function exposeBuildTreeInternal($str)
    {
        return parent::_build_tree($str);
    }

    public function exposeParseLeaves(array $leaves)
    {
        return parent::_parse_leaves($leaves);
    }

    public function exposePropagateIds(\QueryNode $root, array $db_result)
    {
        return parent::_propagate_ids($root, $db_result);
    }

    public function setUniqueIds(array $ids): void
    {
        $this->_unique_ids = $ids;
    }

    public function getUniqueIds(): array
    {
        return $this->_unique_ids;
    }
}

class RelationshipTreeBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();

        ee()->setMock('load', new class extends \eeSingletonLoadMock {
            public $models = [];

            public function model($name = '')
            {
                $this->models[] = $name;
            }
        });

        ee()->setMock('relationship_model', new class {
            public $calls = [];

            public function node_query($node, $entry_ids, $grid_field_id = null, $fluid_field_data_id = null)
            {
                $this->calls[] = [
                    'name' => $node->name(),
                    'entry_ids' => $entry_ids,
                    'grid_field_id' => $grid_field_id,
                    'fluid_field_data_id' => $fluid_field_data_id,
                ];

                return [];
            }
        });

        ee()->setMock('extensions', new class {
            public $end_script = false;

            public function active_hook($name)
            {
                return false;
            }

            public function call(...$args)
            {
                return $args[0] ?? null;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return [];
            }
        });

        ee()->setMock('Variables/Parser', new class {
            public function parseTagParameters($parameters)
            {
                return [];
            }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testBuildTreeReturnsNullWhenNoRelationshipTagsExist()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10]]);

        $this->assertNull($builder->build_tree([1], 'plain text with no tags'));
    }

    public function testBuildTreeMergesParentEntryIdsForNonRootQueryNodes()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10]]);

        $root = $builder->build_tree([1, 2], '{parents}{/parents}');

        $this->assertInstanceOf(\QueryNode::class, $root);
        $this->assertCount(2, ee()->relationship_model->calls);
        $this->assertSame([1, 2], ee()->relationship_model->calls[1]['entry_ids']);
    }

    public function testBuildTreeGridModeUsesEmptyInitialEntrySet()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10]], ['gridrel' => 90], 777);
        $builder->forceBuildTree = true;
        $builder->forcePropagateIds = true;
        $builder->propagateReturn = [99];
        $builder->forcedRoot = $this->makeQueryNode('__root__');

        $builder->build_tree([5], 'ignored');

        $this->assertSame([99], $builder->getUniqueIds());
    }

    public function testBuildTreeInternalInGridModeReturnsNullWhenNoGridTagsMatch()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10]], ['gridrel' => 90], 100);

        $this->assertNull($builder->exposeBuildTreeInternal('{rel}{/rel}'));
    }

    public function testBuildTreeInternalSkipsFluidContentTag()
    {
        $builder = $this->makeBuilder([1 => ['content' => 10]], [], null, 88);

        $root = $builder->exposeBuildTreeInternal('{content:title}');

        $this->assertInstanceOf(\QueryNode::class, $root);
        $this->assertCount(0, $root->children());
    }

    public function testBuildTreeInternalThrowsWhenNestedParentTagIsMissing()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10, 'child' => 11]]);

        $this->expectException(\EE_Relationship_exception::class);
        $this->expectExceptionMessage('no parent');
        $builder->exposeBuildTreeInternal('{rel:child}');
    }

    public function testBuildTreeInternalThrowsOnUnmatchedRelationshipTag()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10]]);

        $this->expectException(\EE_Relationship_exception::class);
        $this->expectExceptionMessage('Unmatched Relationship Tag');
        $builder->exposeBuildTreeInternal('{rel}');
    }

    public function testBuildTreeInternalSkipsShortcutTagsInsideOpenNodeAndCreatesSiblingQueryNode()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10, 'child' => 11]]);

        $root = $builder->exposeBuildTreeInternal(
            '{rel}{rel:title}{rel:child}{/rel:child}{/rel}{siblings}{/siblings}'
        );

        $children = $root->children();
        $this->assertCount(2, $children);
        $this->assertSame('rel', $children[0]->name());
        $this->assertCount(1, $children[0]->children());
        $this->assertSame('rel:child', $children[0]->children()[0]->name());
        $this->assertInstanceOf(\QueryNode::class, $children[1]);
        $this->assertSame('siblings', $children[1]->name());
    }

    public function testParseLeavesParsesStandardAndGridRowsAndSkipsInvalidRows()
    {
        $builder = $this->makeBuilder(
            [1 => ['rel' => 10, 'child' => 11]],
            ['gridrel' => 90],
            200
        );

        $leaves = [
            [
                'L0_field' => 10,
                'L0_id' => 2,
                'L0_parent' => 1,
                'L0_grid_col_id' => 0,
                'L1_field' => 11,
                'L1_id' => 5,
                'L1_parent' => 2,
            ],
            [
                'L0_field' => 90,
                'L0_id' => 8,
                'L0_parent' => 1,
                'L0_grid_col_id' => 77,
            ],
            [
                'L0_field' => 10,
                'L0_id' => 0,
                'L0_parent' => 1,
                'L0_grid_col_id' => 0,
            ],
            [
                'L0_field' => 999,
                'L0_id' => 4,
                'L0_parent' => 1,
                'L0_grid_col_id' => 0,
            ],
        ];

        $parsed = $builder->exposeParseLeaves($leaves);

        $this->assertSame(2, $parsed[0][10][1][0]['id']);
        $this->assertSame('rel', $parsed[0][10][1][0]['field']);
        $this->assertSame(5, $parsed[1][11][2][0]['id']);
        $this->assertSame('child', $parsed[1][11][2][0]['field']);
        $this->assertSame('gridrel', $parsed[0][90][1][0]['field']);
        $this->assertArrayNotHasKey(999, $parsed[0]);
    }

    public function testPropagateIdsBuildsPermutationsForPrefixedSiblingTag()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10]]);
        $root = $this->makeQueryNode('__root__');
        $node = $this->makeParseNode('rel:siblings', ['field_name' => 'siblings']);
        $root->add($node);
        $root->add_entry_id(10, [1, 2, 2]);

        $ids = $builder->exposePropagateIds($root, []);

        $entryMap = $node->entry_ids();
        $this->assertSame([2], array_values($entryMap[1]));
        $this->assertSame([1], array_values($entryMap[2]));
        $this->assertSame([], $ids);
    }

    public function testPropagateIdsHandlesParentsFieldFiltersGridFieldsAndRootSiblings()
    {
        $builder = $this->makeBuilder(
            [1 => ['rel' => 10, 'other' => 11]],
            ['gridrel' => 90],
            300
        );
        $root = $this->makeQueryNode('__root__');

        $parentsNode = $this->makeParseNode('parents', [
            'field_name' => 'parents',
            'params' => ['field' => 'rel|gridrel'],
        ]);
        $gridNode = $this->makeParseNode('gridrel', [
            'field_name' => 'gridrel',
            'in_grid' => true,
        ]);
        $siblingsNode = $this->makeParseNode('siblings', [
            'field_name' => 'siblings',
        ]);

        $root->add($parentsNode);
        $root->add($gridNode);
        $root->add($siblingsNode);

        $dbRows = [
            ['L0_field' => 10, 'L0_id' => 7, 'L0_parent' => 1, 'L0_grid_col_id' => 0],
            ['L0_field' => 90, 'L0_id' => 8, 'L0_parent' => 2, 'L0_grid_col_id' => 1],
            ['L0_field' => 10, 'L0_id' => 3, 'L0_parent' => 3, 'L0_grid_col_id' => 0],
            ['L0_field' => 10, 'L0_id' => 4, 'L0_parent' => 3, 'L0_grid_col_id' => 0],
        ];

        $result = $builder->exposePropagateIds($root, $dbRows);

        $this->assertSame([7], array_values($parentsNode->entry_ids()[1]));
        $this->assertSame([8], array_values($parentsNode->entry_ids()[2]));
        $this->assertSame([8], array_values($gridNode->entry_ids()[2]));
        $siblingIds = $siblingsNode->entry_ids();
        $this->assertContains(4, $siblingIds[3]);
        $this->assertNotContains(3, $siblingIds[3]);
        $this->assertContains(8, $result);
    }

    public function testGetParserBuildsLookupAndReplacesExistingLivePreviewEntry()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10]]);
        $builder->setUniqueIds([1, 2]);

        ee()->setMock('category_model', new class {
            public function get_entry_categories($ids)
            {
                return [1 => [['cat_id' => 1]], 2 => [['cat_id' => 2]]];
            }
        });

        ee()->setMock('Model', new class {
            public function make($name)
            {
                return new class {
                    public function getFields()
                    {
                        return [];
                    }
                };
            }

            public function get($name, $ids)
            {
                return new class {
                    public function with($with)
                    {
                        return $this;
                    }

                    public function all($useFacade)
                    {
                        return $this;
                    }

                    public function getModChannelResultsArray($disabled)
                    {
                        return [
                            ['entry_id' => 1, 'title' => 'old'],
                            ['entry_id' => 2, 'title' => 'keep'],
                        ];
                    }
                };
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return ['entry_id' => 1, 'title' => 'preview'];
            }
        });

        $parser = $builder->get_parser(new \EE_TreeNode('__root__'));

        $this->assertInstanceOf(\EE_Relationship_data_parser::class, $parser);
        $entriesProperty = new \ReflectionProperty(\EE_Relationship_data_parser::class, '_entries');
        $entriesProperty->setAccessible(true);
        $entries = $entriesProperty->getValue($parser);
        $this->assertSame('preview', $entries[1]['title']);
        $this->assertSame('keep', $entries[2]['title']);
        $this->assertContains('category_model', ee()->load->models);
    }

    public function testGetParserCustomFieldModeNormalizesNonArrayResultsAndCanEndScriptViaHook()
    {
        $builder = $this->makeBuilder([1 => ['rel' => 10]]);
        $builder->setUniqueIds([7]);

        ee()->setMock('Model', new class {
            public function make($name)
            {
                return new class($name) {
                    private $name;

                    public function __construct($name)
                    {
                        $this->name = $name;
                    }

                    public function getFields()
                    {
                        if ($this->name === 'ChannelEntry') {
                            return ['entry_id', 'title'];
                        }
                        if ($this->name === 'Channel') {
                            return ['channel_id'];
                        }
                        return ['member_id'];
                    }
                };
            }

            public function get($name, $ids)
            {
                return new class {
                    public function with($with)
                    {
                        return $this;
                    }

                    public function fields($field)
                    {
                        return $this;
                    }

                    public function all($useFacade)
                    {
                        return $this;
                    }

                    public function getModChannelResultsArray($disabled)
                    {
                        return 'not-an-array';
                    }
                };
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return ['entry_id' => 99, 'title' => 'draft'];
            }
        });

        ee()->setMock('extensions', new class {
            public $end_script = true;
            public $lastLookup = null;

            public function active_hook($name)
            {
                return $name === 'relationships_query_result';
            }

            public function call($hook, $entryLookup)
            {
                $this->lastLookup = $entryLookup;
                return $entryLookup;
            }
        });

        $result = $builder->get_parser(
            new \EE_TreeNode('__root__'),
            ['relationship_custom_fields', 'relationship_categories']
        );

        $this->assertNull($result);
        $this->assertArrayHasKey(99, ee()->extensions->lastLookup);
    }

    private function makeBuilder(
        array $relationshipFields,
        array $gridRelationshipIds = [],
        $gridFieldId = null,
        $fluidFieldDataId = null
    ): RelationshipTreeBuilderShim {
        return new RelationshipTreeBuilderShim(
            $relationshipFields,
            $gridRelationshipIds,
            $gridFieldId,
            $fluidFieldDataId
        );
    }

    private function makeParseNode(string $name, array $payload = []): \ParseNode
    {
        $defaults = [
            'params' => [],
            'entry_ids' => [],
            'in_cond' => false,
            'shortcut' => false,
            'open_tag' => '{' . $name . '}',
            'field_name' => $name,
            'in_grid' => false,
            'in_fluid_field' => false,
            'tag_info' => [],
        ];

        return new \ParseNode($name, array_merge($defaults, $payload));
    }

    private function makeQueryNode(string $name, array $payload = []): \QueryNode
    {
        $defaults = [
            'params' => [],
            'entry_ids' => [],
            'in_cond' => false,
            'shortcut' => false,
            'open_tag' => '{' . $name . '}',
            'field_name' => $name,
            'in_grid' => false,
            'in_fluid_field' => false,
            'tag_info' => [],
        ];

        return new \QueryNode($name, array_merge($defaults, $payload));
    }
}
