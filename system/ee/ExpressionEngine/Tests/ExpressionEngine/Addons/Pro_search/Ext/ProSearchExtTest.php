<?php

require_once __DIR__ . '/../ProSearchTestBase.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/ext.pro_search.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchExtFixture extends Pro_search_ext
{
    public $mcpRequests = [];

    public function __construct($settings = [])
    {
        $this->package = 'pro_search';
        $this->settings = array_merge(['batch_size' => 2], $settings);
    }

    protected function mcp_url($path = null, $extra = null, $obj = false)
    {
        $this->mcpRequests[] = [$path, $extra, $obj];

        return 'cp://addons/settings/pro_search/' . $path;
    }
}

class ProSearchExtCollectionModelMock
{
    public $updates = [];
    private $collections = [];

    public function __construct($collections)
    {
        $this->collections = $collections;
    }

    public function get_all()
    {
        return $this->collections;
    }

    public function update($collectionId, $data)
    {
        $this->updates[$collectionId] = $data;

        return true;
    }
}

class ProSearchExtDbQueueMock extends eeDbArMock
{
    public $whereInCalls = [];
    private $resultSets = [];

    public function __construct($resultSets)
    {
        $this->resultSets = $resultSets;
    }

    public function select()
    {
        return $this;
    }

    public function from()
    {
        return $this;
    }

    public function where_in($key = null, $values = null, $escape = null)
    {
        $this->whereInCalls[] = ['key' => $key, 'values' => $values];

        return $this;
    }

    public function get()
    {
        $rows = array_shift($this->resultSets);
        if (! is_array($rows)) {
            $rows = [];
        }

        return new ProSearchDbResult($rows);
    }
}

