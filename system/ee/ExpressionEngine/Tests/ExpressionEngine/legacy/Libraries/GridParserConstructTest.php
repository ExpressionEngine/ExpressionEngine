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
        require_once SYSPATH . 'ee/legacy/libraries/relationship_parser/Exceptions.php';
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
     * Ensure pre_process keeps plain opening tags and preloads field rows.
     *
     * @return void
     */
    public function testPreProcessKeepsPlainOpeningTagAndPreloadsRows(): void
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
            '{grid:alpha}',
            $this->makePreParser('grid:', [60]),
            ['alpha' => 22]
        );

        $this->assertTrue($result);
        $this->assertSame(['grid_model'], $load->models);
        $this->assertSame(
            [
                [
                    'field_ids' => [22],
                    'content_type' => 'channel',
                ],
            ],
            $gridModel->columnsCalls
        );
        $this->assertCount(1, $gridModel->entryRowsCalls);
        $this->assertSame([60], $gridModel->entryRowsCalls[0]['entry_ids']);
        $this->assertSame(22, $gridModel->entryRowsCalls[0]['field_id']);
        $this->assertSame('channel', $gridModel->entryRowsCalls[0]['content_type']);
        $this->assertSame('', $gridModel->entryRowsCalls[0]['params']);
        $this->assertSame([22 => ['grid:alpha']], $parser->grid_field_names);
        $this->assertSame(1, $gridModel->gridDataCalls);
    }

    /**
     * Ensure pre_process keeps reserved modifier tags and primes row data for them.
     *
     * @return void
     */
    public function testPreProcessKeepsReservedModifierTagsAndPreloadsRows(): void
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
            '{grid:alpha:}',
            $this->makePreParser('grid:', [15, 20]),
            ['alpha' => 9],
            'fluid'
        );

        $this->assertTrue($result);
        $this->assertSame(['grid_model'], $load->models);
        $this->assertSame(
            [
                [
                    'field_ids' => [9],
                    'content_type' => 'fluid',
                ],
            ],
            $gridModel->columnsCalls
        );
        $this->assertCount(1, $gridModel->entryRowsCalls);
        $this->assertSame([15, 20], $gridModel->entryRowsCalls[0]['entry_ids']);
        $this->assertSame(9, $gridModel->entryRowsCalls[0]['field_id']);
        $this->assertSame('fluid', $gridModel->entryRowsCalls[0]['content_type']);
        $this->assertSame(':', $gridModel->entryRowsCalls[0]['params']);
        $this->assertSame([9 => ['grid:alpha']], $parser->grid_field_names);
        $this->assertSame(1, $gridModel->gridDataCalls);
    }

    /**
     * Ensure pre_process succeeds when only closing tags are present in the match set.
     *
     * @return void
     */
    public function testPreProcessHandlesClosingTagsWithoutPrimingEntryRows(): void
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

        $result = $parser->pre_process('{/grid:alpha}', $this->makePreParser(), ['alpha' => 22]);

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
     * Ensure instantiate_fieldtype bootstraps fieldtype APIs and assigns merged settings.
     *
     * @return void
     */
    public function testInstantiateFieldtypeBootstrapsApisAndAssignsMergedSettings(): void
    {
        $fieldtype = $this->makeFieldtypeHandlerMock();
        $load = $this->makeParseLoadMock();
        $legacyApi = $this->makeLegacyApiMock();
        $apiChannelFields = $this->makeInstantiateApiChannelFieldsMock($fieldtype, []);
        $column = [
            'col_type' => 'text',
            'col_id' => 12,
            'col_label' => 'Headline',
            'col_required' => 'y',
            'col_name' => 'headline',
            'col_settings' => [
                'custom' => 'value',
                'entry_id' => 999,
            ],
        ];

        ee()->setMock('load', $load);
        ee()->setMock('legacy_api', $legacyApi);
        ee()->setMock('api_channel_fields', $apiChannelFields);

        $parser = new \Grid_parser();
        $result = $parser->instantiate_fieldtype($column, 'new_row_5', 45, 88, 'fluid', 13, true);

        $this->assertSame($fieldtype, $result);
        $this->assertSame(['api'], $load->libraries);
        $this->assertSame(['channel_fields'], $legacyApi->instantiateCalls);
        $this->assertSame(1, $apiChannelFields->fetchInstalledFieldtypesCalls);
        $this->assertSame([['type' => 'text', 'cache' => true]], $apiChannelFields->setupHandlerCalls);
        $this->assertSame(
            [
                [
                    'field_id' => 12,
                    'field_name' => 'col_id_12',
                    'content_id' => 88,
                    'content_type' => 'grid',
                ],
            ],
            $fieldtype->initCalls
        );
        $this->assertSame('value', $fieldtype->settings['custom']);
        $this->assertSame('Headline', $fieldtype->settings['field_label']);
        $this->assertSame('y', $fieldtype->settings['field_required']);
        $this->assertSame(12, $fieldtype->settings['col_id']);
        $this->assertSame('headline', $fieldtype->settings['col_name']);
        $this->assertSame('y', $fieldtype->settings['col_required']);
        $this->assertSame(88, $fieldtype->settings['entry_id']);
        $this->assertSame(45, $fieldtype->settings['grid_field_id']);
        $this->assertSame('new_row_5', $fieldtype->settings['grid_row_name']);
        $this->assertSame('fluid', $fieldtype->settings['grid_content_type']);
        $this->assertSame(13, $fieldtype->settings['fluid_field_data_id']);
        $this->assertTrue($fieldtype->settings['in_modal_context']);
    }

    /**
     * Ensure instantiate_fieldtype keeps canonical Grid metadata when column settings conflict.
     *
     * @return void
     */
    public function testInstantiateFieldtypePrefersCanonicalMetadataOverConflictingColumnSettings(): void
    {
        $fieldtype = $this->makeFieldtypeHandlerMock();
        $apiChannelFields = $this->makeInstantiateApiChannelFieldsMock($fieldtype, ['text' => true], false);
        $column = [
            'col_type' => 'text',
            'col_id' => 12,
            'col_label' => 'Headline',
            'col_required' => 'y',
            'col_name' => 'headline',
            'col_settings' => [
                'custom' => 'value',
                'field_label' => 'Wrong Label',
                'field_required' => 'n',
                'col_id' => 999,
                'col_name' => 'wrong_name',
                'col_required' => 'n',
                'entry_id' => 777,
                'grid_field_id' => 555,
                'grid_row_name' => 'wrong_row',
                'grid_content_type' => 'matrix',
                'fluid_field_data_id' => 404,
                'in_modal_context' => false,
            ],
        ];

        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('legacy_api', $this->makeLegacyApiMock());
        ee()->setMock('api_channel_fields', $apiChannelFields);

        $parser = new \Grid_parser();
        $result = $parser->instantiate_fieldtype($column, 'new_row_5', 45, 88, 'fluid', 13, true);

        $this->assertSame($fieldtype, $result);
        $this->assertSame('value', $fieldtype->settings['custom']);
        $this->assertSame('Headline', $fieldtype->settings['field_label']);
        $this->assertSame('y', $fieldtype->settings['field_required']);
        $this->assertSame(12, $fieldtype->settings['col_id']);
        $this->assertSame('headline', $fieldtype->settings['col_name']);
        $this->assertSame('y', $fieldtype->settings['col_required']);
        $this->assertSame(88, $fieldtype->settings['entry_id']);
        $this->assertSame(45, $fieldtype->settings['grid_field_id']);
        $this->assertSame('new_row_5', $fieldtype->settings['grid_row_name']);
        $this->assertSame('fluid', $fieldtype->settings['grid_content_type']);
        $this->assertSame(13, $fieldtype->settings['fluid_field_data_id']);
        $this->assertTrue($fieldtype->settings['in_modal_context']);
    }

    /**
     * Ensure instantiate_fieldtype returns null when no fieldtype handler is available.
     *
     * @return void
     */
    public function testInstantiateFieldtypeReturnsNullWhenSetupHandlerFails(): void
    {
        $load = $this->makeParseLoadMock();
        $legacyApi = $this->makeLegacyApiMock();
        $apiChannelFields = $this->makeInstantiateApiChannelFieldsMock(false, ['text' => true], false);
        $column = [
            'col_type' => 'text',
            'col_id' => 9,
            'col_label' => 'Title',
            'col_required' => 'n',
            'col_name' => 'title',
        ];

        ee()->setMock('load', $load);
        ee()->setMock('legacy_api', $legacyApi);
        ee()->setMock('api_channel_fields', $apiChannelFields);

        $parser = new \Grid_parser();
        $result = $parser->instantiate_fieldtype($column);

        $this->assertNull($result);
        $this->assertSame([], $load->libraries);
        $this->assertSame([], $legacyApi->instantiateCalls);
        $this->assertSame(0, $apiChannelFields->fetchInstalledFieldtypesCalls);
        $this->assertSame([['type' => 'text', 'cache' => true]], $apiChannelFields->setupHandlerCalls);
    }

    /**
     * Ensure instantiate_fieldtype falls back to empty column settings when none are provided.
     *
     * @return void
     */
    public function testInstantiateFieldtypeUsesDefaultSettingsWhenColumnSettingsAreMissing(): void
    {
        $fieldtype = $this->makeFieldtypeHandlerMock();
        $apiChannelFields = $this->makeInstantiateApiChannelFieldsMock($fieldtype, ['text' => true], false);
        $column = [
            'col_type' => 'text',
            'col_id' => 3,
            'col_label' => 'Summary',
            'col_required' => 'n',
            'col_name' => 'summary',
        ];

        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('legacy_api', $this->makeLegacyApiMock());
        ee()->setMock('api_channel_fields', $apiChannelFields);

        $parser = new \Grid_parser();
        $result = $parser->instantiate_fieldtype($column);

        $this->assertSame($fieldtype, $result);
        $this->assertSame(
            [
                [
                    'field_id' => 3,
                    'field_name' => 'col_id_3',
                    'content_id' => 0,
                    'content_type' => 'grid',
                ],
            ],
            $fieldtype->initCalls
        );
        $this->assertSame('Summary', $fieldtype->settings['field_label']);
        $this->assertSame('n', $fieldtype->settings['field_required']);
        $this->assertSame(3, $fieldtype->settings['col_id']);
        $this->assertSame('summary', $fieldtype->settings['col_name']);
        $this->assertSame('n', $fieldtype->settings['col_required']);
        $this->assertSame(0, $fieldtype->settings['entry_id']);
        $this->assertSame(0, $fieldtype->settings['grid_field_id']);
        $this->assertNull($fieldtype->settings['grid_row_name']);
        $this->assertSame('channel', $fieldtype->settings['grid_content_type']);
        $this->assertSame(0, $fieldtype->settings['fluid_field_data_id']);
        $this->assertFalse($fieldtype->settings['in_modal_context']);
    }

    /**
     * Ensure call() prefers the grid_ method variant and wraps single-parameter payloads.
     *
     * @return void
     */
    public function testCallPrefersGridMethodAndWrapsSingleParam(): void
    {
        $load = $this->makeCallLoadMock();
        $apiChannelFields = $this->makeCallApiChannelFieldsMock(
            [
                'grid_replace_tag' => true,
            ],
            'GRID_RESULT',
            '/custom/fieldtypes/path',
            'text'
        );

        ee()->setMock('load', $load);
        ee()->setMock('api_channel_fields', $apiChannelFields);

        $parser = new \Grid_parser();
        $result = $parser->call('replace_tag', 'value');

        $this->assertSame('GRID_RESULT', $result);
        $this->assertSame(
            [
                [
                    'path' => '/custom/fieldtypes/path',
                    'view_cascade' => false,
                ],
            ],
            $load->addPackagePathCalls
        );
        $this->assertSame(['/custom/fieldtypes/path'], $load->removePackagePathCalls);
        $this->assertSame(['grid_replace_tag', 'grid_replace_tag'], $apiChannelFields->checkMethodExistsCalls);
        $this->assertSame(
            [
                [
                    'method' => 'grid_replace_tag',
                    'data' => ['value'],
                ],
            ],
            $apiChannelFields->applyCalls
        );
    }

    /**
     * Ensure call() falls back to the original method and keeps multi-parameter arrays untouched.
     *
     * @return void
     */
    public function testCallFallsBackToOriginalMethodWithMultiParamPayload(): void
    {
        $load = $this->makeCallLoadMock();
        $apiChannelFields = $this->makeCallApiChannelFieldsMock(
            [
                'grid_replace_tag' => false,
                'replace_tag' => true,
            ],
            'FALLBACK_RESULT'
        );

        ee()->setMock('load', $load);
        ee()->setMock('api_channel_fields', $apiChannelFields);

        $parser = new \Grid_parser();
        $result = $parser->call('replace_tag', ['first', 'second'], true);

        $this->assertSame('FALLBACK_RESULT', $result);
        $this->assertSame(['grid_replace_tag', 'replace_tag'], $apiChannelFields->checkMethodExistsCalls);
        $this->assertSame(
            [
                [
                    'method' => 'replace_tag',
                    'data' => ['first', 'second'],
                ],
            ],
            $apiChannelFields->applyCalls
        );
        $this->assertSame(
            [
                [
                    'path' => '/fieldtypes/path',
                    'view_cascade' => false,
                ],
            ],
            $load->addPackagePathCalls
        );
        $this->assertSame(['/fieldtypes/path'], $load->removePackagePathCalls);
    }

    /**
     * Ensure call() returns null when no method exists while still unwinding package path state.
     *
     * @return void
     */
    public function testCallReturnsNullWhenNoMethodExistsAndAlwaysRemovesPackagePath(): void
    {
        $load = $this->makeCallLoadMock();
        $apiChannelFields = $this->makeCallApiChannelFieldsMock(
            [
                'grid_replace_tag' => false,
                'replace_tag' => false,
            ],
            'UNUSED'
        );

        ee()->setMock('load', $load);
        ee()->setMock('api_channel_fields', $apiChannelFields);

        $parser = new \Grid_parser();
        $result = $parser->call('replace_tag', 'value');

        $this->assertNull($result);
        $this->assertSame(['grid_replace_tag', 'replace_tag'], $apiChannelFields->checkMethodExistsCalls);
        $this->assertSame([], $apiChannelFields->applyCalls);
        $this->assertSame(
            [
                [
                    'path' => '/fieldtypes/path',
                    'view_cascade' => false,
                ],
            ],
            $load->addPackagePathCalls
        );
        $this->assertSame(['/fieldtypes/path'], $load->removePackagePathCalls);
    }

    /**
     * Ensure call() bubbles fieldtype exceptions and does not unwind package path state.
     *
     * @return void
     */
    public function testCallBubblesApplyExceptionWithoutRemovingPackagePath(): void
    {
        $load = $this->makeCallLoadMock();
        $apiChannelFields = new class {
            public $ft_paths = ['text' => '/fieldtypes/path'];
            public $field_type = 'text';
            public $checkMethodExistsCalls = [];
            public $applyCalls = [];

            public function check_method_exists($method)
            {
                $this->checkMethodExistsCalls[] = $method;

                return $method === 'grid_replace_tag';
            }

            public function apply($method, $data)
            {
                $this->applyCalls[] = [
                    'method' => $method,
                    'data' => $data,
                ];

                throw new \RuntimeException('apply failed');
            }
        };

        ee()->setMock('load', $load);
        ee()->setMock('api_channel_fields', $apiChannelFields);

        $parser = new \Grid_parser();

        try {
            $parser->call('replace_tag', 'value');
            $this->fail('Expected RuntimeException from api_channel_fields->apply().');
        } catch (\RuntimeException $exception) {
            $this->assertSame('apply failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                [
                    'path' => '/fieldtypes/path',
                    'view_cascade' => false,
                ],
            ],
            $load->addPackagePathCalls
        );
        $this->assertSame([], $load->removePackagePathCalls);
        $this->assertSame(['grid_replace_tag', 'grid_replace_tag'], $apiChannelFields->checkMethodExistsCalls);
        $this->assertSame(
            [
                [
                    'method' => 'grid_replace_tag',
                    'data' => ['value'],
                ],
            ],
            $apiChannelFields->applyCalls
        );
    }

    /**
     * Ensure parse exits early when the field-pair tagdata is empty.
     *
     * @return void
     */
    public function testParseReturnsEmptyStringWhenTagdataIsEmpty(): void
    {
        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], '');

        $this->assertSame('', $result);
    }

    /**
     * Ensure parse bails out when Grid model returns no rows for the entry.
     *
     * @return void
     */
    public function testParseReturnsEmptyStringWhenGridModelReturnsFalse(): void
    {
        $gridModel = $this->makeParseGridModelMock(false);

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'ROW');

        $this->assertSame('', $result);
    }

    /**
     * Ensure parse bails out when rows are returned for other entries only.
     *
     * @return void
     */
    public function testParseReturnsEmptyStringWhenEntryRowsMissRequestedEntry(): void
    {
        $gridModel = $this->makeParseGridModelMock([
            'params' => $this->makeParseParams(),
            99 => [
                10 => ['row_id' => 10],
            ],
        ]);

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'ROW');

        $this->assertSame('', $result);
    }

    /**
     * Ensure parse normalizes relationship-prefixed field names when tagdata omits full prefix.
     *
     * @return void
     */
    public function testParseNormalizesFieldNameWhenTagdataDoesNotContainRelationshipPrefix(): void
    {
        $gridModel = $this->makeParseGridModelMock([
            'params' => $this->makeParseParams(),
            5 => [
                10 => ['row_id' => 10],
            ],
        ]);

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'rel:grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'gallery');

        $this->assertSame('gallery', $result);
        $this->assertSame('gallery', $parser->grid_field_names[11][0]);
    }

    /**
     * Ensure parse returns empty output for an unknown single row_id request.
     *
     * @return void
     */
    public function testParseReturnsEmptyStringWhenSingleRowIdDoesNotExist(): void
    {
        $gridModel = $this->makeParseGridModelMock([
            'params' => $this->makeParseParams(['row_id' => '404']),
            5 => [
                10 => ['row_id' => 10],
            ],
        ]);

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'ROW');

        $this->assertSame('', $result);
    }

    /**
     * Ensure parse narrows output to the selected row when row_id targets one row.
     *
     * @return void
     */
    public function testParseRestrictsToRequestedSingleRowId(): void
    {
        $gridModel = $this->makeParseGridModelMock([
            'params' => $this->makeParseParams(['row_id' => '20']),
            5 => [
                10 => ['row_id' => 10],
                20 => ['row_id' => 20],
            ],
        ]);
        $tmpl = $this->makeParseTemplateMock();
        $functions = $this->makeParseFunctionsMock();

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $tmpl);
        ee()->setMock('functions', $functions);
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'ROW');

        $this->assertSame('ROW', $result);
        $this->assertSame([['tagdata' => 'ROW', 'index' => 0, 'prefix' => 'gallery:']], $tmpl->parseSwitchCalls);
        $this->assertCount(1, $functions->prepConditionalCalls);
    }

    /**
     * Ensure parse supports "not row_id" filtering and applies backspace trimming.
     *
     * @return void
     */
    public function testParseFiltersNotRowIdAndAppliesBackspace(): void
    {
        $gridModel = $this->makeParseGridModelMock([
            'params' => $this->makeParseParams(['row_id' => 'not 20', 'backspace' => 1]),
            5 => [
                10 => ['row_id' => 10],
                20 => ['row_id' => 20],
            ],
        ]);

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'AB');

        $this->assertSame('A', $result);
    }

    /**
     * Ensure parse keeps only explicitly listed rows when row_id contains multiple values.
     *
     * @return void
     */
    public function testParseKeepsOnlyExplicitMultipleRowIds(): void
    {
        $gridModel = $this->makeParseGridModelMock([
            'params' => $this->makeParseParams(['row_id' => '20|30']),
            5 => [
                10 => ['row_id' => 10],
                20 => ['row_id' => 20],
                30 => ['row_id' => 30],
            ],
        ]);

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'R');

        $this->assertSame('RR', $result);
    }

    /**
     * Ensure parse renders no_results content when filtered rows produce no display data.
     *
     * @return void
     */
    public function testParseReturnsNoResultsContentWhenDisplayRowsAreEmpty(): void
    {
        $gridModel = $this->makeParseGridModelMock([
            'params' => $this->makeParseParams(['offset' => 5, 'limit' => 1]),
            5 => [
                10 => ['row_id' => 10],
            ],
        ]);
        $variablesParser = new class {
            public function getFullTag($tagdata, $chunk)
            {
                return $chunk;
            }
        };
        $template = $this->makeParseTemplateMock();

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $template);
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());
        ee()->setMock('Variables/Parser', $variablesParser);

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{if no_results}{if condition}EMPTY{/if}{/if}'
        );

        $this->assertSame('{if condition}EMPTY', $result);
        $this->assertSame('{if condition}EMPTY', $template->no_results);
    }

    /**
     * Ensure parse processes relationship rows, next/prev chunks, and conditionals across rows.
     *
     * @return void
     */
    public function testParseProcessesRelationshipsAndNextPrevPairsAcrossRows(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    10 => ['row_id' => 10, 'col_id_5' => '', 'col_id_7' => 'alpha'],
                    20 => ['row_id' => 20, 'col_id_5' => '1700000000', 'col_id_7' => 'beta'],
                ],
            ],
            [
                5 => ['field_id' => 11, 'col_id' => 5, 'col_name' => 'publish_at', 'col_type' => 'date'],
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'related_entry', 'col_type' => 'relationship'],
            ]
        );
        $functions = $this->makeParseFunctionsMock();
        $relationshipsParser = $this->makeRelationshipsParserMock(function ($rowId, $gridRow) {
            return str_replace('{rel}', 'REL' . $rowId, $gridRow);
        });

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $functions);
        ee()->setMock(
            'api_channel_fields',
            $this->makeApiChannelFieldsMock([
                'next_row' => [['next_row', 'NXT', [], '{NEXT}']],
                'prev_row' => [['prev_row', 'PRV', [], '{PREV}']],
            ])
        );
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel([101, 202]));
        ee()->setMock('relationships_parser', $relationshipsParser);

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], '{rel}{NEXT}{PREV}');

        $this->assertSame('REL10NXTREL20PRV', $result);
        $this->assertCount(2, $functions->prepConditionalCalls);
        $this->assertFalse($functions->prepConditionalCalls[0]['cond']['gallery:publish_at']);
        $this->assertSame(1700000000, $functions->prepConditionalCalls[1]['cond']['gallery:publish_at']);
        $this->assertSame(['gallery:related_entry' => 7], $relationshipsParser->createCalls[0]['relationships']);
    }

    /**
     * Ensure parse logs relationship parser exceptions and continues rendering rows.
     *
     * @return void
     */
    public function testParseLogsRelationshipParserErrorsAndContinues(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    10 => ['row_id' => 10],
                ],
            ],
            [
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'related_entry', 'col_type' => 'relationship'],
            ]
        );
        $template = $this->makeParseTemplateMock();
        $relationshipsParser = $this->makeRelationshipsParserMock(function () {
            throw new \EE_Relationship_exception('parse error');
        });

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $template);
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel([101]));
        ee()->setMock('relationships_parser', $relationshipsParser);

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'ROW');

        $this->assertSame('ROW', $result);
        $this->assertSame(['parse error'], $template->logMessages);
    }

    /**
     * Ensure parse shuffles entry rows when orderby=random and still renders each row once.
     *
     * @return void
     */
    public function testParseShufflesRowsWhenOrderByIsRandom(): void
    {
        $gridModel = $this->makeParseGridModelMock([
            'params' => $this->makeParseParams(['orderby' => 'random']),
            5 => [
                10 => ['row_id' => 10],
                20 => ['row_id' => 20],
            ],
        ]);

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'R');

        $this->assertSame(2, strlen($result));
        $this->assertSame('RR', $result);
    }

    /**
     * Ensure parse recovers when relationship parser creation throws an EE relationship exception.
     *
     * @return void
     */
    public function testParseContinuesWhenRelationshipParserCreationThrowsException(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    10 => ['row_id' => 10],
                ],
            ],
            [
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'related_entry', 'col_type' => 'relationship'],
            ]
        );
        $failingParser = new class {
            public function create()
            {
                throw new \EE_Relationship_exception('create error');
            }
        };

        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel([101]));
        ee()->setMock('relationships_parser', $failingParser);

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], 'ROW');

        $this->assertSame('ROW', $result);
    }

    /**
     * Ensure parse routes known field pairs through Grid replace_tag with row identity overrides.
     *
     * @return void
     */
    public function testParseRowReplacesKnownFieldPairUsingReplaceTagWithOriginalRowContext(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    10 => [
                        'row_id' => 10,
                        'orig_row_id' => 910,
                        'fluid_field_data_id' => 77,
                        'col_id_7' => 'title-value',
                        'col_id_8' => 44,
                    ],
                ],
            ],
            [
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'title', 'col_type' => 'text'],
                8 => ['field_id' => 11, 'col_id' => 8, 'col_name' => 'related_entry', 'col_type' => 'relationship'],
            ]
        );
        $parser = $this->makeGridParserReplaceTagSpy(function ($call) {
            if ($call['content'] !== false) {
                return 'PAIR_REPLACED';
            }

            return 'SINGLE_REPLACED';
        });
        $relationshipsParser = $this->makeRelationshipsParserMock(function ($rowId, $gridRow) {
            return $gridRow;
        });

        ee()->setMock('Variables/Parser', $this->makeGridVariablesParserMock());
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock(
            'api_channel_fields',
            $this->makeApiChannelFieldsMock([
                'title' => [['title', 'PAIR_CONTENT', ['limit' => '2'], '{grid:gallery:title}PAIR_CONTENT{/grid:gallery:title}']],
            ])
        );
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel([8 => 8]));
        ee()->setMock('relationships_parser', $relationshipsParser);

        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{grid:gallery:title}PAIR_CONTENT{/grid:gallery:title}'
        );

        $this->assertSame('PAIR_REPLACED', $result);
        $this->assertCount(1, $parser->replaceTagCalls);
        $this->assertSame(910, $parser->replaceTagCalls[0]['orig_row_id']);
        $this->assertSame(77, $parser->replaceTagCalls[0]['fluid_field_data_id']);
        $this->assertSame('title', $parser->replaceTagCalls[0]['column']['col_name']);
        $this->assertSame('title', $parser->replaceTagCalls[0]['field']['modifier']);
        $this->assertSame('PAIR_CONTENT', $parser->replaceTagCalls[0]['content']);
    }

    /**
     * Ensure parse falls back to row_id and zero fluid context when pair rows omit override keys.
     *
     * @return void
     */
    public function testParseRowReplacesKnownFieldPairUsingDefaultRowContextWhenOverridesAreMissing(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    10 => [
                        'row_id' => 10,
                        'col_id_7' => 'title-value',
                    ],
                ],
            ],
            [
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'title', 'col_type' => 'text'],
            ]
        );
        $parser = $this->makeGridParserReplaceTagSpy(function ($call) {
            return $call['content'] !== false ? 'PAIR_REPLACED' : 'SINGLE_REPLACED';
        });

        ee()->setMock('Variables/Parser', $this->makeGridVariablesParserMock());
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock(
            'api_channel_fields',
            $this->makeApiChannelFieldsMock([
                'title' => [['title', 'PAIR_CONTENT', ['limit' => '2'], '{gallery:title}PAIR_CONTENT{/gallery:title}']],
            ])
        );
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{gallery:title}PAIR_CONTENT{/gallery:title}'
        );

        $this->assertSame('PAIR_REPLACED', $result);
        $this->assertCount(1, $parser->replaceTagCalls);
        $this->assertSame(10, $parser->replaceTagCalls[0]['orig_row_id']);
        $this->assertSame(0, $parser->replaceTagCalls[0]['fluid_field_data_id']);
        $this->assertSame('PAIR_CONTENT', $parser->replaceTagCalls[0]['content']);
    }

    /**
     * Ensure parse routes known single tags through Grid replace_tag with default row context fallback.
     *
     * @return void
     */
    public function testParseRowReplacesKnownSingleVariableUsingDefaultRowContext(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    10 => [
                        'row_id' => 10,
                        'col_id_7' => 'title-value',
                    ],
                ],
            ],
            [
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'title', 'col_type' => 'text'],
            ]
        );
        $parser = $this->makeGridParserReplaceTagSpy(function ($call) {
            return 'SINGLE_REPLACED';
        });

        ee()->setMock('Variables/Parser', $this->makeGridVariablesParserMock());
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], '{grid:gallery:title}');

        $this->assertSame('SINGLE_REPLACED', $result);
        $this->assertCount(1, $parser->replaceTagCalls);
        $this->assertSame(10, $parser->replaceTagCalls[0]['orig_row_id']);
        $this->assertSame(0, $parser->replaceTagCalls[0]['fluid_field_data_id']);
        $this->assertFalse($parser->replaceTagCalls[0]['content']);
    }

    /**
     * Ensure parse keeps explicit original row and fluid context when replacing known single variables.
     *
     * @return void
     */
    public function testParseRowReplacesKnownSingleVariableUsingExplicitRowContextOverrides(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    10 => [
                        'row_id' => 10,
                        'orig_row_id' => 510,
                        'fluid_field_data_id' => 42,
                        'col_id_7' => 'title-value',
                    ],
                ],
            ],
            [
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'title', 'col_type' => 'text'],
            ]
        );
        $parser = $this->makeGridParserReplaceTagSpy(function ($call) {
            return 'SINGLE_REPLACED';
        });

        ee()->setMock('Variables/Parser', $this->makeGridVariablesParserMock());
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], '{gallery:title}');

        $this->assertSame('SINGLE_REPLACED', $result);
        $this->assertCount(1, $parser->replaceTagCalls);
        $this->assertSame(510, $parser->replaceTagCalls[0]['orig_row_id']);
        $this->assertSame(42, $parser->replaceTagCalls[0]['fluid_field_data_id']);
        $this->assertFalse($parser->replaceTagCalls[0]['content']);
    }

    /**
     * Ensure parse handles unknown pair columns, table value fallback, and parser modifiers.
     *
     * @return void
     */
    public function testParseRowHandlesUnknownPairsRowFallbackAndModifierFallback(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    44 => [
                        'row_id' => 44,
                        'caption' => 'hello world',
                    ],
                ],
            ],
            [
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'title', 'col_type' => 'text'],
            ]
        );

        $variablesParser = $this->makeGridVariablesParserMock();

        ee()->setMock('Variables/Parser', $variablesParser);
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock(
            'api_channel_fields',
            $this->makeApiChannelFieldsMock([
                'missing' => [['missing', 'MISSING_PAIR', [], '{grid:gallery:missing}MISSING_PAIR{/grid:gallery:missing}']],
            ])
        );
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(
            ['entry_id' => 5],
            11,
            [],
            '{grid:gallery:missing}MISSING_PAIR{/grid:gallery:missing}|{grid:gallery:row_id}|{grid:gallery:caption:length}'
        );

        $this->assertSame('|44|{grid:gallery:caption:length}', $result);
        $this->assertContains('caption:length', $variablesParser->parseCalls);
        $this->assertSame(0, $variablesParser->lengthCalls);
    }

    /**
     * Ensure parse leaves single variables untouched when a parser modifier function is unavailable.
     *
     * @return void
     */
    public function testParseRowLeavesSingleVariableWhenParserModifierMethodDoesNotExist(): void
    {
        $gridModel = $this->makeParseGridModelMock(
            [
                'params' => $this->makeParseParams(),
                5 => [
                    44 => [
                        'row_id' => 44,
                        'caption' => 'hello world',
                    ],
                ],
            ],
            [
                7 => ['field_id' => 11, 'col_id' => 7, 'col_name' => 'title', 'col_type' => 'text'],
            ]
        );
        ee()->setMock('Variables/Parser', $this->makeGridVariablesParserMock());
        ee()->setMock('grid_model', $gridModel);
        ee()->setMock('load', $this->makeParseLoadMock());
        ee()->setMock('TMPL', $this->makeParseTemplateMock());
        ee()->setMock('functions', $this->makeParseFunctionsMock());
        ee()->setMock('api_channel_fields', $this->makeApiChannelFieldsMock());
        ee()->setMock('session', $this->makeSessionMockWithActiveChannel());

        $parser = new \Grid_parser();
        $parser->grid_field_names[11][0] = 'grid:gallery';

        $result = $parser->parse(['entry_id' => 5], 11, [], '{grid:gallery:caption:unknown}');

        $this->assertSame('{grid:gallery:caption:unknown}', $result);
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

    /**
     * Build default params returned by grid_model->get_entry_rows().
     *
     * @param array $overrides Parameter values to merge.
     * @return array
     */
    private function makeParseParams(array $overrides = []): array
    {
        return array_merge(
            [
                'row_id' => 0,
                'offset' => 0,
                'limit' => 100,
                'orderby' => 'title',
            ],
            $overrides
        );
    }

    /**
     * Build a load mock that records model and library loading calls.
     *
     * @return object
     */
    private function makeParseLoadMock(): object
    {
        return new class extends \eeSingletonLoadMock {
            public $models = [];
            public $libraries = [];

            public function model($name = null)
            {
                $this->models[] = $name;
            }

            public function library($name = null)
            {
                $this->libraries[] = $name;
            }
        };
    }

    /**
     * Build a load mock that records package-path additions and removals for call() tests.
     *
     * @return object
     */
    private function makeCallLoadMock(): object
    {
        return new class extends \eeSingletonLoadMock {
            public $addPackagePathCalls = [];
            public $removePackagePathCalls = [];

            public function add_package_path($path, $viewCascade = true)
            {
                $this->addPackagePathCalls[] = [
                    'path' => $path,
                    'view_cascade' => $viewCascade,
                ];
            }

            public function remove_package_path($path = '')
            {
                $this->removePackagePathCalls[] = $path;
            }
        };
    }

    /**
     * Build a Grid model mock for parse() flow with configurable rows and columns.
     *
     * @param mixed $entryRowsResponse Return payload for get_entry_rows().
     * @param array $columns Columns returned by get_columns_for_field().
     * @return object
     */
    private function makeParseGridModelMock($entryRowsResponse, array $columns = []): object
    {
        return new class($entryRowsResponse, $columns) {
            public $entryRowsCalls = [];
            public $columnCalls = [];
            public $gridDataCalls = 0;
            private $entryRowsResponse;
            private $columns;

            public function __construct($entryRowsResponse, array $columns)
            {
                $this->entryRowsResponse = $entryRowsResponse;
                $this->columns = $columns;
            }

            public function get_entry_rows($entryIds, $fieldId, $contentType, $params, $cached = false, $fluidFieldDataId = 0)
            {
                $this->entryRowsCalls[] = [
                    'entry_ids' => $entryIds,
                    'field_id' => $fieldId,
                    'content_type' => $contentType,
                    'params' => $params,
                    'cached' => $cached,
                    'fluid_field_data_id' => $fluidFieldDataId,
                ];

                return $this->entryRowsResponse;
            }

            public function get_columns_for_field($fieldId, $contentType)
            {
                $this->columnCalls[] = [
                    'field_id' => $fieldId,
                    'content_type' => $contentType,
                ];

                return $this->columns;
            }

            public function get_grid_data()
            {
                $this->gridDataCalls++;

                return [];
            }
        };
    }

    /**
     * Build a TMPL mock that records switch parsing and relationship parser logs.
     *
     * @return object
     */
    private function makeParseTemplateMock(): object
    {
        return new class {
            public $no_results = '';
            public $parseSwitchCalls = [];
            public $logMessages = [];

            public function parse_switch($tagdata, $index, $prefix)
            {
                $this->parseSwitchCalls[] = [
                    'tagdata' => $tagdata,
                    'index' => $index,
                    'prefix' => $prefix,
                ];

                return $tagdata;
            }

            public function no_results()
            {
                return $this->no_results;
            }

            public function log_item($message)
            {
                $this->logMessages[] = $message;
            }
        };
    }

    /**
     * Build functions mock that records conditional compilation payloads.
     *
     * @return object
     */
    private function makeParseFunctionsMock(): object
    {
        return new class {
            public $prepConditionalCalls = [];

            public function prep_conditionals($tagdata, $cond)
            {
                $this->prepConditionalCalls[] = [
                    'tagdata' => $tagdata,
                    'cond' => $cond,
                ];

                return $tagdata;
            }
        };
    }

    /**
     * Build api_channel_fields mock with optional next/prev pair chunks keyed by modifier.
     *
     * @param array $pairsByModifier Pair-chunk payloads keyed by modifier.
     * @return object
     */
    private function makeApiChannelFieldsMock(array $pairsByModifier = []): object
    {
        return new class($pairsByModifier) {
            public $pairFieldCalls = [];
            private $pairsByModifier;

            public function __construct(array $pairsByModifier)
            {
                $this->pairsByModifier = $pairsByModifier;
            }

            public function get_pair_field($tagdata, $modifier, $prefix)
            {
                $this->pairFieldCalls[] = [
                    'tagdata' => $tagdata,
                    'modifier' => $modifier,
                    'prefix' => $prefix,
                ];

                if (isset($this->pairsByModifier[$modifier])) {
                    return $this->pairsByModifier[$modifier];
                }

                return [];
            }
        };
    }

    /**
     * Build an api_channel_fields mock for instantiate_fieldtype() tests.
     *
     * @param mixed $handler Fieldtype handler returned by setup_handler().
     * @param array $fieldTypes Preloaded field type registry keyed by type.
     * @param bool $populateTypeOnFetch Whether fetch_installed_fieldtypes should register text type.
     * @return object
     */
    private function makeInstantiateApiChannelFieldsMock($handler, array $fieldTypes, bool $populateTypeOnFetch = true): object
    {
        return new class($handler, $fieldTypes, $populateTypeOnFetch) {
            public $field_types = [];
            public $fetchInstalledFieldtypesCalls = 0;
            public $setupHandlerCalls = [];
            private $handler;
            private $populateTypeOnFetch;

            public function __construct($handler, array $fieldTypes, bool $populateTypeOnFetch)
            {
                $this->handler = $handler;
                $this->field_types = $fieldTypes;
                $this->populateTypeOnFetch = $populateTypeOnFetch;
            }

            public function fetch_installed_fieldtypes()
            {
                $this->fetchInstalledFieldtypesCalls++;

                if ($this->populateTypeOnFetch) {
                    $this->field_types['text'] = true;
                }
            }

            public function setup_handler($colType, $cache = false)
            {
                $this->setupHandlerCalls[] = [
                    'type' => $colType,
                    'cache' => $cache,
                ];

                return $this->handler;
            }
        };
    }

    /**
     * Build an api_channel_fields mock for call() method tests.
     *
     * @param array $methodExistsMap Method existence map keyed by method name.
     * @param mixed $applyResult Value returned by apply().
     * @param string $fieldtypePath Package path selected for the current fieldtype.
     * @param string $fieldType Current field type key.
     * @return object
     */
    private function makeCallApiChannelFieldsMock(array $methodExistsMap, $applyResult, string $fieldtypePath = '/fieldtypes/path', string $fieldType = 'text'): object
    {
        return new class($methodExistsMap, $applyResult, $fieldtypePath, $fieldType) {
            public $ft_paths = [];
            public $field_type;
            public $checkMethodExistsCalls = [];
            public $applyCalls = [];
            private $methodExistsMap;
            private $applyResult;

            public function __construct(array $methodExistsMap, $applyResult, string $fieldtypePath, string $fieldType)
            {
                $this->methodExistsMap = $methodExistsMap;
                $this->applyResult = $applyResult;
                $this->field_type = $fieldType;
                $this->ft_paths[$fieldType] = $fieldtypePath;
            }

            public function check_method_exists($method)
            {
                $this->checkMethodExistsCalls[] = $method;

                return $this->methodExistsMap[$method] ?? false;
            }

            public function apply($method, $data)
            {
                $this->applyCalls[] = [
                    'method' => $method,
                    'data' => $data,
                ];

                return $this->applyResult;
            }
        };
    }

    /**
     * Build a fieldtype handler mock that records _init payloads.
     *
     * @return object
     */
    private function makeFieldtypeHandlerMock(): object
    {
        return new class {
            public $initCalls = [];
            public $settings = [];

            public function _init($params)
            {
                $this->initCalls[] = $params;
            }
        };
    }

    /**
     * Build a legacy_api mock that records instantiate() calls.
     *
     * @return object
     */
    private function makeLegacyApiMock(): object
    {
        return new class {
            public $instantiateCalls = [];

            public function instantiate($name)
            {
                $this->instantiateCalls[] = $name;
            }
        };
    }

    /**
     * Build session mock that returns an active channel object for relationship parsing.
     *
     * @param array $rfields Active relationship field mapping.
     * @return object
     */
    private function makeSessionMockWithActiveChannel(array $rfields = []): object
    {
        return new class($rfields) {
            private $activeChannel;

            public function __construct(array $rfields)
            {
                $this->activeChannel = (object) ['rfields' => $rfields];
            }

            public function cache($class, $key, $value = null)
            {
                if ($class === 'mod_channel' && $key === 'active' && $value === null) {
                    return $this->activeChannel;
                }
            }
        };
    }

    /**
     * Build a relationships parser mock with configurable parse callback behavior.
     *
     * @param callable $parseCallback Callback used by parse(row_id, grid_row, channel).
     * @return object
     */
    private function makeRelationshipsParserMock(callable $parseCallback): object
    {
        return new class($parseCallback) {
            public $createCalls = [];
            private $parseCallback;

            public function __construct(callable $parseCallback)
            {
                $this->parseCallback = $parseCallback;
            }

            public function create($rfields, $rowIds, $tagdata, $relationships, $fieldId, $fluidFieldDataId)
            {
                $this->createCalls[] = [
                    'rfields' => $rfields,
                    'row_ids' => $rowIds,
                    'tagdata' => $tagdata,
                    'relationships' => $relationships,
                    'field_id' => $fieldId,
                    'fluid_field_data_id' => $fluidFieldDataId,
                ];

                return $this;
            }

            public function parse($rowId, $gridRow, $channel)
            {
                return call_user_func($this->parseCallback, $rowId, $gridRow, $channel);
            }
        };
    }

    /**
     * Build a Variables/Parser mock that parses field modifiers and supports uppercase replacement.
     *
     * @return object
     */
    private function makeGridVariablesParserMock(): object
    {
        return new class {
            public $parseCalls = [];
            public $lengthCalls = 0;

            public function parseVariableProperties($properties, $fieldName = null)
            {
                $properties = ltrim(trim((string) $properties), ':');
                $this->parseCalls[] = $properties;
                $token = trim((string) strtok($properties, ' '));

                $field = $token;
                $modifier = '';

                if (strpos($token, ':') !== false) {
                    list($field, $modifier) = explode(':', $token, 2);
                }

                return [
                    'field_name' => $field,
                    'modifier' => $modifier,
                    'params' => [],
                ];
            }

            public function replace_upper($value, $params)
            {
                return strtoupper((string) $value);
            }

            public function replace_length($value, $params)
            {
                $this->lengthCalls++;
                return strlen((string) $value);
            }
        };
    }

    /**
     * Build a Grid parser spy that captures _replace_tag calls and returns callback output.
     *
     * @param callable $callback Callback receiving call payload and returning replacement text.
     * @return object
     */
    private function makeGridParserReplaceTagSpy(callable $callback): object
    {
        return new class($callback) extends \Grid_parser {
            public $replaceTagCalls = [];
            private $callback;

            public function __construct(callable $callback)
            {
                parent::__construct();
                $this->callback = $callback;
            }

            protected function _replace_tag($column, $field_id, $entry_id, $row_id, $field, $data, $content = false, $content_type = 'channel', $orig_row_id = null, $fluid_field_data_id = 0)
            {
                $call = [
                    'column' => $column,
                    'field_id' => $field_id,
                    'entry_id' => $entry_id,
                    'row_id' => $row_id,
                    'field' => $field,
                    'data' => $data,
                    'content' => $content,
                    'content_type' => $content_type,
                    'orig_row_id' => $orig_row_id,
                    'fluid_field_data_id' => $fluid_field_data_id,
                ];

                $this->replaceTagCalls[] = $call;

                return call_user_func($this->callback, $call);
            }
        };
    }
}
