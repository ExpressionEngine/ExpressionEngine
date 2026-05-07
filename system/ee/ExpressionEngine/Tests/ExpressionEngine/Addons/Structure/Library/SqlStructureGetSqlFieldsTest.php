<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetSqlFieldsTest extends TestCase
{
    /**
     * Reset singleton mocks between test runs.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    /**
     * Confirm the tests exercise the real Structure module file from PATH_ADDONS.
     *
     * @return void
     */
    public function testSqlStructureLoadsFromPathAddonsTargetFile(): void
    {
        $reflection = new ReflectionClass('Sql_structure');

        $this->assertSame(
            realpath(PATH_ADDONS . 'structure/sql.structure.php'),
            $reflection->getFileName()
        );
    }

    /**
     * It maps a requested title field when the field exists only for the current site.
     *
     * @return void
     */
    public function testCreateCustomTitlesUsesSiteOnlyCustomFieldMap(): void
    {
        $captured = (object) [
            'libraries' => [],
            'instantiations' => [],
            'fetches' => [],
            'channel_filters' => [],
            'site_filters' => [],
            'selected_fields' => [],
        ];

        $this->primeCreateCustomTitlesEnvironment(
            'blog:headline',
            [1 => ['headline' => 42]],
            [
                ['channel_id' => 2, 'channel_name' => 'blog'],
            ],
            [
                (object) [
                    'entry_id' => 10,
                    'channel_id' => 2,
                    'site_id' => 1,
                    'title' => 'Default Blog',
                    'field_id_42' => 'Site Only Headline',
                ],
            ],
            $captured
        );

        $sql = $this->makeSqlWithStructureChannels([
            2 => ['channel_id' => 2],
        ]);
        $titles = $sql->create_custom_titles();

        $this->assertSame([10 => 'Site Only Headline'], $titles);
        $this->assertSame(['api'], $captured->libraries);
        $this->assertSame(['channel_fields'], $captured->instantiations);
        $this->assertSame([['blog:headline']], $captured->fetches);
        $this->assertSame([[2]], $captured->channel_filters);
    }

    /**
     * It prefers the site-specific field id when a global field uses the same name.
     *
     * @return void
     */
    public function testCreateCustomTitlesPrefersSiteFieldOverGlobalFieldWithSameName(): void
    {
        $captured = (object) [
            'libraries' => [],
            'instantiations' => [],
            'fetches' => [],
            'channel_filters' => [],
            'site_filters' => [],
            'selected_fields' => [],
        ];

        $this->primeCreateCustomTitlesEnvironment(
            'blog:headline',
            [
                0 => ['headline' => 12],
                1 => ['headline' => 99],
            ],
            [
                ['channel_id' => 2, 'channel_name' => 'blog'],
            ],
            [
                (object) [
                    'entry_id' => 10,
                    'channel_id' => 2,
                    'site_id' => 1,
                    'title' => 'Default Blog',
                    'field_id_12' => 'Global Headline',
                    'field_id_99' => 'Site Override Headline',
                ],
            ],
            $captured
        );

        $sql = $this->makeSqlWithStructureChannels([
            2 => ['channel_id' => 2],
        ]);
        $titles = $sql->create_custom_titles();

        $this->assertSame([10 => 'Site Override Headline'], $titles);
        $this->assertSame(['api'], $captured->libraries);
        $this->assertSame(['channel_fields'], $captured->instantiations);
        $this->assertSame([['blog:headline']], $captured->fetches);
        $this->assertSame([[2]], $captured->channel_filters);
    }

    /**
     * It returns an empty SQL field map when no title fields reach the private helper.
     *
     * @return void
     */
    public function testGetSqlFieldsReturnsEmptyArrayWhenNoTitleFieldsAreRequested(): void
    {
        $captured = (object) [
            'libraries' => [],
            'instantiations' => [],
            'fetches' => [],
            'channel_filters' => [],
            'site_filters' => [],
            'selected_fields' => [],
        ];

        $this->primeCreateCustomTitlesEnvironment(
            'blog:headline',
            [1 => ['headline' => 42]],
            [],
            [],
            $captured
        );

        $sql = $this->makeSqlWithStructureChannels([]);
        $method = new ReflectionMethod('Sql_structure', '_get_sql_fields');
        \TestReflectionHelper::makeAccessible($method);
        $sqlFields = $method->invoke($sql, ['blog:headline'], []);

        $this->assertSame([], $sqlFields);
        $this->assertSame(['api'], $captured->libraries);
        $this->assertSame(['channel_fields'], $captured->instantiations);
        $this->assertSame([['blog:headline']], $captured->fetches);
        $this->assertSame([], $captured->channel_filters);
    }

    /**
     * It falls back to entry titles when the custom field is empty or missing.
     *
     * @return void
     */
    public function testCreateCustomTitlesFallsBackToEntryTitlesWhenCustomFieldValueIsUnavailable(): void
    {
        $captured = (object) [
            'libraries' => [],
            'instantiations' => [],
            'fetches' => [],
            'channel_filters' => [],
            'site_filters' => [],
            'selected_fields' => [],
        ];

        $this->primeCreateCustomTitlesEnvironment(
            'blog:headline',
            [1 => ['headline' => 42]],
            [
                ['channel_id' => 2, 'channel_name' => 'blog'],
            ],
            [
                (object) [
                    'entry_id' => 10,
                    'channel_id' => 2,
                    'site_id' => 1,
                    'title' => 'Fallback Empty Value',
                    'field_id_42' => '',
                ],
                (object) [
                    'entry_id' => 20,
                    'channel_id' => 3,
                    'site_id' => 1,
                    'title' => 'Fallback Missing Mapping',
                ],
                (object) [
                    'entry_id' => 30,
                    'channel_id' => 2,
                    'site_id' => 2,
                    'title' => 'Other Site Title',
                    'field_id_42' => 'Other Site Headline',
                ],
            ],
            $captured
        );

        $sql = $this->makeSqlWithStructureChannels([
            2 => ['channel_id' => 2],
            3 => ['channel_id' => 3],
        ]);
        $titles = $sql->create_custom_titles();

        $this->assertSame([
            10 => 'Fallback Empty Value',
            20 => 'Fallback Missing Mapping',
        ], $titles);
        $this->assertSame([[2, 3]], $captured->channel_filters);
        $this->assertSame([1], $captured->site_filters);
        $this->assertSame([
            ['entry_id', 'channel_id', 'site_id', 'title'],
            ['field_id_42'],
        ], $captured->selected_fields);
    }

    /**
     * Prime the mocks that drive create_custom_titles() through the real helper chain.
     *
     * @param string $channelTitleParam
     * @param array $customChannelFields
     * @param array $channelRows
     * @param array $entries
     * @param object $captured
     * @return void
     */
    private function primeCreateCustomTitlesEnvironment(
        string $channelTitleParam,
        array $customChannelFields,
        array $channelRows,
        array $entries,
        object $captured
    ): void {
        ee()->setMock('TMPL', new class($channelTitleParam) {
            private $channelTitleParam;

            /**
             * Store the configured template parameter.
             *
             * @param string $channelTitleParam
             * @return void
             */
            public function __construct(string $channelTitleParam)
            {
                $this->channelTitleParam = $channelTitleParam;
            }

            /**
             * Return the configured custom title parameter.
             *
             * @param string $key
             * @param mixed $default
             * @return mixed
             */
            public function fetch_param($key, $default = false)
            {
                if ($key === 'channel:title') {
                    return $this->channelTitleParam;
                }

                return $default;
            }
        });
        ee()->setMock('extensions', new class {
            /**
             * Leave the title hook disabled for helper coverage assertions.
             *
             * @param string $name
             * @return bool
             */
            public function active_hook($name): bool
            {
                return false;
            }

            /**
             * Return the unmodified hook payload.
             *
             * @param string $name
             * @param mixed $value
             * @return mixed
             */
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('load', new class($captured) {
            private $captured;

            /**
             * Store the shared call capture object.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record the requested library name.
             *
             * @param string $name
             * @return void
             */
            public function library($name): void
            {
                $this->captured->libraries[] = $name;
            }
        });
        ee()->setMock('legacy_api', new class($captured) {
            private $captured;

            /**
             * Store the shared call capture object.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record the requested legacy API namespace.
             *
             * @param string $name
             * @return void
             */
            public function instantiate($name): void
            {
                $this->captured->instantiations[] = $name;
            }
        });
        ee()->setMock('api_channel_fields', new class($captured, $customChannelFields) {
            private $captured;
            private $customChannelFields;

            /**
             * Store the shared call capture object and fake field map.
             *
             * @param object $captured
             * @param array $customChannelFields
             * @return void
             */
            public function __construct(object $captured, array $customChannelFields)
            {
                $this->captured = $captured;
                $this->customChannelFields = $customChannelFields;
            }

            /**
             * Return the configured custom field map for the helper under test.
             *
             * @param array $customTitles
             * @return array
             */
            public function fetch_custom_channel_fields($customTitles): array
            {
                $this->captured->fetches[] = $customTitles;

                return ['custom_channel_fields' => $this->customChannelFields];
            }
        });
        ee()->setMock('db', new class($channelRows) {
            private $channelRows;

            /**
             * Store the fake channel lookup rows.
             *
             * @param array $channelRows
             * @return void
             */
            public function __construct(array $channelRows)
            {
                $this->channelRows = $channelRows;
            }

            /**
             * Return the configured channel rows for custom title lookups.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                return new class($this->channelRows) {
                    private $channelRows;

                    /**
                     * Store the fake query rows.
                     *
                     * @param array $channelRows
                     * @return void
                     */
                    public function __construct(array $channelRows)
                    {
                        $this->channelRows = $channelRows;
                    }

                    /**
                     * Return the channel lookup rows.
                     *
                     * @return array
                     */
                    public function result_array(): array
                    {
                        return $this->channelRows;
                    }
                };
            }
        });
        ee()->setMock('Model', new class($captured, $entries) {
            private $captured;
            private $entries;

            /**
             * Store the shared call capture object and fake entries.
             *
             * @param object $captured
             * @param array $entries
             * @return void
             */
            public function __construct(object $captured, array $entries)
            {
                $this->captured = $captured;
                $this->entries = $entries;
            }

            /**
             * Return a fake ChannelEntry model query builder.
             *
             * @param string $model
             * @return object
             */
            public function get($model)
            {
                return new class($this->captured, $this->entries) {
                    private $captured;
                    private $entries;
                    private $channelIds = [];
                    private $siteId;

                    /**
                     * Store shared capture state and fake entries.
                     *
                     * @param object $captured
                     * @param array $entries
                     * @return void
                     */
                    public function __construct(object $captured, array $entries)
                    {
                        $this->captured = $captured;
                        $this->entries = $entries;
                    }

                    /**
                     * Ignore selected field lists for the fake query builder.
                     *
                     * @param mixed ...$args
                     * @return self
                     */
                    public function fields(...$args)
                    {
                        $this->captured->selected_fields[] = $args;

                        return $this;
                    }

                    /**
                     * Capture channel filters applied by get_page_titles().
                     *
                     * @param string $field
                     * @param string $operator
                     * @param mixed $value
                     * @return self
                     */
                    public function filter($field, $operator, $value)
                    {
                        if ($field === 'channel_id' && $operator === 'IN') {
                            $this->channelIds = $value;
                            $this->captured->channel_filters[] = $value;
                        }

                        if ($field === 'site_id' && $operator === '==') {
                            $this->siteId = $value;
                            $this->captured->site_filters[] = $value;
                        }

                        return $this;
                    }

                    /**
                     * Return only the entries selected by the structure channel filter.
                     *
                     * @return array
                     */
                    public function all(): array
                    {
                        return array_values(array_filter($this->entries, function ($entry) {
                            if (!empty($this->channelIds) && !in_array($entry->channel_id, $this->channelIds, true)) {
                                return false;
                            }

                            if (isset($this->siteId) && $entry->site_id !== $this->siteId) {
                                return false;
                            }

                            return true;
                        }));
                    }
                };
            }
        });
    }

    /**
     * Build a Sql_structure instance that returns the supplied Structure channels.
     *
     * @param array $structureChannels
     * @return Sql_structure
     */
    private function makeSqlWithStructureChannels(array $structureChannels): Sql_structure
    {
        $sql = new class($structureChannels) extends Sql_structure {
            private $structureChannels;

            /**
             * Store the fake Structure channels.
             *
             * @param array $structureChannels
             * @return void
             */
            public function __construct(array $structureChannels)
            {
                $this->structureChannels = $structureChannels;
            }

            /**
             * Return the configured Structure channels for page lookups.
             *
             * @param string $type
             * @param string|int $channel_id
             * @param string $order
             * @param bool $selector
             * @return array
             */
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return $this->structureChannels;
            }
        };

        $sql->site_id = 1;
        $sql->cache = [];

        return $sql;
    }
}
