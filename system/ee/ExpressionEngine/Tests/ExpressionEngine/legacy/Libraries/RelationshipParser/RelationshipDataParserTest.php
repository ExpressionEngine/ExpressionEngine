<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\RelationshipParser;

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/libraries/datastructures/Tree.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Exceptions.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Nodes.php';
require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Parser.php';

use PHPUnit\Framework\TestCase;

class RelationshipDataParserShim extends \EE_Relationship_data_parser
{
    public $overrideParseNode = false;
    public $overrideClearNodeTagdata = false;
    public $overrideReplace = false;
    public $overrideProcessParameters = false;
    public $overrideApplySort = false;

    public $parseNodeReturn = '';
    public $clearNodeTagdataReturn = '';
    public $replaceReturn = '';
    public $processParametersReturn = ['entries' => [], 'categories' => []];
    public $applySortReturn = [];

    public $parseNodeCalls = [];
    public $clearNodeTagdataCalls = [];
    public $replaceCalls = [];
    public $processParametersCalls = [];
    public $applySortCalls = [];

    public function parse_node($node, $parent_id, $tagdata)
    {
        if ($this->overrideParseNode) {
            $this->parseNodeCalls[] = [$node, $parent_id, $tagdata];
            return $this->parseNodeReturn;
        }

        return parent::parse_node($node, $parent_id, $tagdata);
    }

    public function clear_node_tagdata($node, $tagdata)
    {
        if ($this->overrideClearNodeTagdata) {
            $this->clearNodeTagdataCalls[] = [$node, $tagdata];
            return $this->clearNodeTagdataReturn;
        }

        return parent::clear_node_tagdata($node, $tagdata);
    }

    public function replace($node, $tagdata, $data)
    {
        if ($this->overrideReplace) {
            $this->replaceCalls[] = [$node, $tagdata, $data];
            return $this->replaceReturn;
        }

        return parent::replace($node, $tagdata, $data);
    }

    public function process_parameters($node, $parent_id)
    {
        if ($this->overrideProcessParameters) {
            $this->processParametersCalls[] = [$node, $parent_id];
            return $this->processParametersReturn;
        }

        return parent::process_parameters($node, $parent_id);
    }

    public function _apply_sort($node, $entry_ids)
    {
        if ($this->overrideApplySort) {
            $this->applySortCalls[] = [$node, $entry_ids];
            return $this->applySortReturn;
        }

        return parent::_apply_sort($node, $entry_ids);
    }
}

class RelationshipDataParserTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();

        ee()->setMock('load', new class extends \eeSingletonLoadMock {
            public $libraries = [];

            public function library($name = '')
            {
                $this->libraries[] = $name;
            }
        });

        ee()->setMock('legacy_api', new class {
            public $instantiated = [];

            public function instantiate($name)
            {
                $this->instantiated[] = $name;
            }
        });

        ee()->setMock('functions', new \FakeFunctions());

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

        ee()->setMock('localize', new class {
            public $now = 100;

            public function string_to_timestamp($value)
            {
                return is_numeric($value) ? (int) $value : (int) strtotime((string) $value);
            }
        });

        $config = new \FakeConfig();
        $config->setItem('site_id', 1);
        ee()->setMock('config', $config);

        ee()->setMock('Variables/Parser', new class {
            public $calls = [];

            public function getFullTag($nodeTagdata, $match, $open, $close)
            {
                $this->calls[] = [$nodeTagdata, $match, $open, $close];
                return $match;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testEntryReturnsEntryWhenPresentAndNullWhenMissing()
    {
        $parser = $this->makeParser(null, [11 => ['title' => 'Alpha']], []);

        $this->assertSame(['title' => 'Alpha'], $parser->entry(11));
        $this->assertNull($parser->entry(99));
    }

    public function testCategoryReturnsCategoryWhenPresentAndNullWhenMissing()
    {
        $parser = $this->makeParser(null, [], [22 => [['cat_id' => 9]]]);

        $this->assertSame([['cat_id' => 9]], $parser->category(22));
        $this->assertNull($parser->category(999));
    }

    public function testParseThrowsForNonRootTree()
    {
        $root = new \EE_TreeNode('root');
        $child = $this->makeNode('rel');
        $root->add($child);

        $parser = $this->makeParser($child);

        $this->expectException(\EE_Relationship_exception::class);
        $this->expectExceptionMessage('Invalid Relationship Tree');
        $parser->parse(1, '{rel}x{/rel}', $this->makeChannel([1 => ['rel' => 10]]));
    }

    public function testParseClearsHiddenRelationshipField()
    {
        $root = new \EE_TreeNode('root');
        $child = $this->makeNode('rel', ['field_name' => 'rel']);
        $root->add($child);

        $parser = $this->makeParser($root);
        $parser->overrideParseNode = true;
        $parser->parseNodeReturn = 'PARSED';
        $parser->overrideClearNodeTagdata = true;
        $parser->clearNodeTagdataReturn = 'CLEARED';

        $out = $parser->parse(
            123,
            '{rel}keep{/rel}',
            $this->makeChannel([1 => ['rel' => 10]], [123 => [10]])
        );

        $this->assertSame('CLEARED', $out);
        $this->assertCount(1, $parser->clearNodeTagdataCalls);
        $this->assertCount(0, $parser->parseNodeCalls);
    }

    public function testParseDelegatesToParseNodeWhenFieldIsVisible()
    {
        $root = new \EE_TreeNode('root');
        $child = $this->makeNode('rel', ['field_name' => 'rel']);
        $root->add($child);

        $parser = $this->makeParser($root);
        $parser->overrideParseNode = true;
        $parser->parseNodeReturn = 'PARSED';
        $parser->overrideClearNodeTagdata = true;
        $parser->clearNodeTagdataReturn = 'CLEARED';

        $out = $parser->parse(
            123,
            '{rel}keep{/rel}',
            $this->makeChannel([1 => ['rel' => 10]], [123 => [99]])
        );

        $this->assertSame('PARSED', $out);
        $this->assertCount(1, $parser->parseNodeCalls);
        $this->assertCount(0, $parser->clearNodeTagdataCalls);
    }

    public function testParseNodeReturnsTagdataForNoResultsConditionalWithoutEntryIds()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel', [
            'entry_ids' => [],
            'in_cond' => true,
            'shortcut' => 'no_results',
            'open_tag' => '{if rel:no_results}',
        ]);

        $this->assertSame('ORIGINAL', $parser->parse_node($node, 5, 'ORIGINAL'));
    }

    public function testParseNodeUsesPrepConditionalsForMissingConditionalParent()
    {
        $functionMock = new class {
            public $vars = [];

            public function prep_conditionals($str, $vars = [])
            {
                $this->vars = $vars;
                return 'COND';
            }
        };
        ee()->setMock('functions', $functionMock);

        $parser = $this->makeParser();
        $node = $this->makeNode('rel', [
            'entry_ids' => [],
            'in_cond' => true,
            'shortcut' => '',
            'open_tag' => '{if rel}',
        ]);

        $this->assertSame('COND', $parser->parse_node($node, 5, 'ORIGINAL'));
        $this->assertSame(['{if rel}' => false], $functionMock->vars);
    }

    public function testParseNodeClearsNodeWhenEntryIdsAreMissingOutsideConditionals()
    {
        $parser = $this->makeParser();
        $parser->overrideClearNodeTagdata = true;
        $parser->clearNodeTagdataReturn = 'CLEARED';

        $node = $this->makeNode('rel', [
            'entry_ids' => [],
            'in_cond' => false,
            'shortcut' => '',
        ]);

        $this->assertSame('CLEARED', $parser->parse_node($node, 5, 'ORIGINAL'));
        $this->assertCount(1, $parser->clearNodeTagdataCalls);
    }

    public function testParseNodeEvaluatesIfRelationshipFieldConditional()
    {
        $functionMock = new class {
            public $vars = [];

            public function prep_conditionals($str, $vars = [])
            {
                $this->vars = $vars;
                return 'COUNTED';
            }
        };
        ee()->setMock('functions', $functionMock);

        $parser = $this->makeParser();
        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [11, 12]],
            'in_cond' => true,
            'shortcut' => '',
            'open_tag' => '{if rel}',
        ]);

        $this->assertSame('COUNTED', $parser->parse_node($node, 5, 'ORIGINAL'));
        $this->assertSame(['{if rel}' => 1], $functionMock->vars);
    }

    public function testParseNodeReplacesShortcutTotalResultsSingleTag()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [11, 11, 12]],
            'shortcut' => 'total_results',
            'open_tag' => '{rel:total_results}',
        ]);

        $this->assertSame('before 2 after', $parser->parse_node($node, 5, 'before {rel:total_results} after'));
    }

    public function testParseNodeUsesPrepConditionalsForShortcutConditionalTotals()
    {
        $functionMock = new class {
            public $vars = [];

            public function prep_conditionals($str, $vars = [])
            {
                $this->vars = $vars;
                return 'SHORTCOND';
            }
        };
        ee()->setMock('functions', $functionMock);

        $parser = $this->makeParser();
        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [11, 12]],
            'in_cond' => true,
            'shortcut' => 'length',
            'open_tag' => '{if rel:length}',
        ]);

        $this->assertSame('SHORTCOND', $parser->parse_node($node, 5, 'ORIGINAL'));
        $this->assertSame(['{if rel:length}' => 2], $functionMock->vars);
    }

    public function testParseNodeReplacesShortcutEntryIdsWithCustomDelimiter()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [11, 12, 13]],
            'shortcut' => 'entry_ids',
            'open_tag' => '{rel:entry_ids}',
            'params' => ['delimiter' => ','],
        ]);

        $this->assertSame('11,12,13', $parser->parse_node($node, 5, '{rel:entry_ids}'));
    }

    public function testParseNodeClearsWhenShortcutEntryRecordIsMissing()
    {
        $parser = $this->makeParser(null, [], []);
        $parser->overrideClearNodeTagdata = true;
        $parser->clearNodeTagdataReturn = 'CLEARED';

        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [99]],
            'shortcut' => 'title',
            'open_tag' => '{rel:title}',
        ]);

        $this->assertSame('CLEARED', $parser->parse_node($node, 5, '{rel:title}'));
        $this->assertCount(1, $parser->clearNodeTagdataCalls);
    }

    public function testParseNodeShortcutTagPairDelegatesToReplace()
    {
        $parser = $this->makeParser(
            null,
            [99 => ['entry_id' => 99, 'title' => 'ok']],
            [
                99 => [[
                    'cat_id' => '5',
                    'parent_id' => '0',
                    'cat_name' => 'news',
                    'cat_image' => '',
                    'cat_description' => '',
                    'group_id' => '1',
                    'cat_url_title' => 'news',
                ]],
            ]
        );
        $parser->overrideReplace = true;
        $parser->replaceReturn = 'REPLACED';

        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [99]],
            'shortcut' => 'title',
            'open_tag' => '{rel:title}',
        ]);

        $this->assertSame('REPLACED', $parser->parse_node($node, 5, '{rel:title}inner{/rel:title}'));
        $this->assertCount(1, $parser->replaceCalls);
    }

    public function testParseNodeReturnsOriginalWhenLoopTagPairIsMissing()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [99]],
            'shortcut' => '',
            'open_tag' => '{rel}',
        ]);

        $this->assertSame('ORIGINAL', $parser->parse_node($node, 5, 'ORIGINAL'));
    }

    public function testParseNodeClearsLoopTagWhenProcessedRowsAreEmpty()
    {
        $parser = $this->makeParser();
        $parser->overrideProcessParameters = true;
        $parser->processParametersReturn = ['entries' => [], 'categories' => []];
        $parser->overrideClearNodeTagdata = true;
        $parser->clearNodeTagdataReturn = 'CLEARED';

        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [99]],
            'shortcut' => '',
            'open_tag' => '{rel}',
        ]);

        $this->assertSame('CLEARED', $parser->parse_node($node, 5, '{rel}x{/rel}'));
        $this->assertCount(1, $parser->processParametersCalls);
    }

    public function testParseNodeLoopTagDelegatesToReplaceForNonEmptyRows()
    {
        $parser = $this->makeParser();
        $parser->overrideProcessParameters = true;
        $parser->processParametersReturn = ['entries' => [99 => ['entry_id' => 99]], 'categories' => []];
        $parser->overrideReplace = true;
        $parser->replaceReturn = 'LOOP-REPLACED';

        $node = $this->makeNode('rel', [
            'entry_ids' => [5 => [99]],
            'shortcut' => '',
            'open_tag' => '{rel}',
        ]);

        $this->assertSame('LOOP-REPLACED', $parser->parse_node($node, 5, '{rel}x{/rel}'));
        $this->assertCount(1, $parser->replaceCalls);
    }

    public function testReplaceRemovesFrontEditTagWhenDisabled()
    {
        $parsed = new class {
            public function parse($channel, $data, $config)
            {
                return 'A {rel:frontedit} B';
            }
        };

        $factory = new class($parsed) {
            public $parser;
            public $createArgs = [];

            public function __construct($parser)
            {
                $this->parser = $parser;
            }

            public function create($tagdata, $prefix)
            {
                $this->createArgs[] = [$tagdata, $prefix];
                return $this->parser;
            }
        };
        ee()->setMock('channel_entries_parser', $factory);

        $parser = $this->makeParser();
        $this->setParserChannel($parser, $this->makeChannel([1 => ['rel' => 22]]));

        $node = $this->makeNode('rel', [
            'field_name' => 'rel',
            'entry_ids' => [10 => [100]],
            'shortcut' => '',
            'params' => ['disable' => 'member_data|frontedit'],
        ]);

        $this->assertSame('A  B', $parser->replace($node, 'chunk', ['entries' => [], 'categories' => []]));
        $this->assertSame([['chunk', 'rel:']], $factory->createArgs);
    }

    public function testReplaceInjectsFrontEditLinkForFronteditShortcut()
    {
        $parsed = new class {
            public function parse($channel, $data, $config)
            {
                return 'A {rel:frontedit} B';
            }
        };
        ee()->setMock('channel_entries_parser', new class($parsed) {
            public $parser;

            public function __construct($parser)
            {
                $this->parser = $parser;
            }

            public function create($tagdata, $prefix)
            {
                return $this->parser;
            }
        });

        ee()->setMock('db', new class {
            public function select($fields)
            {
                return $this;
            }

            public function from($table)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function get()
            {
                return new class {
                    public function row($field)
                    {
                        if ($field === 'channel_id') {
                            return 9;
                        }

                        return 2;
                    }
                };
            }
        });

        ee()->setMock('pro:FrontEdit', new class {
            public function entryFieldEditLink($site_id, $channel_id, $entry_id, $field_id)
            {
                return 'EDIT-LINK';
            }
        });

        $config = new \FakeConfig();
        $config->setItem('site_id', 2);
        ee()->setMock('config', $config);

        $parser = $this->makeParser();
        $this->setParserChannel($parser, $this->makeChannel([
            3 => ['rel' => 44],
            0 => ['rel' => 44],
            2 => ['rel' => 44],
        ]));

        $node = $this->makeNode('rel', [
            'field_name' => 'rel',
            'entry_ids' => [55 => [200]],
            'shortcut' => 'frontedit',
            'params' => [],
        ]);

        $this->assertSame('A EDIT-LINK B', $parser->replace($node, 'chunk', ['entries' => [200 => []], 'categories' => []]));
    }

    public function testReplaceAppliesBackspaceBeforeReturning()
    {
        $parsed = new class {
            public function parse($channel, $data, $config)
            {
                return 'Hello!!!';
            }
        };
        ee()->setMock('channel_entries_parser', new class($parsed) {
            public $parser;

            public function __construct($parser)
            {
                $this->parser = $parser;
            }

            public function create($tagdata, $prefix)
            {
                return $this->parser;
            }
        });

        $parser = $this->makeParser();
        $this->setParserChannel($parser, $this->makeChannel([1 => ['rel' => 22]]));

        $node = $this->makeNode('rel', [
            'field_name' => 'rel',
            'entry_ids' => [10 => [100]],
            'shortcut' => '',
            'params' => ['backspace' => 3],
        ]);

        $this->assertSame('Hello', $parser->replace($node, 'chunk', ['entries' => [], 'categories' => []]));
    }

    public function testFindNoResultsReturnsInnerTagContents()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel');

        $content = $parser->find_no_results($node, 'A {if rel:no_results}EMPTY{/if} B');

        $this->assertSame('EMPTY', $content);
    }

    public function testFindNoResultsReturnsWholeTagWhenRequested()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel');

        $tag = $parser->find_no_results($node, 'A {if rel:no_results}EMPTY{/if} B', true);

        $this->assertSame('{if rel:no_results}EMPTY{/if}', $tag);
    }

    public function testFindNoResultsUsesVariablesParserForNestedConditionals()
    {
        $variablesParser = new class {
            public $called = false;

            public function getFullTag($nodeTagdata, $match, $open, $close)
            {
                $this->called = true;
                return '{if rel:no_results}NESTED{/if}';
            }
        };
        ee()->setMock('Variables/Parser', $variablesParser);

        $parser = $this->makeParser();
        $node = $this->makeNode('rel');

        $content = $parser->find_no_results($node, '{if rel:no_results}{if x}X{/if}{/if}');

        $this->assertTrue($variablesParser->called);
        $this->assertSame('NESTED', $content);
    }

    public function testClearNodeTagdataReplacesLoopWithNoResultsContent()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel', ['open_tag' => '{rel}']);

        $clean = $parser->clear_node_tagdata($node, 'A {rel}X {if rel:no_results}NONE{/if} Y{/rel} B');

        $this->assertSame('A NONE B', preg_replace('/\s+/', ' ', trim($clean)));
    }

    public function testClearNodeTagdataRemovesShortcutSingleTag()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel', [
            'shortcut' => 'entry_ids',
            'open_tag' => '{rel:entry_ids}',
        ]);

        $clean = $parser->clear_node_tagdata($node, 'A {rel:entry_ids} B');

        $this->assertSame('A  B', $clean);
    }

    public function testCleanupNoResultsTagRemovesRemainingNoResultsBlock()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel');

        $this->assertSame('AB', $parser->cleanup_no_results_tag($node, 'A{if rel:no_results}X{/if}B'));
    }

    public function testProcessParametersFiltersFutureExpiredAndSetsDefaultStatus()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
            2 => ['entry_id' => 2, 'entry_date' => 150, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'two', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
            3 => ['entry_id' => 3, 'entry_date' => 40, 'expiration_date' => 90, 'channel_name' => 'news', 'url_title' => 'three', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1, 2, 3]],
            'params' => ['sticky' => 'no'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([1], array_keys($result['entries']));
        $this->assertSame('open', $node->param('status'));
    }

    public function testProcessParametersIncludesEntriesWithNoCategoriesForNotCategoryFilter()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1]],
            'params' => ['sticky' => 'no', 'category' => 'not 5'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([1], array_keys($result['entries']));
    }

    public function testProcessParametersSupportsInclusiveCategoryFilters()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];
        $categories = [
            1 => [
                ['cat_id' => '5', 'parent_id' => '0', 'cat_name' => 'a', 'cat_image' => '', 'cat_description' => '', 'group_id' => '1', 'cat_url_title' => 'a'],
                ['cat_id' => '6', 'parent_id' => '0', 'cat_name' => 'b', 'cat_image' => '', 'cat_description' => '', 'group_id' => '1', 'cat_url_title' => 'b'],
            ],
        ];

        $parser = $this->makeParser(null, $entries, $categories);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1]],
            'params' => ['sticky' => 'no', 'category' => '5&6'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([1], array_keys($result['entries']));
    }

    public function testProcessParametersHonorsStartOnFilter()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 80, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1]],
            'params' => ['sticky' => 'no', 'start_on' => '90'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([], array_keys($result['entries']));
    }

    public function testProcessParametersSkipsEmptyFilterAndSupportsChannelAliasFilter()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 80, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1]],
            'params' => ['sticky' => 'no', 'username' => '', 'channel' => 'news'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([1], array_keys($result['entries']));
    }

    public function testProcessParametersSupportsNegatedFieldFilters()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 80, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1]],
            'params' => ['sticky' => 'no', 'username' => 'not tom'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([], array_keys($result['entries']));
    }

    public function testProcessParametersSupportsSimpleCategoryIncludeFilter()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];
        $categories = [
            1 => [
                ['cat_id' => '5', 'parent_id' => '0', 'cat_name' => 'a', 'cat_image' => '', 'cat_description' => '', 'group_id' => '1', 'cat_url_title' => 'a'],
            ],
        ];

        $parser = $this->makeParser(null, $entries, $categories);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1]],
            'params' => ['sticky' => 'no', 'category' => '5'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([1], array_keys($result['entries']));
    }

    public function testProcessParametersSupportsSimpleNegatedCategoryFilter()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
            2 => ['entry_id' => 2, 'entry_date' => 40, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'two', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];
        $categories = [
            1 => [
                ['cat_id' => '5', 'parent_id' => '0', 'cat_name' => 'a', 'cat_image' => '', 'cat_description' => '', 'group_id' => '1', 'cat_url_title' => 'a'],
            ],
            2 => [
                ['cat_id' => '6', 'parent_id' => '0', 'cat_name' => 'b', 'cat_image' => '', 'cat_description' => '', 'group_id' => '1', 'cat_url_title' => 'b'],
            ],
        ];

        $parser = $this->makeParser(null, $entries, $categories);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1, 2]],
            'params' => ['sticky' => 'no', 'category' => 'not 5'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([2], array_keys($result['entries']));
    }

    public function testProcessParametersSupportsNegatedInclusiveCategoryFilters()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];
        $categories = [
            1 => [
                ['cat_id' => '5', 'parent_id' => '0', 'cat_name' => 'a', 'cat_image' => '', 'cat_description' => '', 'group_id' => '1', 'cat_url_title' => 'a'],
                ['cat_id' => '6', 'parent_id' => '0', 'cat_name' => 'b', 'cat_image' => '', 'cat_description' => '', 'group_id' => '1', 'cat_url_title' => 'b'],
            ],
        ];

        $parser = $this->makeParser(null, $entries, $categories);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1]],
            'params' => ['sticky' => 'no', 'category' => 'not 5&6'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([], array_keys($result['entries']));
    }

    public function testProcessParametersUsesExtensionHookAndSkipsSliceWhenEndScript()
    {
        ee()->setMock('extensions', new class {
            public $end_script = true;

            public function active_hook($name)
            {
                return $name === 'relationships_modify_rows';
            }

            public function call($hook, $rows, $node)
            {
                return [99 => ['entry_id' => 99]];
            }
        });

        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
            2 => ['entry_id' => 2, 'entry_date' => 40, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'two', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1, 2]],
            'params' => ['sticky' => 'no', 'limit' => 1],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([99], array_keys($result['entries']));
    }

    public function testProcessParametersAppliesOffsetAndLimitWhenAllowed()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
            2 => ['entry_id' => 2, 'entry_date' => 40, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'two', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
            3 => ['entry_id' => 3, 'entry_date' => 30, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'three', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1, 2, 3]],
            'params' => ['sticky' => 'no', 'offset' => 1, 'limit' => 1],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertSame([2], array_keys($result['entries']));
    }

    public function testProcessParametersTriggersApplySortWhenSortingRequested()
    {
        $entries = [
            1 => ['entry_id' => 1, 'entry_date' => 50, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'one', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
            2 => ['entry_id' => 2, 'entry_date' => 40, 'expiration_date' => 0, 'channel_name' => 'news', 'url_title' => 'two', 'username' => 'tom', 'group_id' => 1, 'status' => 'open', 'sticky' => 0],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $parser->overrideApplySort = true;
        $parser->applySortReturn = [2, 1];

        $node = $this->makeNode('rel', [
            'entry_ids' => [10 => [1, 2]],
            'params' => ['orderby' => 'entry_date', 'sticky' => 'no'],
        ]);

        $result = $parser->process_parameters($node, 10);

        $this->assertCount(1, $parser->applySortCalls);
        $this->assertSame([2, 1], array_keys($result['entries']));
    }

    public function testFormatCatArrayRenamesRequiredKeys()
    {
        $parser = $this->makeParser();
        $method = new \ReflectionMethod(\EE_Relationship_data_parser::class, '_format_cat_array');
        $method->setAccessible(true);

        $categories = [
            1 => [[
                'cat_id' => 9,
                'parent_id' => 0,
                'cat_name' => 'name',
                'cat_image' => 'img',
                'cat_description' => 'desc',
                'group_id' => 1,
                'cat_url_title' => 'slug',
            ]],
        ];

        $formatted = $method->invoke($parser, $categories);

        $this->assertArrayNotHasKey('cat_id', $formatted[1][0]);
        $this->assertSame(9, $formatted[1][0][0]);
        $this->assertSame('slug', $formatted[1][0][6]);
    }

    public function testApplySortReturnsInputWhenEntryIdsAreEmpty()
    {
        $parser = $this->makeParser();
        $node = $this->makeNode('rel', ['params' => ['sticky' => 'no']]);

        $this->assertSame([], $parser->_apply_sort($node, []));
    }

    public function testApplySortSupportsRandomOrder()
    {
        $entries = [
            1 => ['entry_id' => 1, 'sticky' => 0, 'entry_date' => 10],
            2 => ['entry_id' => 2, 'sticky' => 0, 'entry_date' => 20],
            3 => ['entry_id' => 3, 'sticky' => 0, 'entry_date' => 30],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $this->setParserChannel($parser, $this->makeChannel([1 => []]));

        $node = $this->makeNode('rel', [
            'params' => ['orderby' => 'random', 'sticky' => 'no'],
        ]);

        $sorted = $parser->_apply_sort($node, [1, 2, 3]);
        sort($sorted);

        $this->assertSame([1, 2, 3], $sorted);
    }

    public function testApplySortHandlesCustomFieldsDateAliasAndMissingEntries()
    {
        $entries = [
            1 => ['entry_id' => 1, 'sticky' => 0, 'field_id_7' => 'Bravo', 'entry_date' => 20],
            2 => ['entry_id' => 2, 'sticky' => 0, 'field_id_7' => 'alpha', 'entry_date' => 10],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $this->setParserChannel($parser, $this->makeChannel([1 => ['custom' => 7]]));

        $node = $this->makeNode('rel', [
            'params' => ['orderby' => 'custom|date', 'sort' => 'asc|desc', 'sticky' => 'no'],
        ]);

        $sorted = $parser->_apply_sort($node, [1, 3, 2]);

        $this->assertSame([2, 1], array_values($sorted));
    }

    public function testApplySortPrependsStickySortWhenEnabled()
    {
        $entries = [
            1 => ['entry_id' => 1, 'sticky' => 0, 'title' => 'b', 'entry_date' => 10],
            2 => ['entry_id' => 2, 'sticky' => 1, 'title' => 'a', 'entry_date' => 20],
        ];

        $parser = $this->makeParser(null, $entries, []);
        $this->setParserChannel($parser, $this->makeChannel([1 => []]));

        $node = $this->makeNode('rel', [
            'params' => ['orderby' => 'title', 'sort' => 'asc', 'sticky' => 'yes'],
        ]);

        $sorted = $parser->_apply_sort($node, [1, 2]);

        $this->assertSame([2, 1], array_values($sorted));
    }

    private function makeParser($tree = null, array $entries = [], array $categories = [])
    {
        if ($tree === null) {
            $tree = new \EE_TreeNode('root');
        }

        return new RelationshipDataParserShim($tree, $entries, $categories);
    }

    private function makeNode($name, array $payload = [])
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

    private function makeChannel(array $cfields, array $hiddenFields = [])
    {
        return (object) [
            'cfields' => $cfields,
            'hidden_fields' => $hiddenFields,
        ];
    }

    private function setParserChannel(\EE_Relationship_data_parser $parser, $channel): void
    {
        $property = new \ReflectionProperty(\EE_Relationship_data_parser::class, '_channel');
        $property->setAccessible(true);
        $property->setValue($parser, $channel);
    }
}
