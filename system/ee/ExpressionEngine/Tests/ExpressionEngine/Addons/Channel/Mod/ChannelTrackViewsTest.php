<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelTrackViewsTest extends ChannelTestBase
{
    public function testTrackViewsReturnsEarlyWhenTrackingDisabled()
    {
        // Set config to disable view tracking
        ee()->config->items['enable_entry_view_tracking'] = 'n';

        // Set up mocks
        $this->setTemplateParams(['track_views' => 'one']);
        $this->channel->hit_tracking_id = 123;

        $executed = false;
        $this->setMock('db', new class($executed) {
            private $executed;
            public function __construct(&$executed) { $this->executed = &$executed; }
            public function query($sql) { $this->executed = true; }
            public function escape_str($str) { return $str; }
        });

        $this->channel->track_views();

        // Verify no query was executed
        $this->assertFalse($executed);
    }

    public function testTrackViewsReturnsEarlyWhenTrackViewsParamMissing()
    {
        // Set config to enable view tracking
        ee()->config->items['enable_entry_view_tracking'] = 'y';

        // Don't set track_views parameter
        $this->channel->hit_tracking_id = 123;

        $executed = false;
        $this->setMock('db', new class($executed) {
            private $executed;
            public function __construct(&$executed) { $this->executed = &$executed; }
            public function query($sql) { $this->executed = true; }
            public function escape_str($str) { return $str; }
        });

        $this->channel->track_views();

        // Verify no query was executed
        $this->assertFalse($executed);
    }

    public function testTrackViewsReturnsEarlyWhenHitTrackingIdFalse()
    {
        // Set config to enable view tracking
        ee()->config->items['enable_entry_view_tracking'] = 'y';

        // Set track_views parameter but hit_tracking_id is false
        $this->setTemplateParams(['track_views' => 'one']);
        $this->channel->hit_tracking_id = false;

        $executed = false;
        $this->setMock('db', new class($executed) {
            private $executed;
            public function __construct(&$executed) { $this->executed = &$executed; }
            public function query($sql) { $this->executed = true; }
            public function escape_str($str) { return $str; }
        });

        $this->channel->track_views();

        // Verify no query was executed
        $this->assertFalse($executed);
    }

    public function testTrackViewsReturnsEarlyWhenPaginationOffset()
    {
        // Set config to enable view tracking
        ee()->config->items['enable_entry_view_tracking'] = 'y';

        // Set parameters
        $this->setTemplateParams(['track_views' => 'one']);
        $this->channel->hit_tracking_id = 123;

        // Mock pagination with offset
        $this->channel->pagination = new class {
            public $field_pagination = true;
            public $offset = 10;
        };

        $executed = false;
        $this->setMock('db', new class($executed) {
            private $executed;
            public function __construct(&$executed) { $this->executed = &$executed; }
            public function query($sql) { $this->executed = true; }
            public function escape_str($str) { return $str; }
        });

        $this->channel->track_views();

        // Verify no query was executed
        $this->assertFalse($executed);
    }

    public function testTrackViewsExecutesQueryForValidViewType()
    {
        // Set config to enable view tracking
        ee()->config->items['enable_entry_view_tracking'] = 'y';

        // Set parameters
        $this->setTemplateParams(['track_views' => 'one']);
        $this->channel->hit_tracking_id = 123;

        // Mock pagination without offset
        $this->channel->pagination = new class {
            public $field_pagination = false;
            public $offset = 0;
        };

        $executedQuery = null;
        $this->setMock('db', new class($executedQuery) {
            private $executedQuery;
            public function __construct(&$executedQuery) { $this->executedQuery = &$executedQuery; }
            public function query($sql) { $this->executedQuery = $sql; }
            public function escape_str($str) { return $str; }
        });

        $this->channel->track_views();

        // Verify query was executed with correct format
        $this->assertTrue(strpos($executedQuery, 'UPDATE exp_channel_titles SET view_count_one = (view_count_one + 1)') !== false);
        $this->assertTrue(strpos($executedQuery, 'entry_id = 123') !== false);
    }

    public function testTrackViewsHandlesUrlTitleInsteadOfEntryId()
    {
        // Set config to enable view tracking
        ee()->config->items['enable_entry_view_tracking'] = 'y';

        // Set parameters with string hit_tracking_id
        $this->setTemplateParams(['track_views' => 'two']);
        $this->channel->hit_tracking_id = 'test-url-title';

        // Mock pagination without offset
        $this->channel->pagination = new class {
            public $field_pagination = false;
            public $offset = 0;
        };

        $executedQuery = null;
        $this->setMock('db', new class($executedQuery) {
            private $executedQuery;
            public function __construct(&$executedQuery) { $this->executedQuery = &$executedQuery; }
            public function query($sql) { $this->executedQuery = $sql; }
            public function escape_str($str) { return $str; }
        });

        $this->channel->track_views();

        // Verify query was executed with url_title condition
        $this->assertTrue(strpos($executedQuery, 'UPDATE exp_channel_titles SET view_count_two = (view_count_two + 1)') !== false);
        $this->assertTrue(strpos($executedQuery, "url_title = 'test-url-title'") !== false);
    }

    public function testTrackViewsIgnoresInvalidViewTypes()
    {
        // Set config to enable view tracking
        ee()->config->items['enable_entry_view_tracking'] = 'y';

        // Set parameters with invalid view type
        $this->setTemplateParams(['track_views' => 'invalid|one']);
        $this->channel->hit_tracking_id = 123;

        // Mock pagination without offset
        $this->channel->pagination = new class {
            public $field_pagination = false;
            public $offset = 0;
        };

        $queryCount = 0;
        $this->setMock('db', new class($queryCount) {
            private $queryCount;
            public function __construct(&$queryCount) { $this->queryCount = &$queryCount; }
            public function query($sql) { $this->queryCount++; }
            public function escape_str($str) { return $str; }
        });

        $this->channel->track_views();

        // Verify only one query was executed (for 'one', ignoring 'invalid')
        $this->assertEquals(1, $queryCount);
    }

    public function testTrackViewsHandlesMultipleValidViewTypes()
    {
        // Set config to enable view tracking
        ee()->config->items['enable_entry_view_tracking'] = 'y';

        // Set parameters with multiple valid view types
        $this->setTemplateParams(['track_views' => 'one|two|three']);
        $this->channel->hit_tracking_id = 123;

        // Mock pagination without offset
        $this->channel->pagination = new class {
            public $field_pagination = false;
            public $offset = 0;
        };

        $queryCount = 0;
        $this->setMock('db', new class($queryCount) {
            private $queryCount;
            public function __construct(&$queryCount) { $this->queryCount = &$queryCount; }
            public function query($sql) { $this->queryCount++; }
            public function escape_str($str) { return $str; }
        });

        $this->channel->track_views();

        // Verify three queries were executed (one for each valid view type)
        $this->assertEquals(3, $queryCount);
    }
}
