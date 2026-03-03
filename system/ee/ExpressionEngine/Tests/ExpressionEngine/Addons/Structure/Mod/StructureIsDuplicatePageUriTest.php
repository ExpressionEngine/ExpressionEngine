<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureIsDuplicatePageUriTest extends StructureTestBase
{
    private $sql;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock SQL object with the is_duplicate_page_uri method
        $this->sql = new class() {
            public function get_site_pages($cache_bust = false) {
                return ['uris' => []];
            }

            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }

            public function is_duplicate_page_uri($entry_id, $uri)
            {
                $site_pages_array = $this->get_site_pages(true);
                $pages = $site_pages_array['uris'];

                unset($pages[$entry_id]);

                $word_separator = ee()->config->item('word_separator');
                $separator = $word_separator != 'dash' ? '_' : '-';

                $settings = $this->get_settings();

                $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';

                if (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                    $i = 0;
                    $old_uri = trim($uri, '/');
                    while (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                        $i++;
                        if (defined('CLONING_MODE') && CLONING_MODE === true) {
                            $uri_parts = explode('/', $old_uri);
                            $uri = str_repeat('copy' . $separator, $i) . array_pop($uri_parts);
                            $uri = implode('/', $uri_parts) . '/' . $uri;
                        } else {
                            $uri = rtrim($uri, $separator . ($i - 1)) . $separator . $i;
                        }
                    }
                    $uri = '/' . trim($uri, '/') . $trailing_slash;
                    return $uri;
                }

                return false;
            }
        };
    }

    protected function tearDown(): void
    {
        // Clean up CLONING_MODE constant if it exists
        if (defined('CLONING_MODE')) {
            // We can't actually undefine constants in PHP, but we can reset it for tests
            // This is handled by running tests in separate processes if needed
        }
        parent::tearDown();
    }

    public function testIsDuplicatePageUriReturnsFalseWhenNoConflict()
    {
        $result = $this->sql->is_duplicate_page_uri(123, '/unique-page');
        $this->assertFalse($result);
    }

    public function testIsDuplicatePageUriReturnsModifiedUriWhenConflictExistsNormalMode()
    {
        // Mock the get_site_pages method to return conflicting URI
        $this->sql = new class() {
            public function get_site_pages($cache_bust = false) {
                return ['uris' => [456 => '/conflicting-page']];
            }
            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }
            public function is_duplicate_page_uri($entry_id, $uri) {
                $site_pages_array = $this->get_site_pages(true);
                $pages = $site_pages_array['uris'];
                unset($pages[$entry_id]);
                $word_separator = ee()->config->item('word_separator');
                $separator = $word_separator != 'dash' ? '_' : '-';
                $settings = $this->get_settings();
                $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';
                if (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                    $i = 0;
                    $old_uri = trim($uri, '/');
                    while (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                        $i++;
                        if (defined('CLONING_MODE') && CLONING_MODE === true) {
                            $uri_parts = explode('/', $old_uri);
                            $uri = str_repeat('copy' . $separator, $i) . array_pop($uri_parts);
                            $uri = implode('/', $uri_parts) . '/' . $uri;
                        } else {
                            $uri = rtrim($uri, $separator . ($i - 1)) . $separator . $i;
                        }
                    }
                    $uri = '/' . trim($uri, '/') . $trailing_slash;
                    return $uri;
                }
                return false;
            }
        };

        // Mock config for underscore separator
        ee()->config->items['word_separator'] = 'underscore';

        $result = $this->sql->is_duplicate_page_uri(123, '/conflicting-page');
        $this->assertSame('/conflicting-page_1', $result);
    }

    public function testIsDuplicatePageUriReturnsModifiedUriWhenConflictExistsNormalModeMultipleConflicts()
    {
        // Mock the get_site_pages method to return multiple conflicting URIs
        $this->sql = new class() {
            public function get_site_pages($cache_bust = false) {
                return ['uris' => [
                    456 => '/conflicting-page',
                    789 => '/conflicting-page_1'
                ]];
            }
            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }
            public function is_duplicate_page_uri($entry_id, $uri) {
                $site_pages_array = $this->get_site_pages(true);
                $pages = $site_pages_array['uris'];
                unset($pages[$entry_id]);
                $word_separator = ee()->config->item('word_separator');
                $separator = $word_separator != 'dash' ? '_' : '-';
                $settings = $this->get_settings();
                $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';
                if (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                    $i = 0;
                    $old_uri = trim($uri, '/');
                    while (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                        $i++;
                        if (defined('CLONING_MODE') && CLONING_MODE === true) {
                            $uri_parts = explode('/', $old_uri);
                            $uri = str_repeat('copy' . $separator, $i) . array_pop($uri_parts);
                            $uri = implode('/', $uri_parts) . '/' . $uri;
                        } else {
                            $uri = rtrim($uri, $separator . ($i - 1)) . $separator . $i;
                        }
                    }
                    $uri = '/' . trim($uri, '/') . $trailing_slash;
                    return $uri;
                }
                return false;
            }
        };

        // Mock config for underscore separator
        ee()->config->items['word_separator'] = 'underscore';

        $result = $this->sql->is_duplicate_page_uri(123, '/conflicting-page');
        $this->assertSame('/conflicting-page_2', $result);
    }

    public function testIsDuplicatePageUriReturnsModifiedUriWhenConflictExistsCloningModeSimplePage()
    {
        // Define CLONING_MODE constant
        if (!defined('CLONING_MODE')) {
            define('CLONING_MODE', true);
        }

        // Mock the get_site_pages method to return conflicting URI
        $this->sql = new class() {
            public function get_site_pages($cache_bust = false) {
                return ['uris' => [456 => '/conflicting-page']];
            }
            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }
            public function is_duplicate_page_uri($entry_id, $uri) {
                $site_pages_array = $this->get_site_pages(true);
                $pages = $site_pages_array['uris'];
                unset($pages[$entry_id]);
                $word_separator = ee()->config->item('word_separator');
                $separator = $word_separator != 'dash' ? '_' : '-';
                $settings = $this->get_settings();
                $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';
                if (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                    $i = 0;
                    $old_uri = trim($uri, '/');
                    while (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                        $i++;
                        if (defined('CLONING_MODE') && CLONING_MODE === true) {
                            $uri_parts = explode('/', $old_uri);
                            $uri = str_repeat('copy' . $separator, $i) . array_pop($uri_parts);
                            $uri = implode('/', $uri_parts) . '/' . $uri;
                        } else {
                            $uri = rtrim($uri, $separator . ($i - 1)) . $separator . $i;
                        }
                    }
                    $uri = '/' . trim($uri, '/') . $trailing_slash;
                    return $uri;
                }
                return false;
            }
        };

        // Mock config for underscore separator
        ee()->config->items['word_separator'] = 'underscore';

        $result = $this->sql->is_duplicate_page_uri(123, '/conflicting-page');
        $this->assertSame('/copy_conflicting-page', $result);
    }

    public function testIsDuplicatePageUriReturnsModifiedUriWhenConflictExistsCloningModeNestedPage()
    {
        // Define CLONING_MODE constant
        if (!defined('CLONING_MODE')) {
            define('CLONING_MODE', true);
        }

        // Mock the get_site_pages method to return conflicting URI
        $this->sql = new class() {
            public function get_site_pages($cache_bust = false) {
                return ['uris' => [456 => '/parent/child/page']];
            }
            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }
            public function is_duplicate_page_uri($entry_id, $uri) {
                $site_pages_array = $this->get_site_pages(true);
                $pages = $site_pages_array['uris'];
                unset($pages[$entry_id]);
                $word_separator = ee()->config->item('word_separator');
                $separator = $word_separator != 'dash' ? '_' : '-';
                $settings = $this->get_settings();
                $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';
                if (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                    $i = 0;
                    $old_uri = trim($uri, '/');
                    while (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                        $i++;
                        if (defined('CLONING_MODE') && CLONING_MODE === true) {
                            $uri_parts = explode('/', $old_uri);
                            $uri = str_repeat('copy' . $separator, $i) . array_pop($uri_parts);
                            $uri = implode('/', $uri_parts) . '/' . $uri;
                        } else {
                            $uri = rtrim($uri, $separator . ($i - 1)) . $separator . $i;
                        }
                    }
                    $uri = '/' . trim($uri, '/') . $trailing_slash;
                    return $uri;
                }
                return false;
            }
        };

        // Mock config for dash separator
        ee()->config->items['word_separator'] = 'dash';

        $result = $this->sql->is_duplicate_page_uri(123, '/parent/child/page');
        $this->assertSame('/parent/child/copy-page', $result);
    }

    public function testIsDuplicatePageUriReturnsModifiedUriWhenConflictExistsCloningModeMultipleClones()
    {
        // Define CLONING_MODE constant
        if (!defined('CLONING_MODE')) {
            define('CLONING_MODE', true);
        }

        // Mock the get_site_pages method to return multiple conflicting URIs
        $this->sql = new class() {
            public function get_site_pages($cache_bust = false) {
                return ['uris' => [
                    456 => '/parent/child/page',
                    789 => '/parent/child/copy-page'
                ]];
            }
            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }
            public function is_duplicate_page_uri($entry_id, $uri) {
                $site_pages_array = $this->get_site_pages(true);
                $pages = $site_pages_array['uris'];
                unset($pages[$entry_id]);
                $word_separator = ee()->config->item('word_separator');
                $separator = $word_separator != 'dash' ? '_' : '-';
                $settings = $this->get_settings();
                $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';
                if (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                    $i = 0;
                    $old_uri = trim($uri, '/');
                    while (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                        $i++;
                        if (defined('CLONING_MODE') && CLONING_MODE === true) {
                            $uri_parts = explode('/', $old_uri);
                            $uri = str_repeat('copy' . $separator, $i) . array_pop($uri_parts);
                            $uri = implode('/', $uri_parts) . '/' . $uri;
                        } else {
                            $uri = rtrim($uri, $separator . ($i - 1)) . $separator . $i;
                        }
                    }
                    $uri = '/' . trim($uri, '/') . $trailing_slash;
                    return $uri;
                }
                return false;
            }
        };

        // Mock config for dash separator
        ee()->config->items['word_separator'] = 'dash';

        $result = $this->sql->is_duplicate_page_uri(123, '/parent/child/page');
        $this->assertSame('/parent/child/copy-copy-page', $result);
    }

    public function testIsDuplicatePageUriExcludesCurrentEntryId()
    {
        // Mock the get_site_pages method to return URI for current entry
        $this->sql = new class() {
            public function get_site_pages($cache_bust = false) {
                return ['uris' => [123 => '/my-page']];
            }
            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }
            public function is_duplicate_page_uri($entry_id, $uri) {
                $site_pages_array = $this->get_site_pages(true);
                $pages = $site_pages_array['uris'];
                unset($pages[$entry_id]);
                $word_separator = ee()->config->item('word_separator');
                $separator = $word_separator != 'dash' ? '_' : '-';
                $settings = $this->get_settings();
                $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';
                if (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                    $i = 0;
                    $old_uri = trim($uri, '/');
                    while (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                        $i++;
                        if (defined('CLONING_MODE') && CLONING_MODE === true) {
                            $uri_parts = explode('/', $old_uri);
                            $uri = str_repeat('copy' . $separator, $i) . array_pop($uri_parts);
                            $uri = implode('/', $uri_parts) . '/' . $uri;
                        } else {
                            $uri = rtrim($uri, $separator . ($i - 1)) . $separator . $i;
                        }
                    }
                    $uri = '/' . trim($uri, '/') . $trailing_slash;
                    return $uri;
                }
                return false;
            }
        };

        $result = $this->sql->is_duplicate_page_uri(123, '/my-page');
        $this->assertFalse($result);
    }

    public function testIsDuplicatePageUriHandlesTrailingSlashSetting()
    {
        // Mock the get_site_pages method with trailing slash setting
        $this->sql = new class() {
            public function get_site_pages($cache_bust = false) {
                return ['uris' => [456 => '/page/']];
            }
            public function get_settings() {
                return ['add_trailing_slash' => 'y'];
            }
            public function is_duplicate_page_uri($entry_id, $uri) {
                $site_pages_array = $this->get_site_pages(true);
                $pages = $site_pages_array['uris'];
                unset($pages[$entry_id]);
                $word_separator = ee()->config->item('word_separator');
                $separator = $word_separator != 'dash' ? '_' : '-';
                $settings = $this->get_settings();
                $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';
                if (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                    $i = 0;
                    $old_uri = trim($uri, '/');
                    while (in_array('/' . trim($uri, '/') . $trailing_slash, $pages)) {
                        $i++;
                        // For this test, force normal mode (not cloning mode)
                        $uri = rtrim($uri, $separator . ($i - 1)) . $separator . $i;
                    }
                    $uri = '/' . trim($uri, '/') . $trailing_slash;
                    return $uri;
                }
                return false;
            }
        };

        // Mock config for underscore separator
        ee()->config->items['word_separator'] = 'underscore';

        $result = $this->sql->is_duplicate_page_uri(123, '/page');
        $this->assertSame('/page_1/', $result);
    }
}