class ProSearchExtTest extends ProSearchTestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $settings = new class {
            public $prefix = 'pro_search_';
            public function get($key) { return ''; }
            public function stop_words() { return []; }
            public function ignore_words() { return []; }
        };

        ee()->setMock('pro_search_settings', $settings);
    }

    public function testSettingsRedirectsToExtensionSettingsPage()
    {
        $functions = new class {
            public $redirectTo;
            public function redirect($url)
            {
                $this->redirectTo = $url;
            }
        };
        ee()->setMock('functions', $functions);

        $ext = new ProSearchExtFixture();
        $ext->settings();

        $this->assertSame('cp://addons/settings/pro_search/settings', $functions->redirectTo);
        $this->assertSame('settings', $ext->mcpRequests[0][0]);
    }

    public function testEntryHooksCallExpectedCollaborators()
    {
        $loader = new class extends eeSingletonLoadMock {
            public $libraryCalls = [];
            public function library($name = '')
            {
                $this->libraryCalls[] = $name;

                return null;
            }
        };
        ee()->setMock('load', $loader);

        $indexModel = new class {
            public $deleteCalls = [];
            public function delete($id, $key)
            {
                $this->deleteCalls[] = [$id, $key];

                return true;
            }
        };
        ee()->setMock('pro_search_index_model', $indexModel);

        $index = new class {
            public $buildCalls = [];
            public function build_by_entry($entryId)
            {
                $this->buildCalls[] = $entryId;

                return true;
            }
        };
        ee()->setMock('pro_search_index', $index);

        $entry = (object) ['entry_id' => 42];

        $ext = new ProSearchExtFixture();
        $ext->after_channel_entry_save($entry, []);
        $ext->after_channel_entry_insert($entry, []);
        $ext->after_channel_entry_update($entry, []);
        $ext->after_channel_entry_delete($entry, []);

        $this->assertCount(3, $loader->libraryCalls);
        $this->assertCount(4, $indexModel->deleteCalls);
        $this->assertCount(3, $index->buildCalls);
        $this->assertSame([42, 'entry_id'], $indexModel->deleteCalls[0]);
        $this->assertSame(42, $index->buildCalls[0]);
    }

    public function testChannelEntriesQueryResultBailsOutWhenNotProSearchRequest()
    {
        $tmpl = new FakeTemplate();
        $tmpl->setMap(['pro_search' => 'no']);
        ee()->setMock('TMPL', $tmpl);
        ee()->setMock('extensions', (object) ['last_call' => false]);

        $ext = new ProSearchExtFixture();
        $query = [['entry_id' => 1, 'title' => 'alpha']];

        $result = $ext->channel_entries_query_result((object) [], $query);

        $this->assertSame($query, $result);
    }

    public function testChannelEntriesQueryResultAddsSearchContextToRows()
    {
        $tmpl = new FakeTemplate();
        $tmpl->setMap(['pro_search' => 'yes']);
        ee()->setMock('TMPL', $tmpl);

        $params = new class {
            public function get_vars($prefix)
            {
                return [$prefix . 'keywords' => 'kittens'];
            }
        };
        ee()->setMock('pro_search_params', $params);

        $filters = new class {
            public $receivedRows = [];
            public function entry_ids()
            {
                return [10, 20];
            }
            public function results($rows)
            {
                $this->receivedRows = $rows;

                return $rows;
            }
        };
        ee()->setMock('pro_search_filters', $filters);

        $shortcutModel = new class {
            public function get_template_attrs()
            {
                return ['collection_name'];
            }
        };
        ee()->setMock('pro_search_shortcut_model', $shortcutModel);

        $session = new eeSingletonSessionMock();
        ee()->setMock('session', $session);
        $session->set_cache('pro_search', 'shortcut', ['collection_name' => 'Docs']);
        ee()->setMock('extensions', (object) ['last_call' => [['entry_id' => 2, 'title' => 'fresh']]]);

        $ext = new ProSearchExtFixture();
        $result = $ext->channel_entries_query_result((object) [], [['entry_id' => 1, 'title' => 'stale']]);

        $this->assertCount(1, $result);
        $this->assertSame('10|20', $result[0]['pro_search_entry_ids']);
        $this->assertSame('kittens', $result[0]['pro_search_keywords']);
        $this->assertSame('Docs', $result[0]['pro_search_collection_name']);
        $this->assertSame(2, $result[0]['entry_id']);
        $this->assertSame($result, $filters->receivedRows);
    }

    public function testAfterChannelFieldDeleteUpdatesOnlyImpactedCollections()
    {
        $collections = [
            1 => ['excerpt' => 10, 'settings' => [10 => 'remove-me', 11 => 'keep-me']],
            2 => ['excerpt' => 9, 'settings' => [11 => 'keep-only']],
            3 => ['excerpt' => 0, 'settings' => [10 => 'remove-me-too']],
        ];

        $collectionModel = new ProSearchExtCollectionModelMock($collections);
        ee()->setMock('pro_search_collection_model', $collectionModel);
        ee()->setMock('localize', (object) ['now' => 1700000000]);

        $ext = new ProSearchExtFixture();
        $ext->after_channel_field_delete((object) ['field_id' => 10], []);

        $this->assertArrayHasKey(1, $collectionModel->updates);
        $this->assertArrayHasKey(3, $collectionModel->updates);
        $this->assertArrayNotHasKey(2, $collectionModel->updates);
        $this->assertSame(0, $collectionModel->updates[1]['excerpt']);
        $this->assertSame(1700000000, $collectionModel->updates[1]['edit_date']);

        $settingsOne = json_decode($collectionModel->updates[1]['settings'], true);
        $settingsThree = json_decode($collectionModel->updates[3]['settings'], true);

        $this->assertArrayNotHasKey(10, $settingsOne);
        $this->assertArrayNotHasKey(10, $settingsThree);
    }

    public function testCategoryHooksAndCategoryBatchingPaths()
    {
        $db = new ProSearchExtDbQueueMock([
            [['entry_id' => 4], ['entry_id' => 4], ['entry_id' => 9]],
            [],
            [['entry_id' => 1], ['entry_id' => 2], ['entry_id' => 3]],
        ]);
        ee()->setMock('db', $db);

        $loader = new class extends eeSingletonLoadMock {
            public $libraryCalls = [];
            public function library($name = '')
            {
                $this->libraryCalls[] = $name;

                return null;
            }
        };
        ee()->setMock('load', $loader);

        $index = new class {
            public $buildCalls = [];
            public function build_by_entry($entryIds)
            {
                $this->buildCalls[] = $entryIds;

                return true;
            }
        };
        ee()->setMock('pro_search_index', $index);

        $ext = new ProSearchExtFixture(['batch_size' => 2]);

        $ext->after_category_save((object) ['cat_id' => 8], []);
        $ext->after_category_delete((object) ['cat_id' => 9], []);

        $private = new ReflectionMethod('Pro_search_ext', '_update_index_by_category');
        if (PHP_VERSION_ID < 80100) {
            $private->setAccessible(true);
        }

        $this->assertSame([], $private->invoke($ext, []));
        $private->invoke($ext, [7]);

        $this->assertCount(1, $index->buildCalls);
        $this->assertSame([4, 9], array_values($index->buildCalls[0]));
        $this->assertSame([8], $db->whereInCalls[0]['values']);
        $this->assertSame([9], $db->whereInCalls[1]['values']);
        $this->assertSame([7], $db->whereInCalls[2]['values']);
    }
}
