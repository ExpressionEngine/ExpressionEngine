<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use PHPUnit\Framework\TestCase;

class GridParserConstructTest extends TestCase
{
    /**
     * Load the legacy parser class before tests run.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        require_once BASEPATH . 'libraries/Grid_parser.php';
    }

    /**
     * Reset singleton mocks so each test runs with isolated EE state.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();

        parent::tearDown();
    }

    /**
     * Ensure constructor seeds all supported Grid aggregate and row modifiers.
     *
     * @return void
     */
    public function testConstructorInitializesExpectedModifiersInStableOrder(): void
    {
        $parser = new \Grid_parser();

        $this->assertSame(
            [
                'next_row',
                'prev_row',
                'total_rows',
                'table',
                'sum',
                'average',
                'lowest',
                'highest',
            ],
            $parser->modifiers
        );
    }

    /**
     * Ensure reserved names always include modifiers plus parser-only tag names.
     *
     * @return void
     */
    public function testConstructorBuildsReservedNamesFromModifiersAndReservedTags(): void
    {
        $parser = new \Grid_parser();

        $this->assertSame(
            [
                'next_row',
                'prev_row',
                'total_rows',
                'table',
                'sum',
                'average',
                'lowest',
                'highest',
                'switch',
                'count',
                'index',
                'field_total_rows',
            ],
            $parser->reserved_names
        );
        $this->assertCount(count(array_unique($parser->reserved_names)), $parser->reserved_names);
    }

    /**
     * Ensure constructor creates per-instance arrays and does not leak mutations.
     *
     * @return void
     */
    public function testConstructorCreatesIndependentInstanceState(): void
    {
        $first = new \Grid_parser();
        $first->modifiers[] = 'custom_modifier';
        $first->reserved_names[] = 'custom_reserved';

        $second = new \Grid_parser();

        $this->assertNotContains('custom_modifier', $second->modifiers);
        $this->assertNotContains('custom_reserved', $second->reserved_names);
        $this->assertSame(
            array_merge($second->modifiers, ['switch', 'count', 'index', 'field_total_rows']),
            $second->reserved_names
        );
    }

    /**
     * Ensure pre_process exits early when tagdata has no matching Grid tags.
     *
     * @return void
     */
    public function testPreProcessReturnsFalseWhenNoGridTagsMatch(): void
    {
        $parser = new \Grid_parser();

        $result = $parser->pre_process('plain text only', $this->makePreParser(), ['gallery' => 11]);

        $this->assertFalse($result);
    }

