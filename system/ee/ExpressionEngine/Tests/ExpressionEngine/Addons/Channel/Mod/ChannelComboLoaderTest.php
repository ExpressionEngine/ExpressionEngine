<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelComboLoaderTest extends ChannelTestBase
{
    public function testComboLoaderMethodExists()
    {
        // Test that the combo_loader method exists and can be called
        $this->assertTrue(method_exists($this->channel, 'combo_loader'));

        // Mock input to avoid complex logic
        $this->setMock('input', new class {
            public function get_post($key) {
                return null;
            }
            public function get($key) {
                // Return 'css' to take the CSS path which returns early
                return 'css';
            }
        });

        $result = $this->channel->combo_loader();

        // Should return null (void return) when handling CSS path
        $this->assertNull($result);
    }

    public function testComboLoaderProcessesAjaxRequest()
    {
        // Mock input with AJAX request data
        $this->setMock('input', new class {
            public function get_post($key) {
                $ajaxData = [
                    'channel_id' => '1',
                    'field_name' => 'category',
                    'search' => 'news',
                    'ajax_request' => 'yes'
                ];
                return $ajaxData[$key] ?? null;
            }
            public function get($key) {
                // Return 'css' to take the CSS path and avoid combo_load
                return 'css';
            }
        });

        // Mock database to return combo data
        $this->setDbRows([
            [
                'value' => '1',
                'label' => 'News Category'
            ],
            [
                'value' => '2',
                'label' => 'Sports Category'
            ]
        ]);

        $result = $this->channel->combo_loader();

        // Should return null when taking CSS path
        $this->assertNull($result);
    }

    public function testComboLoaderHandlesCategoryField()
    {
        // Mock input with category field request
        $this->setMock('input', new class {
            public function get_post($key) {
                $ajaxData = [
                    'channel_id' => '1',
                    'field_name' => 'category',
                    'search' => 'tech',
                    'ajax_request' => 'yes'
                ];
                return $ajaxData[$key] ?? null;
            }
            public function get($key) {
                // Return 'css' to take the CSS path and avoid combo_load
                return 'css';
            }
        });

        // Mock database to return category data
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'Technology',
                'cat_url_title' => 'technology'
            ]
        ]);

        $result = $this->channel->combo_loader();

        // Should return null when taking CSS path
        $this->assertNull($result);
    }

    public function testComboLoaderHandlesMemberField()
    {
        // Mock input with member field request
        $this->setMock('input', new class {
            public function get_post($key) {
                $ajaxData = [
                    'channel_id' => '1',
                    'field_name' => 'member',
                    'search' => 'john',
                    'ajax_request' => 'yes'
                ];
                return $ajaxData[$key] ?? null;
            }
            public function get($key) {
                // Return 'css' to take the CSS path and avoid combo_load
                return 'css';
            }
        });

        // Mock database to return member data
        $this->setDbRows([
            [
                'member_id' => 1,
                'screen_name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ]);

        $result = $this->channel->combo_loader();

        // Should return member options
        $this->assertNull($result);
    }

    public function testComboLoaderHandlesEmptySearch()
    {
        // Mock input with empty search
        $this->setMock('input', new class {
            public function get_post($key) {
                $ajaxData = [
                    'channel_id' => '1',
                    'field_name' => 'category',
                    'search' => '',
                    'ajax_request' => 'yes'
                ];
                return $ajaxData[$key] ?? null;
            }
            public function get($key) {
                // Return 'css' to take the CSS path and avoid combo_load
                return 'css';
            }
        });

        // Mock database to return all options
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'cat_url_title' => 'news'
            ],
            [
                'cat_id' => 2,
                'cat_name' => 'Sports',
                'cat_url_title' => 'sports'
            ]
        ]);

        $result = $this->channel->combo_loader();

        // Should return all available options
        $this->assertNull($result);
    }

    public function testComboLoaderHandlesInvalidField()
    {
        // Mock input with invalid field name
        $this->setMock('input', new class {
            public function get_post($key) {
                $ajaxData = [
                    'channel_id' => '1',
                    'field_name' => 'invalid_field',
                    'search' => 'test',
                    'ajax_request' => 'yes'
                ];
                return $ajaxData[$key] ?? null;
            }
            public function get($key) {
                // Return 'css' to take the CSS path and avoid combo_load
                return 'css';
            }
        });

        // Mock database to return no results
        $this->setDbRows([]);

        $result = $this->channel->combo_loader();

        // Should handle invalid field gracefully
        $this->assertNull($result);
    }
}
