<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibFetchEntryTest extends ChannelFormLibTestBase
{
    public function testFetchEntryWithEntryId()
    {
        $mockChannel = $this->createMockChannel();
        $mockEntry = $this->createMockEntry(['entry_id' => 10, 'title' => 'Test Entry']);
        $mockEntry->Channel = $mockChannel;

        // Mock the Model query
        $mockQuery = new class($mockEntry) {
            private $entry;
            public function __construct($entry) { $this->entry = $entry; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function first() { return $this->entry; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->channel = $mockChannel; // Set up channel first
        $this->channelFormLib->site_id = 1; // Set up site_id
        $this->channelFormLib->fetch_entry(10);

        $this->assertEquals(10, $this->channelFormLib->entry('entry_id'));
        $this->assertEquals('Test Entry', $this->channelFormLib->entry('title'));
    }

    public function testFetchEntryWithUrlTitle()
    {
        $mockEntry = $this->createMockEntry(['entry_id' => 15, 'url_title' => 'test-url-title']);

        // Mock the Model query
        $mockQuery = new class($mockEntry) {
            private $entry;
            public function __construct($entry) { $this->entry = $entry; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function first() { return $this->entry; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->channel = $this->createMockChannel(); // Set up channel first
        $this->channelFormLib->site_id = 1; // Set up site_id
        $this->channelFormLib->fetch_entry(null, 'test-url-title');

        $this->assertEquals(15, $this->channelFormLib->entry('entry_id'));
        $this->assertEquals('test-url-title', $this->channelFormLib->entry('url_title'));
    }

    public function testFetchEntryCreatesNewEntryWhenNoParameters()
    {
        // Mock ee('Model') to return a new entry
        $this->setMock('Model', new class {
            public function make($model) {
                return new class {
                    public $entry_id = 0;
                    public $Channel = null;
                    public $ip_address = '127.0.0.1';
                    public $title = 'Default Title';
                    public $versioning_enabled = 'n';
                    public $status = 'open';
                    public $author_id = 1;
                    public function getProperty($key) { return $this->$key ?? null; }
                };
            }
        });

        $mockChannel = $this->createMockChannel(['default_entry_title' => 'Default Title']);
        $this->channelFormLib->channel = $mockChannel; // Set up channel for new entry
        $this->channelFormLib->fetch_entry(null, null); // Pass null parameters to create new entry

        $this->assertEquals(0, $this->channelFormLib->entry('entry_id'));
        $this->assertEquals('Default Title', $this->channelFormLib->entry('title'));
        $this->assertEquals('open', $this->channelFormLib->entry('status'));
    }

    public function testFetchEntryWithChannelDefaults()
    {
        $mockChannel = $this->createMockChannel([
            'default_entry_title' => 'Channel Default Title',
            'deft_status' => 'draft',
            'enable_versioning' => 'y'
        ]);

        $this->channelFormLib->channel = $mockChannel;

        // Mock ee('Model') to return a new entry
        $this->setMock('Model', new class {
            public function make($model) {
                return new class {
                    public $entry_id = 0;
                    public $Channel = null;
                    public $ip_address = '127.0.0.1';
                    public $title = '';
                    public $versioning_enabled = '';
                    public $status = '';
                    public $author_id = 1;
                    public function getProperty($key) { return $this->$key ?? null; }
                    public function setProperty($key, $value) { $this->$key = $value; }
                };
            }
        });

        $this->channelFormLib->fetch_entry(null, null); // Pass null parameters to create new entry

        $this->assertEquals('Channel Default Title', $this->channelFormLib->entry('title'));
        $this->assertEquals('draft', $this->channelFormLib->entry('status'));
    }

    public function testFetchEntryWithChannelFormSettings()
    {
        $mockChannel = $this->createMockChannel();
        $mockChannel->ChannelFormSettings = new class {
            public $default_status = 'pending';
            public $allow_guest_posts = 'y';
            public $default_author = 5;
        };

        $this->channelFormLib->channel = $mockChannel;

        // Mock session to return no member_id (guest)
        ee()->session->userdata['member_id'] = 0;

        // Mock ee('Model') to return a new entry
        $this->setMock('Model', new class {
            public function make($model) {
                return new class {
                    public $entry_id = 0;
                    public $Channel = null;
                    public $ip_address = '127.0.0.1';
                    public $title = 'Default Title';
                    public $versioning_enabled = 'n';
                    public $status = '';
                    public $author_id = 0;
                    public function getProperty($key) { return $this->$key ?? null; }
                    public function setProperty($key, $value) { $this->$key = $value; }
                };
            }
        });

        $this->channelFormLib->fetch_entry(null, null); // Pass null parameters to create new entry

        $this->assertEquals('pending', $this->channelFormLib->entry('status'));
        $this->assertEquals(5, $this->channelFormLib->entry('author_id'));
    }

    public function testFetchEntryWithDefaultCategory()
    {
        $mockChannel = $this->createMockChannel(['deft_category' => 3]);

        // Mock ee('Model') for both entry and category
        $this->setMock('Model', new class {
            public function make($model) {
                if ($model === 'ChannelEntry') {
                    return new class {
                        public $entry_id = 0;
                        public $Channel = null;
                        public $Categories = null;
                        public $ip_address = '127.0.0.1';
                        public $title = 'Default Title';
                        public $versioning_enabled = 'n';
                        public $status = 'open';
                        public $author_id = 1;
                        public function getProperty($key) { return $this->$key ?? null; }
                        public function setProperty($key, $value) { $this->$key = $value; }
                    };
                }
            }
            public function get($model, $id = null) {
                if ($model === 'Category' && $id === 3) {
                    return new class {
                        public $cat_id = 3;
                        public function first() { return $this; }
                    };
                }
            }
        });

        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->fetch_entry(null, null); // Pass null parameters to create new entry

        $this->assertNotNull($this->channelFormLib->entry->Categories);
    }
}
