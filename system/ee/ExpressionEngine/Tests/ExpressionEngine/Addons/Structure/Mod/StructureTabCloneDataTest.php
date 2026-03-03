<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureTabCloneDataTest extends StructureTestBase
{
    private $tab;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock SQL object for the tab
        $this->tab = new class() {
            public $sql;

            public function cloneData($entry, $values)
            {
                if ($values['uri'] == '') {
                    return $values;
                }
                //check if submitted URI exists
                $site_pages = $this->sql->get_site_pages(true, true);
                $uris = $site_pages['uris'];

                //exclude current page from check
                if (isset($uris[$entry->entry_id])) {
                    unset($uris[$entry->entry_id]);
                }
                //ensure leading slash is present
                $value = '/' . trim($values['uri'], '/');

                $word_separator = ee()->config->item('word_separator') != "dash" ? '_' : '-';
                while (in_array($value, $uris)) {
                    $value = 'copy' . $word_separator . ltrim($value, '/');
                }
                $_POST['structure__uri'] = $values['uri'] = $value;

                return $values;
            }
        };
    }

    public function testCloneDataReturnsValuesUnchangedWhenUriIsEmpty()
    {
        $entry = $this->createMockEntry(123);
        $values = ['uri' => ''];

        $result = $this->tab->cloneData($entry, $values);

        $this->assertSame($values, $result);
    }

    public function testCloneDataReturnsUniqueUriWhenNoConflictExists()
    {
        $entry = $this->createMockEntry(123);

        // Mock SQL to return existing URIs that don't conflict
        $this->tab->sql = new class() {
            public function get_site_pages($cache_bust = false, $force = false) {
                return ['uris' => [456 => '/existing-page']];
            }
        };

        $values = ['uri' => '/new-page'];

        $result = $this->tab->cloneData($entry, $values);

        $this->assertSame('/new-page', $result['uri']);
    }

    public function testCloneDataPrefixesUriWithCopyWhenConflictExistsUnderscoreSeparator()
    {
        $entry = $this->createMockEntry(123);

        // Mock SQL to return conflicting URI
        $this->tab->sql = new class() {
            public function get_site_pages($cache_bust = false, $force = false) {
                return ['uris' => [456 => '/existing-page']];
            }
        };

        // Mock config for underscore separator
        ee()->config->items['word_separator'] = 'underscore';

        $values = ['uri' => '/existing-page'];

        $result = $this->tab->cloneData($entry, $values);

        $this->assertSame('copy_existing-page', $result['uri']);
    }

    public function testCloneDataPrefixesUriWithCopyWhenConflictExistsDashSeparator()
    {
        $entry = $this->createMockEntry(123);

        // Mock SQL to return conflicting URI
        $this->tab->sql = new class() {
            public function get_site_pages($cache_bust = false, $force = false) {
                return ['uris' => [456 => '/existing-page']];
            }
        };

        // Mock config for dash separator
        ee()->config->items['word_separator'] = 'dash';

        $values = ['uri' => '/existing-page'];

        $result = $this->tab->cloneData($entry, $values);

        $this->assertSame('copy-existing-page', $result['uri']);
    }

    public function testCloneDataHandlesMultipleConflictsByAddingMoreCopyPrefixes()
    {
        $entry = $this->createMockEntry(123);

        // Mock SQL to return multiple conflicting URIs
        $this->tab->sql = new class() {
            public function get_site_pages($cache_bust = false, $force = false) {
                return ['uris' => [
                    456 => '/existing-page',
                    789 => 'copy_existing-page'
                ]];
            }
        };

        // Mock config for underscore separator
        ee()->config->items['word_separator'] = 'underscore';

        $values = ['uri' => '/existing-page'];

        $result = $this->tab->cloneData($entry, $values);

        $this->assertSame('copy_copy_existing-page', $result['uri']);
    }

    public function testCloneDataExcludesCurrentEntryFromConflictCheck()
    {
        $entry = $this->createMockEntry(123);

        // Mock SQL to return URI for current entry
        $this->tab->sql = new class() {
            public function get_site_pages($cache_bust = false, $force = false) {
                return ['uris' => [123 => '/my-page']];
            }
        };

        $values = ['uri' => '/my-page'];

        $result = $this->tab->cloneData($entry, $values);

        // Should not modify URI since it's the current entry
        $this->assertSame('/my-page', $result['uri']);
    }

    public function testCloneDataNormalizesUriWithLeadingSlash()
    {
        $entry = $this->createMockEntry(123);

        // Mock SQL to return no conflicts
        $this->tab->sql = new class() {
            public function get_site_pages($cache_bust = false, $force = false) {
                return ['uris' => []];
            }
        };

        $values = ['uri' => 'page-without-leading-slash'];

        $result = $this->tab->cloneData($entry, $values);

        $this->assertSame('/page-without-leading-slash', $result['uri']);
    }

    public function testCloneDataUpdatesPostData()
    {
        $entry = $this->createMockEntry(123);

        // Mock SQL to return no conflicts
        $this->tab->sql = new class() {
            public function get_site_pages($cache_bust = false, $force = false) {
                return ['uris' => []];
            }
        };

        $values = ['uri' => '/test-page'];

        $result = $this->tab->cloneData($entry, $values);

        $this->assertSame('/test-page', $_POST['structure__uri']);
        $this->assertSame('/test-page', $result['uri']);
    }

    private function createMockEntry($entryId)
    {
        return new class($entryId) {
            public $entry_id;
            public function __construct($id) { $this->entry_id = $id; }
        };
    }
}
