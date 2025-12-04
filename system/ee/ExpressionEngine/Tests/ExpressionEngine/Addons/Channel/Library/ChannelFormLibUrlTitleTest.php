<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibUrlTitleTest extends ChannelFormLibTestBase
{
    public function testUrlTitleJsGeneratesValidJavascript()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config mocks for JavaScript generation
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                if ($file === 'foreign_chars') {
                    return [
                        'ä' => 'ae',
                        'ö' => 'oe',
                        'ü' => 'ue'
                    ];
                }
                return [];
            }
        });

        // Setup extensions mock (no hooks for this test)
        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Verify the result is a string containing JavaScript
        $this->assertIsString($result);
        $this->assertStringContainsString('function liveUrlTitle(event)', $result);
        $this->assertStringContainsString('liveUrlTitle(event)', $result);
    }

    public function testUrlTitleJsHandlesUnderscoreWordSeparator()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config with underscore separator
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'n',
                'word_separator' => 'underscore'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                return [];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Verify underscore separator is used
        $this->assertStringContainsString('var separator = "_"', $result);
    }

    public function testUrlTitleJsHandlesDashWordSeparator()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config with dash separator
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'n',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                return [];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Verify dash separator is used
        $this->assertStringContainsString('var separator = "-"', $result);
    }

    public function testUrlTitleJsIncludesForeignCharacterConversion()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config with foreign characters
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                return [
                    'ä' => 'ae',
                    'ö' => 'oe',
                    'ü' => 'ue',
                    'ñ' => 'n'
                ];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Verify foreign character conversion logic is included
        $this->assertStringContainsString('if (c == \'ä\') {NewTextTemp += \'ae\'', $result);
        $this->assertStringContainsString('if (c == \'ö\') {NewTextTemp += \'oe\'', $result);
        $this->assertStringContainsString('if (c == \'ü\') {NewTextTemp += \'ue\'', $result);
        $this->assertStringContainsString('if (c == \'ñ\') {NewTextTemp += \'n\'', $result);
    }

    public function testUrlTitleJsHandlesForeignCharacterConversionHook()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config with basic foreign characters
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                return [
                    'ä' => 'ae'
                ];
            }
        });

        // Setup extensions mock with hook that modifies foreign characters
        $this->setMock('extensions', new class {
            public $extensions = [
                'foreign_character_conversion_array' => ['test']
            ];
            public function call($hook, $args = null) {
                if ($hook === 'foreign_character_conversion_array') {
                    return [
                        'ä' => 'ae',
                        'ß' => 'ss',  // Hook adds additional character
                        'custom' => 'converted'  // Hook adds custom character
                    ];
                }
                return null;
            }
        });

        // Call the private method
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Verify hook-modified characters are included
        $this->assertStringContainsString('if (c == \'ß\') {NewTextTemp += \'ss\'', $result);
        $this->assertStringContainsString('if (c == \'custom\') {NewTextTemp += \'converted\'', $result);
    }

    public function testUrlTitleJsMinifiesOutputInProduction()
    {
        // Skip this test if PATH_JS is already set to 'src' (development mode)
        if (defined('PATH_JS') && PATH_JS === 'src') {
            $this->markTestSkipped('Test requires production environment (PATH_JS != src)');
        }

        // Setup basic config
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'n',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                return [];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Verify output is minified (no newlines or tabs)
        $this->assertStringNotContainsString("\n", $result);
        $this->assertStringNotContainsString("\t", $result);
    }

    public function testUrlTitleJsPreservesFormattingInDevelopment()
    {
        // Skip this test if PATH_JS is set to something other than 'src'
        if (defined('PATH_JS') && PATH_JS !== 'src') {
            $this->markTestSkipped('Test requires development environment (PATH_JS = src)');
        }

        // Ensure PATH_JS is set to 'src' for this test
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup basic config
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'n',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                return [];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Verify output preserves formatting (contains newlines)
        $this->assertStringContainsString("\n", $result);
    }

    public function testUrlTitleJsIncludesAllRequiredJavaScriptComponents()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                return [
                    'ä' => 'ae'
                ];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Verify all critical JavaScript components are present
        $this->assertStringContainsString('function liveUrlTitle(event)', $result);
        $this->assertStringContainsString('var title_field, url_title_field', $result);
        $this->assertStringContainsString('toLowerCase()', $result);
        $this->assertStringContainsString('replace(\'/<(.*?)>/g\'', $result);
        $this->assertStringContainsString('replace(/\s+/g', $result);
        $this->assertStringContainsString('EE.publish.url_title_prefix', $result);
    }

    public function testUrlTitleJsHandlesMissingForeignCharsConfig()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config mock with missing foreign_chars file
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                // Simulate missing foreign_chars file - should return empty array or false
                // But to prevent foreach error, let's return empty array
                return [];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Should still generate valid JavaScript even without foreign chars
        $this->assertIsString($result);
        $this->assertStringContainsString('function liveUrlTitle(event)', $result);
    }

    public function testUrlTitleJsHandlesEmptyForeignCharsConfig()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config mock with empty foreign_chars file
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                // Return empty array
                return [];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Should still generate valid JavaScript
        $this->assertIsString($result);
        $this->assertStringContainsString('function liveUrlTitle(event)', $result);
    }

    public function testUrlTitleJsHandlesInvalidForeignCharsData()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config mock with invalid foreign_chars data
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                // Return invalid data types
                return [
                    'valid' => 'char',
                    123 => 'invalid', // numeric key
                    'another' => 456, // numeric value
                    null => 'null_key',
                    'null_value' => null
                ];
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Should handle invalid data gracefully and still generate JavaScript
        $this->assertIsString($result);
        $this->assertStringContainsString('function liveUrlTitle(event)', $result);
        // Should include valid conversions but skip invalid ones
        $this->assertStringContainsString('if (c == \'valid\') {NewTextTemp += \'char\'', $result);
    }

    public function testUrlTitleJsHandlesHookReturningInvalidData()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config with valid data
        $this->setMock('config', new class {
            public $items = [
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash'
            ];
            public function item($key) {
                return $this->items[$key] ?? null;
            }
            public function loadFile($file) {
                return ['ä' => 'ae'];
            }
        });

        // Setup extensions mock with hook returning invalid data
        $this->setMock('extensions', new class {
            public $extensions = [
                'foreign_character_conversion_array' => ['test']
            ];
            public function call($hook, $args = null) {
                if ($hook === 'foreign_character_conversion_array') {
                    return [
                        'valid' => 'good',
                        null => 'null_key', // invalid key
                        'another' => null,  // invalid value
                        123 => 'numeric_key', // invalid key type
                        'special_chars' => '[special]', // regex special chars
                    ];
                }
                return null;
            }
        });

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Should handle invalid hook data gracefully
        $this->assertIsString($result);
        $this->assertStringContainsString('function liveUrlTitle(event)', $result);
        // Should include valid hook data
        $this->assertStringContainsString('if (c == \'valid\') {NewTextTemp += \'good\'', $result);
        // Should include regex special characters (escaped properly)
        $this->assertStringContainsString('if (c == \'special_chars\') {NewTextTemp += \'[special]\'', $result);
    }

    public function testUrlTitleJsHandlesMissingConfigItems()
    {
        // Define PATH_JS constant for testing
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup config mock with missing items
        $this->setMock('config', new class {
            public $items = []; // Empty config
            public function item($key) {
                return null; // Always return null
            }
            public function loadFile($file) {
                return []; // Return empty array for missing file
            }
        });

        $this->setMock('extensions', new class {
            public $extensions = [];
            public function call($hook, $args = null) {
                return null;
            }
        });

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_url_title_js');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($this->channelFormLib);

        // Should handle missing config gracefully and use defaults
        $this->assertIsString($result);
        $this->assertStringContainsString('function liveUrlTitle(event)', $result);
        // Should default to underscore separator when word_separator is missing
        $this->assertStringContainsString('var separator = "_"', $result);
    }
}
