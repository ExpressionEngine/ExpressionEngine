<?php

require_once __DIR__ . '/PagesTestBase.php';
require_once __DIR__ . '/../../../../Addons/pages/tab.pages.php';

use ExpressionEngine\Model\Channel\ChannelEntry;

if (!class_exists('PagesTabChannelEntryStub')) {
    class PagesTabChannelEntryStub extends ChannelEntry
    {
        public $entry_id;
        public $title;
        public function __construct(int $entryId = 0, string $title = '')
        {
            $this->entry_id = $entryId;
            $this->title = $title;
        }
    }
}

class PagesTabTest extends PagesTestBase
{
    public function testDisplayBuildsSettingsForExistingEntry(): void
    {
        $captured = (object) ['setSettings' => []];

        ee()->setMock('lang', new class {
            public function loadfile($file)
            {
            }
            public function load($file)
            {
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_url') {
                    return 'https://example.com/';
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'uris' => [44 => '/about'],
                            'templates' => [44 => 9],
                        ],
                    ];
                }
                return null;
            }
        });
        ee()->setMock('db', new class {
            public function select($field)
            {
                return $this;
            }
            public function where($field, $value)
            {
                return $this;
            }
            public function get($table)
            {
                return new eeDbResultMock([]);
            }
        });
        ee()->setMock('template_model', new class {
            public function get_templates($siteId)
            {
                return new class {
                    public function result()
                    {
                        return [
                            (object) ['group_name' => 'site', 'template_id' => 9, 'template_name' => 'index'],
                            (object) ['group_name' => 'blog', 'template_id' => 12, 'template_name' => 'entry'],
                        ];
                    }
                    public function num_rows()
                    {
                        return 2;
                    }
                };
            }
        });
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
        });
        ee()->setMock('api_channel_fields', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function set_settings($key, $value)
            {
                $this->captured->setSettings[] = [$key, $value];
            }
        });
        ee()->setMock('View', new class {
            public function make($view)
            {
                return new class {
                    public function render($vars = [])
                    {
                        return 'rendered';
                    }
                };
            }
        });

        $tab = new Pages_tab();
        $settings = $tab->display(3, 44);

        $this->assertSame('/about', $settings['pages_uri']['field_data']);
        $this->assertSame(9, $settings['pages_template_id']['selected']);
        $this->assertCount(2, $captured->setSettings);
    }

    public function testDisplayBuildsNoTemplatesMessageForNewEntry(): void
    {
        ee()->setMock('lang', new class {
            public function loadfile($file)
            {
            }
            public function load($file)
            {
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_url') {
                    return 'https://example.com/';
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [], 'templates' => []]];
                }
                return null;
            }
        });
        ee()->setMock('db', new class {
            public function select($field)
            {
                return $this;
            }
            public function where($field, $value)
            {
                return $this;
            }
            public function get($table)
            {
                return new eeDbResultMock([['configuration_value' => '17']]);
            }
        });
        ee()->setMock('template_model', new class {
            public function get_templates($siteId)
            {
                return new class {
                    public function result()
                    {
                        return [];
                    }
                    public function num_rows()
                    {
                        return 0;
                    }
                };
            }
        });
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
        });
        ee()->setMock('api_channel_fields', new class {
            public function set_settings($key, $value)
            {
            }
        });
        ee()->setMock('View', new class {
            public function make($view)
            {
                return new class {
                    public function render($vars = [])
                    {
                        return 'no-templates';
                    }
                };
            }
        });
        ee()->setMock('CP/URL', new class {
            public function __invoke($path)
            {
                return 'cp://' . $path;
            }
        });

        $tab = new Pages_tab();
        $settings = $tab->display(9, 0);

        $this->assertSame(17, $settings['pages_template_id']['selected']);
        $this->assertSame('no-templates', $settings['pages_template_id']['string_override']);
    }

    public function testDisplayUsesDefaultTemplateWhenEditingEntryWithoutExistingPageUri(): void
    {
        ee()->setMock('lang', new class {
            public function loadfile($file)
            {
            }
            public function load($file)
            {
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_url') {
                    return 'https://example.com/';
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [], 'templates' => []]];
                }
                return null;
            }
        });
        ee()->setMock('db', new class {
            public function select($field)
            {
                return $this;
            }
            public function where($field, $value)
            {
                return $this;
            }
            public function get($table)
            {
                return new eeDbResultMock([['configuration_value' => '21']]);
            }
        });
        ee()->setMock('template_model', new class {
            public function get_templates($siteId)
            {
                return new class {
                    public function result()
                    {
                        return [
                            (object) ['group_name' => 'site', 'template_id' => 21, 'template_name' => 'default'],
                        ];
                    }
                    public function num_rows()
                    {
                        return 1;
                    }
                };
            }
        });
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
        });
        ee()->setMock('api_channel_fields', new class {
            public function set_settings($key, $value)
            {
            }
        });

        $tab = new Pages_tab();
        $settings = $tab->display(5, 44);

        $this->assertSame('', $settings['pages_uri']['field_data']);
        $this->assertSame(21, $settings['pages_template_id']['selected']);
    }

    public function testCloneDataReturnsUnchangedWhenUriEmpty(): void
    {
        $entry = new PagesTabChannelEntryStub(10);
        $tab = new Pages_tab();
        $values = ['pages_uri' => '', 'pages_template_id' => 1];

        $this->assertSame($values, $tab->cloneData($entry, $values));
    }

    public function testCloneDataPrefixesDuplicateUriUntilUnique(): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [11 => '/foo', 12 => '/copy_foo', 33 => '/self']]];
                }
                return null;
            }
        });

        $entry = new PagesTabChannelEntryStub(33);
        $tab = new Pages_tab();

        $values = $tab->cloneData($entry, ['pages_uri' => 'foo', 'pages_template_id' => 2]);
        $this->assertSame('copy_foo', $values['pages_uri']);
        $this->assertSame('copy_foo', $_POST['pages__pages_uri']);
    }

    public function testValidateRegistersRulesAndEvaluatesWhenUriSkipRule(): void
    {
        $captured = (object) ['rules' => [], 'defined' => [], 'validated' => null];

        ee()->setMock('Validation', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function make($rules)
            {
                $this->captured->rules = $rules;
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function defineRule($name, $closure)
                    {
                        $this->captured->defined[$name] = $closure;
                    }
                    public function validate($values)
                    {
                        $this->captured->validated = $values;
                        return 'validation-result';
                    }
                };
            }
        });
        $_POST = ['pages__pages_uri' => ''];

        $tab = new Pages_tab();
        $result = $tab->validate(new PagesTabChannelEntryStub(7), ['pages_uri' => '/x', 'pages_template_id' => 2]);

        $this->assertSame('validation-result', $result);
        $this->assertArrayHasKey('whenURI', $captured->defined);

        $rule = new class {
            public function skip()
            {
                return 'SKIPPED';
            }
        };
        $this->assertSame('SKIPPED', $captured->defined['whenURI']('pages_template_id', '', [], $rule));

        $_POST = ['pages__pages_uri' => '/real'];
        $captured2 = (object) ['rules' => [], 'defined' => [], 'validated' => null];
        ee()->setMock('Validation', new class($captured2) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function make($rules)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function defineRule($name, $closure)
                    {
                        $this->captured->defined[$name] = $closure;
                    }
                    public function validate($values)
                    {
                        return 'ok';
                    }
                };
            }
        });
        $tab->validate(new PagesTabChannelEntryStub(7), ['pages_uri' => '/x', 'pages_template_id' => 2]);
        $this->assertTrue($captured2->defined['whenURI']('pages_template_id', '', [], $rule));
    }

    public function testValidateWhenUriRuleSkipsForExamplePlaceholder(): void
    {
        $captured = (object) ['defined' => []];
        ee()->setMock('Validation', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function make($rules)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function defineRule($name, $closure)
                    {
                        $this->captured->defined[$name] = $closure;
                    }
                    public function validate($values)
                    {
                        return true;
                    }
                };
            }
        });
        $_POST = ['pages__pages_uri' => 'example_uri'];

        $tab = new Pages_tab();
        $tab->validate(new PagesTabChannelEntryStub(7), ['pages_uri' => '/x', 'pages_template_id' => 2]);

        $rule = new class {
            public function skip()
            {
                return 'SKIPPED';
            }
        };
        $this->assertSame('SKIPPED', $captured->defined['whenURI']('pages_template_id', '', [], $rule));
    }

    public function testValidationClosuresCoverErrorAndSuccessPaths(): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_url') {
                    return 'https://example.com/';
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [8 => '/dupe', 9 => '/other']]];
                }
                return null;
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity, $id = null)
            {
                return new class {
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function first()
                    {
                        return new PagesTabChannelEntryStub(8, 'Duplicate <Page>');
                    }
                };
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return 'cp://' . $path;
            }
        });

        $tab = new Pages_tab();

        $hasTemplateRule = $this->invokePrivate($tab, 'makeValidHasTemplateRule', [['pages_uri' => '', 'pages_template_id' => '']]);
        $this->assertSame('invalid_template', $hasTemplateRule('field', ''));

        $hasTemplateRule2 = $this->invokePrivate($tab, 'makeValidHasTemplateRule', [['pages_uri' => '/x', 'pages_template_id' => '']]);
        $this->assertSame('invalid_template', $hasTemplateRule2('field', ''));
        $hasTemplateRule3 = $this->invokePrivate($tab, 'makeValidHasTemplateRule', [['pages_uri' => '/x', 'pages_template_id' => '3']]);
        $this->assertTrue($hasTemplateRule3('field', '3'));

        $validTemplateRule = $this->invokePrivate($tab, 'makeValidTemplateRule', [['pages_uri' => '/x']]);
        $this->assertSame('invalid_template', $validTemplateRule('pages_template_id', 'abc'));
        $this->assertTrue($validTemplateRule('pages_template_id', 5));

        $uriRule = $this->invokePrivate($tab, 'makeValidURIRule');
        $this->assertTrue($uriRule('pages_uri', ''));
        $this->assertSame('invalid_page_uri', $uriRule('pages_uri', '/bad$'));
        $this->assertTrue($uriRule('pages_uri', '/good.path-ok_1'));

        $segRule = $this->invokePrivate($tab, 'makeValidSegmentCountRule');
        $this->assertTrue($segRule('pages_uri', ''));
        $this->assertSame('invalid_page_num_segs', $segRule('pages_uri', '/a/b/c/d/e/f/g/h/i/j'));
        $this->assertTrue($segRule('pages_uri', '/a/b/c/d/e/f/g/h/i'));

        $notDupRule = $this->invokePrivate($tab, 'makeNotDuplicatedRule', [new PagesTabChannelEntryStub(9)]);
        $this->assertTrue($notDupRule('pages_uri', ''));
        $message = $notDupRule('pages_uri', '/dupe');
        $this->assertSame('duplicate_page_uri_used', $message);
    }

    /**
     * @dataProvider validUriRuleProvider
     */
    public function testMakeValidURIRuleMatrix(string $value, $expected): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_url') {
                    return 'https://example.com/';
                }
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [], 'templates' => []]];
                }
                return null;
            }
        });

        $tab = new Pages_tab();
        $rule = $this->invokePrivate($tab, 'makeValidURIRule');
        $this->assertSame($expected, $rule('pages_uri', $value));
    }

    public function validUriRuleProvider(): array
    {
        return [
            'empty' => ['', true],
            'normal path' => ['/docs/page', true],
            'full site url currently invalid' => ['https://example.com/docs/page', 'invalid_page_uri'],
            'trailing invalid char' => ['/docs/page$', 'invalid_page_uri'],
            'trailing invalid punctuation sequence' => ['/docs/page!!', 'invalid_page_uri'],
            'embedded encoded slash accepted by current regex' => ['/docs/%2F/page', true],
        ];
    }

    /**
     * @dataProvider validSegmentCountProvider
     */
    public function testMakeValidSegmentCountRuleMatrix(string $value, $expected): void
    {
        $tab = new Pages_tab();
        $rule = $this->invokePrivate($tab, 'makeValidSegmentCountRule');
        $this->assertSame($expected, $rule('pages_uri', $value));
    }

    public function validSegmentCountProvider(): array
    {
        return [
            'empty' => ['', true],
            'exactly 9 segments allowed' => ['/a/b/c/d/e/f/g/h/i', true],
            '10 segments invalid' => ['/a/b/c/d/e/f/g/h/i/j', 'invalid_page_num_segs'],
            'single segment' => ['/one', true],
        ];
    }

    public function testMakeValidTemplateRuleSkipsChecksWhenSitePagesDisabled(): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return $key === 'site_pages' ? false : null;
            }
        });

        $tab = new Pages_tab();
        $rule = $this->invokePrivate($tab, 'makeValidTemplateRule', [['pages_uri' => '/x']]);
        $this->assertTrue($rule('pages_template_id', null));
    }

    public function testMakeNotDuplicatedRuleReturnsTrueWhenEntryNoLongerExists(): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [8 => '/dupe']]];
                }
                return null;
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity, $id = null)
            {
                return new class {
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function first()
                    {
                        return null;
                    }
                };
            }
        });

        $tab = new Pages_tab();
        $rule = $this->invokePrivate($tab, 'makeNotDuplicatedRule', [new PagesTabChannelEntryStub(1)]);
        $this->assertTrue($rule('pages_uri', '/dupe'));
    }

    public function testPrepareSitePagesDataSanitizesAndStoresData(): void
    {
        ee()->setMock('config', new class {
            private $items = [
                'site_id' => 1,
                'site_url' => 'https://example.com/',
                'site_pages' => [1 => ['uris' => [], 'templates' => []]],
            ];
            public function item($key)
            {
                return $this->items[$key] ?? null;
            }
        });

        $tab = new Pages_tab();
        $entry = new PagesTabChannelEntryStub(50);
        $data = $tab->prepareSitePagesData($entry, ['pages_uri' => 'https://example.com//', 'pages_template_id' => '12']);

        $this->assertSame('/', $data[1]['uris'][50]);
        $this->assertSame('12', $data[1]['templates'][50]);
    }

    public function testPrepareSitePagesDataReturnsFalseWhenSitePagesConfigDisabled(): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return false;
                }
                if ($key === 'site_url') {
                    return 'https://example.com/';
                }
                return null;
            }
        });

        $tab = new Pages_tab();
        $entry = new PagesTabChannelEntryStub(88);
        $this->assertFalse($tab->prepareSitePagesData($entry, ['pages_uri' => '/x', 'pages_template_id' => '5']));
    }

    public function testPrepareSitePagesDataNormalizesDoubleSlashGuardBranch(): void
    {
        $uriStore = new class extends ArrayObject {
            private $forceDoubleSlashOnFirstWrite = true;
            public function offsetSet($offset, $value): void
            {
                if ($this->forceDoubleSlashOnFirstWrite) {
                    parent::offsetSet($offset, '//');
                    $this->forceDoubleSlashOnFirstWrite = false;
                    return;
                }

                parent::offsetSet($offset, $value);
            }
        };

        ee()->setMock('config', new class($uriStore) {
            private $uriStore;
            public function __construct($uriStore)
            {
                $this->uriStore = $uriStore;
            }
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_url') {
                    return 'https://example.com/';
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'uris' => $this->uriStore,
                            'templates' => [],
                        ],
                    ];
                }
                return null;
            }
        });

        $tab = new Pages_tab();
        $entry = new PagesTabChannelEntryStub(91);
        $data = $tab->prepareSitePagesData($entry, ['pages_uri' => '/forced', 'pages_template_id' => '9']);

        $this->assertSame('/', $data[1]['uris'][91]);
        $this->assertSame('9', $data[1]['templates'][91]);
    }

    public function testPrepareSitePagesDataSkipsInvalidInputs(): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [], 'templates' => []]];
                }
                return null;
            }
        });

        $tab = new Pages_tab();
        $entry = new PagesTabChannelEntryStub(60);
        $original = [1 => ['uris' => [], 'templates' => []]];

        $this->assertSame(
            $original,
            $tab->prepareSitePagesData($entry, ['pages_uri' => 'example_uri', 'pages_template_id' => 5])
        );
        $this->assertSame(
            $original,
            $tab->prepareSitePagesData($entry, ['pages_uri' => '/ok', 'pages_template_id' => 'not-numeric'])
        );
    }

    public function testSavePersistsWhenSitePagesAvailableAndSkipsWhenNot(): void
    {
        $captured = (object) ['setItems' => [], 'saved' => 0];

        ee()->setMock('config', new class($captured) {
            private $captured;
            private $items;
            public function __construct($captured)
            {
                $this->captured = $captured;
                $this->items = [
                    'site_id' => 1,
                    'site_url' => 'https://example.com/',
                    'site_pages' => [1 => ['uris' => [], 'templates' => []]],
                ];
            }
            public function item($key)
            {
                return $this->items[$key] ?? null;
            }
            public function set_item($key, $value)
            {
                $this->captured->setItems[] = [$key, $value];
            }
        });
        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity, $id = null)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function first()
                    {
                        return new class($this->captured) {
                            private $captured;
                            public $site_pages = [];
                            public function __construct($captured)
                            {
                                $this->captured = $captured;
                            }
                            public function save()
                            {
                                $this->captured->saved++;
                            }
                        };
                    }
                };
            }
        });

        $tab = new Pages_tab();
        $tab->save(new PagesTabChannelEntryStub(7), ['pages_uri' => '/x', 'pages_template_id' => 3]);
        $this->assertNotEmpty($captured->setItems);
        $this->assertSame(1, $captured->saved);

        $tabNoPages = new class extends Pages_tab {
            public function prepareSitePagesData($entry, $values)
            {
                return false;
            }
        };
        $tabNoPages->save(new PagesTabChannelEntryStub(8), ['pages_uri' => '/y', 'pages_template_id' => 4]);
        $this->assertSame(1, $captured->saved);
    }

    public function testDeleteRemovesEntryIdsFromSitePagesAndSaves(): void
    {
        $captured = (object) ['saved' => 0];
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'uris' => [2 => '/a', 3 => '/b'],
                            'templates' => [2 => 20, 3 => 30],
                        ],
                    ];
                }
                return null;
            }
        });
        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity, $id = null)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function first()
                    {
                        return new class($this->captured) {
                            private $captured;
                            public $site_pages = [];
                            public function __construct($captured)
                            {
                                $this->captured = $captured;
                            }
                            public function save()
                            {
                                $this->captured->saved++;
                            }
                        };
                    }
                };
            }
        });

        $tab = new Pages_tab();
        $tab->delete([2]);
        $this->assertSame(1, $captured->saved);
    }

    public function testDeleteWithUnknownIdsLeavesSitePagesUnchanged(): void
    {
        $captured = (object) ['saved' => 0, 'savedPages' => null];
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'uris' => [2 => '/a', 3 => '/b'],
                            'templates' => [2 => 20, 3 => 30],
                        ],
                    ];
                }
                return null;
            }
        });
        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity, $id = null)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function first()
                    {
                        return new class($this->captured) {
                            private $captured;
                            public $site_pages = [];
                            public function __construct($captured)
                            {
                                $this->captured = $captured;
                            }
                            public function save()
                            {
                                $this->captured->saved++;
                                $this->captured->savedPages = $this->site_pages;
                            }
                        };
                    }
                };
            }
        });

        $tab = new Pages_tab();
        $tab->delete([999]);

        $this->assertSame(1, $captured->saved);
        $this->assertSame('/a', $captured->savedPages[1]['uris'][2]);
        $this->assertSame(30, $captured->savedPages[1]['templates'][3]);
    }

    public function testRenderTableCellAndTableColumnConfig(): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [6 => '/docs/page']]];
                }
                return null;
            }
        });
        ee()->setMock('functions', new class {
            public function fetch_site_index($a = 0, $b = 0)
            {
                return 'https://example.com/';
            }
        });

        $tab = new Pages_tab();
        $this->assertStringContainsString('<a href="https:/example.com/docs/page"', $tab->renderTableCell('', '', (object) ['entry_id' => 6]));
        $this->assertSame('', $tab->renderTableCell('', '', (object) ['entry_id' => 99]));
        $this->assertSame(['encode' => false], $tab->getTableColumnConfig());
    }

    public function testCloneDataUsesDashSeparatorWhenConfigured(): void
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'word_separator') {
                    return 'dash';
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [11 => '/foo', 12 => '/copy-foo']]];
                }
                return null;
            }
        });

        $entry = new PagesTabChannelEntryStub(33);
        $tab = new Pages_tab();
        $values = $tab->cloneData($entry, ['pages_uri' => 'foo', 'pages_template_id' => 2]);
        $this->assertSame('copy-foo', $values['pages_uri']);
        $this->assertSame('copy-foo', $_POST['pages__pages_uri']);
    }
}
