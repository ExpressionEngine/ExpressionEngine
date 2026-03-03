<?php

require_once __DIR__ . '/../ProSearchTestBase.php';
require_once PATH_ADDONS . 'pro_search/libraries/Pro_search_settings.php';

class ProSearchSettingsTest extends ProSearchTestBase
{
    protected $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = new Pro_search_settings();
    }

    public function testGetDefaults()
    {
        // When no settings in DB, should return defaults
        ee()->db->setRows([]); // No rows
        
        $defaults = $this->settings->get();
        $this->assertEquals('y', $defaults['encode_query']);
        $this->assertEquals('4', $defaults['min_word_length']);
        
        $this->assertEquals('y', $this->settings->get('encode_query'));
    }

    public function testGetFromDb()
    {
        $dbSettings = ['encode_query' => 'n', 'custom_setting' => 'custom'];
        $serialized = serialize($dbSettings);
        
        ee()->db->setRows([
            ['class' => 'Pro_search_ext', 'settings' => $serialized]
        ]);

        // Should return merged settings
        $this->assertEquals('n', $this->settings->get('encode_query'));
        $this->assertEquals('custom', $this->settings->get('custom_setting'));
        // Defaults should still be present if not overridden
        $this->assertEquals('4', $this->settings->get('min_word_length'));
    }

    public function testSet()
    {
        $this->settings->set(['encode_query' => 'n']);
        $this->assertEquals('n', $this->settings->get('encode_query'));
    }

    public function testMagicGet()
    {
        $this->assertEquals('pro_search_', $this->settings->prefix);
        $this->assertContains('any', $this->settings->search_modes);
    }

    public function testStopWords()
    {
        $words = $this->settings->stop_words();
        $this->assertIsArray($words);
        $this->assertContains('about', $words);
        $this->assertNotContains("'", $words); // Should be cleaned
    }

    public function testIgnoreWords()
    {
        $words = $this->settings->ignore_words();
        $this->assertIsArray($words);
        $this->assertContains('the', $words);
    }

    public function testPermissions()
    {
        $perms = $this->settings->permissions();
        $this->assertContains('can_manage', $perms);
        $this->assertContains('can_manage_shortcuts', $perms);
        // Should not contain non-permission settings
        $this->assertNotContains('encode_query', $perms);
    }
}
