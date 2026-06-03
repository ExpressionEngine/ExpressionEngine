<?php

require_once __DIR__ . '/../ProSearchTestBase.php';
require_once PATH_ADDONS . 'pro_search/libraries/Pro_search_words.php';

class ProSearchWordsTest extends ProSearchTestBase
{
    protected $words;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Config for inflection rules check
        ee()->config->setItem('pro_search_inflection_rules', []);
        
        // Mock word model
        $model = $this->getMockBuilder('stdClass')
            ->addMethods(['get_dirty', 'get_sounds'])
            ->getMock();
        $model->method('get_dirty')->willReturn([]);
        $model->method('get_sounds')->willReturn([]);
        ee()->setMock('pro_search_word_model', $model);

        $this->words = new Pro_search_words();
    }

    public function testSetLanguage()
    {
        $this->words->set_language('en');
        // Check internal state? We can test functionality that depends on language.
        $this->assertEquals('cats', $this->words->plural('cat'));
    }

    public function testStem()
    {
        // Requires stemmer file to exist in i18n/en/stemmer.php
        // ProSearchTestBase defines PATH_ADDONS to point to actual addons
        // So it should load the real stemmer if it exists.
        
        $this->words->set_language('en');
        $stem = $this->words->stem('running');
        // Porter stemmer usually stems 'running' to 'run'
        $this->assertEquals('run', $stem);
    }

    public function testInflect()
    {
        $this->words->set_language('en');
        $this->assertEquals('dogs', $this->words->plural('dog'));
        $this->assertEquals('cat', $this->words->singular('cats'));
        // Uncountable
        $this->assertEquals('equipment', $this->words->plural('equipment'));
    }

    public function testIsCountable()
    {
        $this->words->set_language('en');
        $this->assertTrue($this->words->is_countable('cat'));
        $this->assertFalse($this->words->is_countable('equipment'));
    }

    public function testIsValid()
    {
        $this->assertTrue($this->words->is_valid('valid'));
        $this->assertFalse($this->words->is_valid('no')); // Too short (< 3)
        $this->assertFalse($this->words->is_valid('digit1')); // Has digit
    }

    public function testClean()
    {
        // clean() uses ee()->pro_search_settings->ignore_words()
        $settings = $this->getMockBuilder('stdClass')
            ->addMethods(['ignore_words'])
            ->getMock();
        $settings->method('ignore_words')->willReturn(['the', 'and']);
        ee()->setMock('pro_search_settings', $settings);

        $str = "<p>The &quot;quick&quot; brown fox</p>";
        $clean = $this->words->clean($str, true); // true = remove ignore words
        // "the" is removed. "quick" quotes removed. tags removed.
        // clean() lowercases.
        $this->assertEquals('quick brown fox', $clean);
    }

    public function testCleanWithNullInput()
    {
        $settings = $this->getMockBuilder('stdClass')
            ->addMethods(['ignore_words'])
            ->getMock();
        $settings->method('ignore_words')->willReturn([]);
        ee()->setMock('pro_search_settings', $settings);

        $clean = $this->words->clean(null, true);
        $this->assertSame('', $clean);
    }

    public function testRemoveDiacritics()
    {
        // remove_diacritics uses ee()->config->loadFile('foreign_chars')
        // We need to mock config->loadFile to return array
        $config = ee()->config;
        
        // eeSingletonConfigMock doesn't have loadFile.
        // We need to check if we can add it or if we should mock ee()->config entirely with something else.
        // ee()->config is set in ProSearchTestBase as FakeConfig.
        // FakeConfig doesn't have loadFile.
        
        // I can overwrite ee()->config with a Mock object that supports loadFile.
        $configMock = $this->getMockBuilder('FakeConfig')
            ->addMethods(['loadFile'])
            ->getMock();
        
        $configMock->method('loadFile')->with('foreign_chars')->willReturn([
            228 => 'ae', // ä
        ]);
        
        ee()->setMock('config', $configMock);
        
        // We also need pro_chr helper which relies on html_entity_decode
        // remove_diacritics caches chars in static var, so we need to be careful if tested multiple times?
        // PHPUnit isolates tests? No, processIsolation=false in xml.
        // But static variable in method `remove_diacritics` will persist.
        // So if this test runs after another test that initialized it, we might have issues.
        // But this is the first test using it.
        
        $str = $this->words->remove_diacritics('bär');
        $this->assertEquals('baer', $str);
    }
}
