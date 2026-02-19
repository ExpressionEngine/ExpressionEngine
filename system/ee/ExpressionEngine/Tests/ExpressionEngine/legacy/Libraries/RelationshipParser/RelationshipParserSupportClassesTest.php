<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\RelationshipParser;

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/libraries/datastructures/Tree.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Nodes.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Iterators.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/VariableFinder.php';

use PHPUnit\Framework\TestCase;

class RelationshipParserSupportClassesTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }

            public function call(...$args)
            {
                return $args[0] ?? null;
            }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testAddEntryIdMergesValuesAndAvoidsDuplicates()
    {
        $node = $this->makeParseNode('rel');
        $node->add_entry_id(1, 2);
        $node->add_entry_id(1, 2);
        $node->add_entry_id(1, [3, 4]);
        $node->add_entry_id(2, 0);

        $ids = $node->entry_ids();

        $this->assertSame([2, 3, 4], $ids[1]);
        $this->assertSame([], $ids[2]);
    }

    public function testEntryIdsAppliesPositiveEntryIdFilter()
    {
        $node = $this->makeParseNode('rel', ['params' => ['entry_id' => '2|3']]);
        $node->add_entry_id(1, [1, 2, 2, 3, 4]);

        $this->assertSame([2, 3], array_values($node->entry_ids()[1]));
    }

    public function testEntryIdsAppliesNegatedEntryIdFilter()
    {
        $node = $this->makeParseNode('rel', ['params' => ['entry_id' => 'not 2|3']]);
        $node->add_entry_id(1, [1, 2, 2, 3, 4]);

        $this->assertSame([1, 4], array_values($node->entry_ids()[1]));
    }

    public function testCallbackTagdataLoopStartReturnsOriginalTagdataWhenHookIsInactive()
    {
        $node = $this->makeParseNode('rel');

        $this->assertSame('x', $node->callback_tagdata_loop_start('x', ['entry_id' => 5]));
    }

    public function testCallbackTagdataLoopStartUsesHookResultWhenHookIsActive()
    {
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return $name === 'relationship_entries_tagdata';
            }

            public function call($hook, $tagdata, $row, $node)
            {
                return $tagdata . '-' . $row['entry_id'];
            }
        });

        $node = $this->makeParseNode('rel');
        $this->assertSame('x-7', $node->callback_tagdata_loop_start('x', ['entry_id' => 7]));
    }

    public function testCallbackTagdataLoopEndParsesAllChildNodes()
    {
        $parent = $this->makeParseNode('rel');
        $childA = $this->makeParseNode('child_a');
        $childB = $this->makeParseNode('child_b');
        $parent->add($childA);
        $parent->add($childB);

        $parser = new class {
            public $calls = [];

            public function parse_node($child, $entryId, $tagdata)
            {
                $this->calls[] = [$child->name(), $entryId];
                return $tagdata . '|' . $child->name();
            }
        };
        $parent->parser = $parser;

        $result = $parent->callback_tagdata_loop_end('base', ['entry_id' => 44]);

        $this->assertSame('base|child_a|child_b', $result);
        $this->assertSame([['child_a', 44], ['child_b', 44]], $parser->calls);
    }

    public function testQueryNodeTracksClosureChildrenAcrossParseNodeParent()
    {
        $queryRoot = $this->makeQueryNode('q_root');
        $middle = $this->makeParseNode('middle');
        $queryLeaf = $this->makeQueryNode('q_leaf');

        $queryRoot->add($middle);
        $middle->add($queryLeaf);

        $closure = $queryRoot->closureChildren();
        $this->assertCount(1, $closure);
        $this->assertSame($queryLeaf, $closure[0]);

        $queryRoot->addClosurePath($queryLeaf);
        $this->assertCount(2, $queryRoot->closureChildren());
    }

    public function testParseNodeIteratorHasChildrenReturnsFalseForLeafNode()
    {
        $root = $this->makeParseNode('root');
        $iterator = new \ParseNodeIterator([$root]);
        $iterator->rewind();

        $this->assertFalse($iterator->hasChildren());
    }

    public function testParseNodeIteratorHasChildrenSkipsPureQueryNodeChildren()
    {
        $root = $this->makeParseNode('root');
        $root->add($this->makeQueryNode('query_only'));
        $iterator = new \ParseNodeIterator([$root]);
        $iterator->rewind();

        $this->assertFalse($iterator->hasChildren());
    }

    public function testQueryNodeIteratorHasChildrenAndGetChildrenForClosurePath()
    {
        $queryRoot = $this->makeQueryNode('q_root');
        $queryLeaf = $this->makeQueryNode('q_leaf');
        $queryRoot->addClosurePath($queryLeaf);

        $iterator = new \QueryNodeIterator([$queryRoot]);
        $iterator->rewind();

        $this->assertTrue($iterator->hasChildren());

        $childrenIterator = $iterator->getChildren();
        $childrenIterator->rewind();
        $this->assertSame($queryLeaf, $childrenIterator->current());
    }

    public function testQueryNodeIteratorHasChildrenReturnsFalseForNonQueryNode()
    {
        $iterator = new \QueryNodeIterator([$this->makeParseNode('parse')]);
        $iterator->rewind();

        $this->assertFalse($iterator->hasChildren());
    }

    public function testVariableFinderFindInTagsMatchesAndSupportsNoMatches()
    {
        $finder = new \VariableFinder('foo(?::bar)?');
        $matches = $finder->findInTags('x {foo} y {foo:bar}');

        $this->assertCount(2, $matches);
        $this->assertSame('tag', $matches[0][2]);
        $this->assertSame([], $finder->findInTags('x {bar} y'));
    }

    public function testVariableFinderFindInConditionalsMatchesAndSkipsNonMatchingVariables()
    {
        $finder = new \VariableFinder('foo');
        $matches = $finder->findInConditionals('{if foo == "x"}OK{/if}{if bar}NO{/if}');

        $this->assertCount(1, $matches);
        $this->assertSame('conditional', $matches[0][2]);
        $this->assertIsInt($matches[0][1]);
    }

    public function testVariableFinderLineToCharacterOffsetsCoversOffsetBranches()
    {
        $finder = new \VariableFinder('foo');
        $method = new \ReflectionMethod(\VariableFinder::class, 'lineToCharacterOffsets');
        $method->setAccessible(true);

        $variables = [
            [['foo'], 2, 'conditional'],
            [['missing'], 10, 'conditional'],
        ];

        $result = $method->invoke($finder, $variables, "\nfoo\nbar");

        $this->assertSame(1, $result[0][1]);
        $this->assertSame(10, $result[1][1]);
    }

    private function makeParseNode(string $name, array $payload = []): \ParseNode
    {
        $defaults = [
            'params' => [],
            'entry_ids' => [],
            'in_cond' => false,
            'shortcut' => '',
            'open_tag' => '{' . $name . '}',
            'field_name' => $name,
        ];

        return new \ParseNode($name, array_merge($defaults, $payload));
    }

    private function makeQueryNode(string $name, array $payload = []): \QueryNode
    {
        $defaults = [
            'params' => [],
            'entry_ids' => [],
            'in_cond' => false,
            'shortcut' => '',
            'open_tag' => '{' . $name . '}',
            'field_name' => $name,
        ];

        return new \QueryNode($name, array_merge($defaults, $payload));
    }
}