    /**
     * Ensure pre_process rejects configured keys that normalize to missing field names.
     *
     * @return void
     */
    public function testPreProcessReturnsFalseWhenMatchedFieldNormalizesToUnknownKey(): void
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $fieldName = null)
            {
                return ['field_name' => $fieldName];
            }
        });

        $parser = new \Grid_parser();

        $result = $parser->pre_process(
            '{grid:grid:content}',
            $this->makePreParser(),
            ['grid:content' => 11]
        );

        $this->assertFalse($result);
    }

    /**
     * Ensure closing and non-reserved variable tags are skipped without row queries.
     *
     * @return void
     */
    public function testPreProcessSkipsClosingAndNonReservedVariableTags(): void
    {
        $gridModel = $this->makeGridModelMock();
        $load = $this->makeLoadMock();

        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $fieldName = null)
            {
                return ['field_name' => 'custom_modifier'];
            }
        });
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $load);

        $parser = new \Grid_parser();

        $result = $parser->pre_process(
            '{grid:alpha:custom}{/grid:alpha}',
            $this->makePreParser('grid:', [4, 5]),
            ['alpha' => 22]
        );

        $this->assertTrue($result);
        $this->assertSame(['grid_model'], $load->models);
        $this->assertSame(
            [
                [
                    'field_ids' => [],
                    'content_type' => 'channel',
                ],
            ],
            $gridModel->columnsCalls
        );
        $this->assertSame([], $gridModel->entryRowsCalls);
        $this->assertSame(1, $gridModel->gridDataCalls);
        $this->assertSame([], $parser->grid_field_names);
    }

    /**
     * Ensure pre_process deduplicates field IDs and primes row data for each kept match.
     *
     * @return void
     */
    public function testPreProcessDeduplicatesFieldIdsAndPrimesEntryRows(): void
    {
        $gridModel = $this->makeGridModelMock();
        $load = $this->makeLoadMock();

        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $fieldName = null)
            {
                if (substr((string) $fieldName, -1) === ':') {
                    return ['field_name' => 'count'];
                }

                return ['field_name' => $fieldName];
            }
        });
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $load);

        $parser = new \Grid_parser();

        $result = $parser->pre_process(
            '{grid:alpha}Body{/grid:alpha}{grid:beta:}',
            $this->makePreParser('grid:', [101, 202]),
            ['alpha' => 11, 'beta' => 11],
            'fluid'
        );

        $this->assertTrue($result);
        $this->assertSame(['grid_model'], $load->models);
        $this->assertSame(
            [
                [
                    'field_ids' => [11],
                    'content_type' => 'fluid',
                ],
            ],
            $gridModel->columnsCalls
        );
        $this->assertCount(2, $gridModel->entryRowsCalls);
        $this->assertSame([101, 202], $gridModel->entryRowsCalls[0]['entry_ids']);
        $this->assertSame(11, $gridModel->entryRowsCalls[0]['field_id']);
        $this->assertSame('fluid', $gridModel->entryRowsCalls[0]['content_type']);
        $this->assertSame('', $gridModel->entryRowsCalls[0]['params']);
        $this->assertSame([101, 202], $gridModel->entryRowsCalls[1]['entry_ids']);
        $this->assertSame(11, $gridModel->entryRowsCalls[1]['field_id']);
        $this->assertSame('fluid', $gridModel->entryRowsCalls[1]['content_type']);
        $this->assertSame('', ltrim($gridModel->entryRowsCalls[1]['params'], ':'));
        $this->assertSame([11 => ['grid:beta']], $parser->grid_field_names);
        $this->assertSame(1, $gridModel->gridDataCalls);
    }

    /**
     * Ensure field names containing dashes are matched before their root names.
     *
     * @return void
     */
    public function testPreProcessPrefersDashedFieldNameOverRootName(): void
    {
        $gridModel = $this->makeGridModelMock();
        $load = $this->makeLoadMock();

        ee()->setMock('Variables/Parser', new class {
            public function parseVariableProperties($properties, $fieldName = null)
            {
                return ['field_name' => $fieldName];
            }
        });
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $load);

        $parser = new \Grid_parser();

        $result = $parser->pre_process(
            '{grid:photos-grid}',
            $this->makePreParser(),
            ['photos' => 31, 'photos-grid' => 42]
        );

        $this->assertTrue($result);
        $this->assertSame([['field_ids' => [42], 'content_type' => 'channel']], $gridModel->columnsCalls);
        $this->assertSame(42, $gridModel->entryRowsCalls[0]['field_id']);
        $this->assertSame([42 => ['grid:photos-grid']], $parser->grid_field_names);
    }

    /**
     * Build a parser-like stub exposing Grid pre-parser methods.
     *
     * @param string $prefix Tag prefix expected by the parser.
     * @param array $entryIds Entry IDs returned by pre-parser.
     * @return object
     */
    private function makePreParser(string $prefix = 'grid:', array $entryIds = [7]): object
    {
        return new class($prefix, $entryIds) {
            private $prefix;
            private $entryIds;

            public function __construct(string $prefix, array $entryIds)
            {
                $this->prefix = $prefix;
                $this->entryIds = $entryIds;
            }

            public function prefix()
            {
                return $this->prefix;
            }

            public function entry_ids()
            {
                return $this->entryIds;
            }
        };
    }

    /**
     * Build a load mock that records model loading calls.
     *
     * @return object
     */
    private function makeLoadMock(): object
    {
        return new class extends \eeSingletonLoadMock {
            public $models = [];

            public function model($name = null)
            {
                $this->models[] = $name;
            }
        };
    }

    /**
     * Build a lightweight Grid model mock used by pre_process tests.
     *
     * @return object
     */
    private function makeGridModelMock(): object
    {
        return new class {
            public $columnsCalls = [];
            public $entryRowsCalls = [];
            public $gridDataCalls = 0;

            public function get_columns_for_field($fieldIds, $contentType)
            {
                $this->columnsCalls[] = [
                    'field_ids' => $fieldIds,
                    'content_type' => $contentType,
                ];

                return [];
            }

            public function get_entry_rows($entryIds, $fieldId, $contentType, $params)
            {
                $this->entryRowsCalls[] = [
                    'entry_ids' => $entryIds,
                    'field_id' => $fieldId,
                    'content_type' => $contentType,
                    'params' => $params,
                ];

                return [];
            }

            public function get_grid_data()
            {
                $this->gridDataCalls++;

                return [];
            }
        };
    }
}
