<?php

use PHPUnit\Framework\TestCase;

if (!defined('SYSPATH')) {
    define('SYSPATH', realpath(__DIR__ . '/../../../../../../..') . '/');
}

if (!defined('BASEPATH')) {
    define('BASEPATH', SYSPATH . 'ee/legacy/');
}

if (!defined('LD')) {
    define('LD', '{');
}

if (!defined('RD')) {
    define('RD', '}');
}

if (!function_exists('ee')) {
    require_once __DIR__ . '/../../../../eeObjectMock.php';
}

require_once __DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchHelperEncodeTest extends TestCase
{
    /**
     * Whether the current request header existed before the test.
     *
     * @var bool
     */
    private $requestedWithHeaderWasSet = false;

    /**
     * Original current request header value.
     *
     * @var mixed
     */
    private $requestedWithHeaderValue;

    /**
     * Preserve request header state before each helper test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestedWithHeaderWasSet = array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER);
        $this->requestedWithHeaderValue = $this->requestedWithHeaderWasSet ? $_SERVER['HTTP_X_REQUESTED_WITH'] : null;
    }

    /**
     * Reset globals and ExpressionEngine mocks after helper tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->requestedWithHeaderWasSet) {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->requestedWithHeaderValue;
        }

        if (! $this->requestedWithHeaderWasSet) {
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        }

        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    /**
     * pro_search_encode filters empty values before encoding.
     *
     * @return void
     */
    public function testFiltersEmptyValuesBeforeEncoding(): void
    {
        $encoded = pro_search_encode([
            'keywords' => 'kittens',
            'empty_string' => '',
            'null_value' => null,
            'false_value' => false,
            'zero_string' => '0',
            'zero_int' => 0,
        ]);

        $this->assertSame([
            'keywords' => 'kittens',
            'zero_string' => '0',
            'zero_int' => 0,
        ], pro_search_decode($encoded));
    }

    /**
     * pro_search_encode returns raw JSON when URL encoding is disabled.
     *
     * @return void
     */
    public function testReturnsJsonWhenUrlEncodingIsDisabled(): void
    {
        $encoded = pro_search_encode([
            'keywords' => 'alpha beta',
            'collection' => 'news',
        ], false);

        $this->assertSame('{"keywords":"alpha beta","collection":"news"}', $encoded);
    }

    /**
     * pro_search_encode emits JSON objects for list-style arrays.
     *
     * @return void
     */
    public function testForcesJsonObjectShapeForSequentialArrays(): void
    {
        $encoded = pro_search_encode(['alpha', 'beta'], false);

        $this->assertSame('{"0":"alpha","1":"beta"}', $encoded);
    }

    /**
     * pro_search_encode creates URL-safe base64 without padding.
     *
     * @return void
     */
    public function testCreatesUrlSafeBase64WithoutPadding(): void
    {
        $encoded = pro_search_encode([
            'slash' => '////',
            'keywords' => 'alpha beta',
        ]);

        $expectedJson = json_encode([
            'slash' => '////',
            'keywords' => 'alpha beta',
        ], JSON_FORCE_OBJECT);

        $this->assertSame(rtrim(str_replace('/', '_', base64_encode($expectedJson)), '='), $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
        $this->assertSame(['slash' => '////', 'keywords' => 'alpha beta'], pro_search_decode($encoded));
    }

    /**
     * pro_search_encode preserves non-empty nested values through decode.
     *
     * @return void
     */
    public function testPreservesNestedValuesThroughDecode(): void
    {
        $params = [
            'keywords' => 'alpha',
            'filters' => [
                'category' => ['1', '2'],
                'range' => ['from' => 10, 'to' => 20],
            ],
        ];

        $this->assertSame($params, pro_search_decode(pro_search_encode($params)));
    }

    /**
     * pro_search_encode returns an encoded empty JSON object for empty input.
     *
     * @return void
     */
    public function testEmptyInputEncodesEmptyJsonObject(): void
    {
        $this->assertSame('e30', pro_search_encode([]));
        $this->assertSame('{}', pro_search_encode([], false));
    }

    /**
     * pro_not_empty preserves its legacy filter boundaries.
     *
     * @dataProvider notEmptyValueProvider
     * @param mixed $value
     * @param bool $expected
     * @return void
     */
    public function testNotEmptyPreservesLegacyFilterBoundaries($value, bool $expected): void
    {
        $actual = pro_not_empty($value);

        $this->assertSame($expected, $actual);
        $this->assertIsBool($actual);
    }

    /**
     * Legacy empty and non-empty values for pro_not_empty.
     *
     * @return array
     */
    public function notEmptyValueProvider(): array
    {
        return [
            'null is empty' => [null, false],
            'false is empty' => [false, false],
            'empty string is empty' => ['', false],
            'zero string is not empty' => ['0', true],
            'zero integer is not empty' => [0, true],
            'zero float is not empty' => [0.0, true],
            'empty array is not empty' => [[], true],
            'non-empty array is not empty' => [['alpha'], true],
            'true is not empty' => [true, true],
            'blank string is not empty' => [' ', true],
            'object is not empty' => [new stdClass(), true],
        ];
    }

    /**
     * pro_not_empty subprocess coverage exercises all reachable branches.
     *
     * @return void
     */
    public function testNotEmptyCoverageSubprocessCoversBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/pro-search-helper-pro-not-empty-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/pro_search_helper_pro_not_empty_coverage.php';
        $command = escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(__DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php'),
            $result['real_module_path']
        );
        $this->assertFalse($result['results']['null']);
        $this->assertFalse($result['results']['false']);
        $this->assertFalse($result['results']['empty_string']);
        $this->assertTrue($result['results']['zero_string']);
        $this->assertTrue($result['results']['zero_integer']);
        $this->assertTrue($result['results']['zero_float']);
        $this->assertTrue($result['results']['empty_array']);
        $this->assertTrue($result['results']['object']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * pro_array_is_numeric returns true for its default empty input.
     *
     * @return void
     */
    public function testArrayIsNumericReturnsTrueForDefaultInput(): void
    {
        $actual = pro_array_is_numeric();

        $this->assertTrue($actual);
        $this->assertIsBool($actual);
    }

    /**
     * pro_array_is_numeric preserves legacy scalar input boundaries.
     *
     * @dataProvider arrayIsNumericLegacyScalarProvider
     * @param mixed $value
     * @return void
     */
    public function testArrayIsNumericPreservesLegacyScalarInputBoundaries($value): void
    {
        $actual = @pro_array_is_numeric($value);

        $this->assertTrue($actual);
        $this->assertIsBool($actual);
    }

    /**
     * Legacy scalar values for pro_array_is_numeric.
     *
     * @return array
     */
    public function arrayIsNumericLegacyScalarProvider(): array
    {
        return [
            'null input' => [null],
            'false input' => [false],
            'empty string input' => [''],
            'numeric string input' => ['123'],
        ];
    }

    /**
     * pro_array_is_numeric preserves legacy numeric array semantics.
     *
     * @dataProvider arrayIsNumericValueProvider
     * @param array $value
     * @param bool $expected
     * @return void
     */
    public function testArrayIsNumericPreservesLegacyNumericArraySemantics(array $value, bool $expected): void
    {
        $actual = pro_array_is_numeric($value);

        $this->assertSame($expected, $actual);
        $this->assertIsBool($actual);
    }

    /**
     * Numeric and non-numeric array values for pro_array_is_numeric.
     *
     * @return array
     */
    public function arrayIsNumericValueProvider(): array
    {
        return [
            'empty array is numeric' => [[], true],
            'integer zero is numeric' => [[0], true],
            'integers and floats are numeric' => [[1, -2, 3.5], true],
            'numeric strings are numeric' => [['1', '0', '-2.5', '1e3'], true],
            'associative keys do not affect numeric values' => [['first' => '10', 'second' => 20], true],
            'non-numeric string is not numeric' => [[1, 'alpha', 2], false],
            'null value is not numeric' => [[1, null], false],
            'boolean value is not numeric' => [[true], false],
            'nested array value is not numeric' => [[[1]], false],
        ];
    }

    /**
     * pro_array_is_numeric subprocess coverage exercises all reachable branches.
     *
     * @return void
     */
    public function testArrayIsNumericCoverageSubprocessCoversBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/pro-search-helper-array-is-numeric-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/pro_search_helper_array_is_numeric_coverage.php';
        $command = escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(__DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php'),
            $result['real_module_path']
        );
        $this->assertTrue($result['results']['default_empty']);
        $this->assertTrue($result['results']['empty_array']);
        $this->assertTrue($result['results']['integer_and_float']);
        $this->assertTrue($result['results']['numeric_strings']);
        $this->assertTrue($result['results']['associative_numeric_values']);
        $this->assertTrue($result['results']['legacy_null_input']);
        $this->assertFalse($result['results']['non_numeric_string']);
        $this->assertFalse($result['results']['null_value']);
        $this->assertFalse($result['results']['boolean_value']);
        $this->assertFalse($result['results']['nested_array_value']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * pro_array_get_prefixed returns matching parameters with original keys.
     *
     * @return void
     */
    public function testArrayGetPrefixedReturnsMatchingValuesWithOriginalKeys(): void
    {
        $params = [
            'form_id' => '10',
            'form_class' => 'primary',
            'not_form_id' => '20',
            'Form_id' => 'wrong-case',
            'form_empty' => '',
            'form_zero' => 0,
            'form_false' => false,
            'form_null' => null,
        ];

        $actual = pro_array_get_prefixed($params, 'form_');

        $this->assertSame([
            'form_id' => '10',
            'form_class' => 'primary',
            'form_empty' => '',
            'form_zero' => 0,
            'form_false' => false,
            'form_null' => null,
        ], $actual);
    }

    /**
     * pro_array_get_prefixed strips prefixes only when requested with strict true.
     *
     * @dataProvider arrayGetPrefixedStripProvider
     * @param mixed $strip
     * @param array $expected
     * @return void
     */
    public function testArrayGetPrefixedStripsPrefixesOnlyWhenStrictTrue($strip, array $expected): void
    {
        $params = [
            'form_id' => '10',
            'form_' => 'empty-key',
            'form_class' => 'primary',
            'not_form_id' => '20',
        ];

        $this->assertSame($expected, pro_array_get_prefixed($params, 'form_', $strip));
    }

    /**
     * Prefix stripping flag inputs for pro_array_get_prefixed.
     *
     * @return array
     */
    public function arrayGetPrefixedStripProvider(): array
    {
        return [
            'strict true strips prefix' => [
                true,
                [
                    'id' => '10',
                    '' => 'empty-key',
                    'class' => 'primary',
                ],
            ],
            'false keeps original keys' => [
                false,
                [
                    'form_id' => '10',
                    'form_' => 'empty-key',
                    'form_class' => 'primary',
                ],
            ],
            'truthy non-boolean keeps original keys' => [
                1,
                [
                    'form_id' => '10',
                    'form_' => 'empty-key',
                    'form_class' => 'primary',
                ],
            ],
        ];
    }

    /**
     * pro_array_get_prefixed returns an empty array without a usable array and prefix.
     *
     * @dataProvider arrayGetPrefixedEmptyInputProvider
     * @param mixed $array
     * @param mixed $prefix
     * @return void
     */
    public function testArrayGetPrefixedReturnsEmptyArrayWithoutUsableArrayAndPrefix($array, $prefix): void
    {
        $this->assertSame([], @pro_array_get_prefixed($array, $prefix, true));
    }

    /**
     * pro_array_get_prefixed treats the zero string as a usable prefix.
     *
     * @return void
     */
    public function testArrayGetPrefixedTreatsZeroStringAsUsablePrefix(): void
    {
        $this->assertSame(
            ['id' => '10'],
            pro_array_get_prefixed(['0id' => '10', 'form_id' => '20'], '0', true)
        );
    }

    /**
     * Empty-input boundaries for pro_array_get_prefixed.
     *
     * @return array
     */
    public function arrayGetPrefixedEmptyInputProvider(): array
    {
        return [
            'empty array' => [[], 'form_'],
            'null array' => [null, 'form_'],
            'false array' => [false, 'form_'],
            'string array' => ['form_id=10', 'form_'],
            'empty prefix' => [['form_id' => '10'], ''],
            'null prefix' => [['form_id' => '10'], null],
            'false prefix' => [['form_id' => '10'], false],
        ];
    }

    /**
     * pro_array_get_prefixed subprocess coverage records all reachable behavior.
     *
     * @return void
     */
    public function testArrayGetPrefixedCoverageSubprocessRecordsReachableBehavior(): void
    {
        $outputFile = sys_get_temp_dir() . '/pro-search-helper-array-get-prefixed-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/pro_search_helper_array_get_prefixed_coverage.php';
        $command = escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(__DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php'),
            $result['real_module_path']
        );
        $this->assertSame(
            ['form_id', 'form_class', 'form_empty', 'form_zero', 'form_false', 'form_null'],
            array_keys($result['original_keys'])
        );
        $this->assertSame(['id', '', 'class'], array_keys($result['stripped_keys']));
        $this->assertSame(['form_id', 'form_', 'form_class'], array_keys($result['truthy_strip_keys']));
        $this->assertSame([], $result['empty_prefix']);
        $this->assertSame([], $result['null_array']);
        $this->assertSame([], $result['string_array']);
        $this->assertSame(['0id' => '10'], $result['zero_string_prefix']);
        $this->assertSame('', $result['original_keys']['form_empty']);
        $this->assertSame(0, $result['original_keys']['form_zero']);
        $this->assertFalse($result['original_keys']['form_false']);
        $this->assertNull($result['original_keys']['form_null']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertGreaterThanOrEqual(90.0, $result['branch_percentage']);
        }
    }

    /**
     * pro_array_add_prefix casts values while preserving keys and caller input.
     *
     * @return void
     */
    public function testArrayAddPrefixCastsValuesAndPreservesKeysWithoutMutatingCallerInput(): void
    {
        $params = [
            'entry_id' => 12,
            7 => 'seven',
            'zero' => 0,
            'false' => false,
            'null' => null,
            'float' => 3.5,
            'true' => true,
        ];

        $actual = pro_array_add_prefix($params, 'cat:');

        $this->assertSame([
            'entry_id' => 'cat:12',
            7 => 'cat:seven',
            'zero' => 'cat:0',
            'false' => 'cat:',
            'null' => 'cat:',
            'float' => 'cat:3.5',
            'true' => 'cat:1',
        ], $actual);
        $this->assertSame([
            'entry_id' => 12,
            7 => 'seven',
            'zero' => 0,
            'false' => false,
            'null' => null,
            'float' => 3.5,
            'true' => true,
        ], $params);
    }

    /**
     * pro_array_add_prefix preserves legacy prefix boundary behavior.
     *
     * @dataProvider arrayAddPrefixBoundaryProvider
     * @param array $params
     * @param mixed $prefix
     * @param array $expected
     * @return void
     */
    public function testArrayAddPrefixPreservesPrefixBoundaries(array $params, $prefix, array $expected): void
    {
        $this->assertSame($expected, @pro_array_add_prefix($params, $prefix));
    }

    /**
     * Prefix boundary inputs for pro_array_add_prefix.
     *
     * @return array
     */
    public function arrayAddPrefixBoundaryProvider(): array
    {
        return [
            'empty prefix still casts values' => [
                ['entry_id' => 12, 'keyword' => 'alpha'],
                '',
                ['entry_id' => '12', 'keyword' => 'alpha'],
            ],
            'zero string prefix is preserved' => [
                ['entry_id' => 12],
                '0',
                ['entry_id' => '012'],
            ],
            'null prefix behaves as an empty prefix' => [
                ['entry_id' => 12],
                null,
                ['entry_id' => '12'],
            ],
            'false prefix behaves as an empty prefix' => [
                ['entry_id' => 12],
                false,
                ['entry_id' => '12'],
            ],
            'empty array returns empty array' => [
                [],
                'cat:',
                [],
            ],
        ];
    }

    /**
     * pro_array_add_prefix preserves legacy non-array input behavior.
     *
     * @dataProvider arrayAddPrefixLegacyNonArrayProvider
     * @param mixed $params
     * @return void
     */
    public function testArrayAddPrefixPreservesLegacyNonArrayInputBoundaries($params): void
    {
        $this->assertSame($params, @pro_array_add_prefix($params, 'cat:'));
    }

    /**
     * Legacy non-array inputs for pro_array_add_prefix.
     *
     * @return array
     */
    public function arrayAddPrefixLegacyNonArrayProvider(): array
    {
        return [
            'null input' => [null],
            'false input' => [false],
            'empty string input' => [''],
        ];
    }

    /**
     * pro_array_add_prefix subprocess coverage exercises all reachable branches.
     *
     * @return void
     */
    public function testArrayAddPrefixCoverageSubprocessCoversBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/pro-search-helper-array-add-prefix-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/pro_search_helper_array_add_prefix_coverage.php';
        $command = escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(__DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php'),
            $result['real_module_path']
        );
        $this->assertSame([
            'entry_id' => 'cat:12',
            7 => 'cat:seven',
            'zero' => 'cat:0',
            'false' => 'cat:',
            'null' => 'cat:',
            'float' => 'cat:3.5',
            'true' => 'cat:1',
        ], $result['prefixed_values']);
        $this->assertSame(['entry_id' => '12', 'keyword' => 'alpha'], $result['empty_prefix']);
        $this->assertSame(['entry_id' => '012'], $result['zero_string_prefix']);
        $this->assertSame(['entry_id' => '12'], $result['null_prefix']);
        $this->assertSame(['entry_id' => '12'], $result['false_prefix']);
        $this->assertSame([], $result['empty_array']);
        $this->assertNull($result['null_input']);
        $this->assertFalse($result['false_input']);
        $this->assertSame('', $result['empty_string_input']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * pro_param_is_numeric accepts legacy numeric parameter syntax.
     *
     * @dataProvider paramIsNumericAcceptedProvider
     * @param string $value
     * @return void
     */
    public function testParamIsNumericAcceptsLegacyNumericParameterSyntax(string $value): void
    {
        $actual = pro_param_is_numeric($value);

        $this->assertSame(1, $actual);
        $this->assertIsInt($actual);
    }

    /**
     * Accepted numeric parameter syntax values.
     *
     * @return array
     */
    public function paramIsNumericAcceptedProvider(): array
    {
        return [
            'zero' => ['0'],
            'plain digits' => ['123'],
            'pipe-separated digits' => ['1|2|300'],
            'ampersand-separated digits' => ['1&2&300'],
            'mixed separators' => ['1|2&300'],
            'equals-prefixed digits' => ['=123'],
            'equals-prefixed list' => ['=1|2&300'],
            'lowercase not-prefixed digits' => ['not 123'],
            'uppercase not-prefixed digits' => ['NOT 123'],
            'not-prefixed mixed list' => ['not 1|2&300'],
            'not prefix with tab whitespace' => ["not\t123"],
            'trailing pipe separator' => ['1|'],
            'trailing ampersand separator' => ['not 1&'],
        ];
    }

    /**
     * pro_param_is_numeric rejects non-numeric parameter syntax.
     *
     * @dataProvider paramIsNumericRejectedProvider
     * @param string $value
     * @return void
     */
    public function testParamIsNumericRejectsNonNumericParameterSyntax(string $value): void
    {
        $actual = pro_param_is_numeric($value);

        $this->assertSame(0, $actual);
        $this->assertIsInt($actual);
    }

    /**
     * Rejected non-numeric parameter syntax values.
     *
     * @return array
     */
    public function paramIsNumericRejectedProvider(): array
    {
        return [
            'empty string' => [''],
            'letters only' => ['alpha'],
            'digits with letters' => ['123alpha'],
            'bare not' => ['not'],
            'not without whitespace' => ['not123'],
            'not with multiple spaces' => ['not  123'],
            'negative number' => ['-1'],
            'not negative number' => ['not -1'],
            'decimal number' => ['1.5'],
            'scientific notation' => ['1e3'],
            'comma-separated digits' => ['1,2'],
            'repeated pipe separator' => ['1||2'],
            'repeated ampersand separator' => ['1&&2'],
            'leading pipe separator' => ['|1'],
            'leading ampersand separator' => ['&1'],
            'separator with leading space' => ['1 |2'],
            'separator with trailing space' => ['1| 2'],
            'equals with whitespace' => ['= 1'],
            'double equals' => ['==1'],
            'not with equals' => ['not =1'],
        ];
    }

    /**
     * pro_param_is_numeric subprocess coverage exercises all reachable code.
     *
     * @return void
     */
    public function testParamIsNumericCoverageSubprocessCoversReachableCode(): void
    {
        $outputFile = sys_get_temp_dir() . '/pro-search-helper-param-is-numeric-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/pro_search_helper_param_is_numeric_coverage.php';
        $command = escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(__DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php'),
            $result['real_module_path']
        );
        $this->assertSame([
            'zero' => 1,
            'plain_digits' => 1,
            'pipe_separated_digits' => 1,
            'ampersand_separated_digits' => 1,
            'mixed_separators' => 1,
            'equals_prefixed_digits' => 1,
            'equals_prefixed_list' => 1,
            'not_prefixed_digits' => 1,
            'uppercase_not_prefixed_digits' => 1,
            'not_prefixed_mixed_list' => 1,
            'not_prefix_with_tab_whitespace' => 1,
            'trailing_pipe_separator' => 1,
            'trailing_ampersand_separator' => 1,
        ], $result['accepted']);
        $this->assertSame([
            'empty_string' => 0,
            'letters_only' => 0,
            'digits_with_letters' => 0,
            'bare_not' => 0,
            'not_without_whitespace' => 0,
            'not_with_multiple_spaces' => 0,
            'negative_number' => 0,
            'not_negative_number' => 0,
            'decimal_number' => 0,
            'scientific_notation' => 0,
            'comma_separated_digits' => 0,
            'repeated_pipe_separator' => 0,
            'repeated_ampersand_separator' => 0,
            'leading_pipe_separator' => 0,
            'leading_ampersand_separator' => 0,
            'separator_with_leading_space' => 0,
            'separator_with_trailing_space' => 0,
            'equals_with_whitespace' => 0,
            'double_equals' => 0,
            'not_with_equals' => 0,
        ], $result['rejected']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * pro_search_decode returns an empty array for invalid input.
     *
     * @dataProvider invalidDecodeInputProvider
     * @param mixed $value
     * @return void
     */
    public function testDecodeReturnsEmptyArrayForInvalidInput($value): void
    {
        $this->assertSame([], pro_search_decode($value));
    }

    /**
     * Invalid input values for pro_search_decode.
     *
     * @return array
     */
    public function invalidDecodeInputProvider(): array
    {
        return [
            'empty string' => [''],
            'null' => [null],
            'false' => [false],
            'zero integer' => [0],
            'array' => [[]],
        ];
    }

    /**
     * pro_search_decode returns raw JSON arrays when URL decoding is disabled.
     *
     * @return void
     */
    public function testDecodeReturnsRawJsonWhenUrlDecodingIsDisabled(): void
    {
        $rawJson = '{"keywords":"alpha beta","filters":{"category":["1","2"]}}';

        $this->assertSame([
            'keywords' => 'alpha beta',
            'filters' => [
                'category' => ['1', '2'],
            ],
        ], pro_search_decode($rawJson, false));
    }

    /**
     * pro_search_decode returns raw serialized arrays when URL decoding is disabled.
     *
     * @return void
     */
    public function testDecodeReturnsRawSerializedArraysWhenUrlDecodingIsDisabled(): void
    {
        $params = [
            'keywords' => 'serialized',
            'collection' => 'articles',
        ];

        $this->assertSame($params, pro_search_decode(serialize($params), false));
    }

    /**
     * pro_search_decode forces URL decoding for legacy serialized payloads.
     *
     * @return void
     */
    public function testDecodeForcesUrlDecodingForLegacySerializedPayloads(): void
    {
        $params = [
            'keywords' => 'legacy',
            'collection' => 'archive',
        ];
        $encoded = base64_encode(serialize($params));

        $this->assertStringStartsWith('YTo', $encoded);
        $this->assertSame($params, pro_search_decode($encoded, false));
    }

    /**
     * pro_search_decode repairs URI spaces before base64 decoding.
     *
     * @return void
     */
    public function testDecodeRepairsUriSpacesBeforeBase64Decoding(): void
    {
        $params = ['k' => '/>'];
        $encoded = base64_encode(json_encode($params, JSON_FORCE_OBJECT));

        $this->assertStringContainsString('+', $encoded);
        $this->assertSame($params, pro_search_decode(str_replace('+', ' ', $encoded)));
    }

    /**
     * pro_search_decode restores URL-safe underscores before base64 decoding.
     *
     * @return void
     */
    public function testDecodeRestoresUrlSafeUnderscoresBeforeBase64Decoding(): void
    {
        $params = ['k' => '/?'];
        $encoded = base64_encode(json_encode($params, JSON_FORCE_OBJECT));
        $urlSafe = rtrim(str_replace('/', '_', $encoded), '=');

        $this->assertStringContainsString('/', $encoded);
        $this->assertStringContainsString('_', $urlSafe);
        $this->assertStringNotContainsString('/', $urlSafe);
        $this->assertSame($params, pro_search_decode($urlSafe));
    }

    /**
     * pro_search_decode returns an empty array when the decoded payload is not an array.
     *
     * @return void
     */
    public function testDecodeReturnsEmptyArrayWhenDecodedPayloadIsNotArray(): void
    {
        $encodedScalar = base64_encode(json_encode('scalar'));

        $this->assertSame([], pro_search_decode($encodedScalar));
    }

    /**
     * pro_clean_string returns empty input unchanged without loading words.
     *
     * @dataProvider cleanStringEmptyInputProvider
     * @param mixed $value
     * @return void
     */
    public function testCleanStringReturnsEmptyInputWithoutLoadingWords($value): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->never())->method('library');
        ee()->setMock('load', $loader);

        $this->assertSame($value, pro_clean_string($value, ['ignored']));
    }

    /**
     * Empty legacy inputs for pro_clean_string.
     *
     * @return array
     */
    public function cleanStringEmptyInputProvider(): array
    {
        return [
            'empty string' => [''],
            'zero string' => ['0'],
            'zero integer' => [0],
            'null' => [null],
            'false' => [false],
        ];
    }

    /**
     * pro_clean_string loads words and returns the final cleaned string.
     *
     * @return void
     */
    public function testCleanStringLoadsWordsAndReturnsFinalCleanedString(): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->once())
            ->method('library')
            ->with('pro_search_words');
        ee()->setMock('load', $loader);

        $words = $this->getMockBuilder('stdClass')
            ->addMethods(['clean', 'remove_diacritics'])
            ->getMock();
        $words->expects($this->once())
            ->method('clean')
            ->with(' Cafe Search ', true)
            ->willReturn('cleaned cafe search');
        $words->expects($this->once())
            ->method('remove_diacritics')
            ->with('cleaned cafe search')
            ->willReturn('cafe search');
        ee()->setMock('pro_search_words', $words);

        $this->assertSame('cafe search', pro_clean_string(' Cafe Search ', ['cafe']));
    }

    /**
     * pro_clean_string casts the ignore argument before cleaning.
     *
     * @dataProvider cleanStringIgnoreCoercionProvider
     * @param mixed $ignore
     * @param bool $expectedIgnore
     * @return void
     */
    public function testCleanStringCastsIgnoreArgumentBeforeCleaning($ignore, bool $expectedIgnore): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->once())
            ->method('library')
            ->with('pro_search_words');
        ee()->setMock('load', $loader);

        $words = $this->getMockBuilder('stdClass')
            ->addMethods(['clean', 'remove_diacritics'])
            ->getMock();
        $words->expects($this->once())
            ->method('clean')
            ->with('alpha beta', $expectedIgnore)
            ->willReturn('clean alpha beta');
        $words->expects($this->once())
            ->method('remove_diacritics')
            ->with('clean alpha beta')
            ->willReturn('clean alpha beta');
        ee()->setMock('pro_search_words', $words);

        $this->assertSame('clean alpha beta', pro_clean_string('alpha beta', $ignore));
    }

    /**
     * Ignore argument coercion inputs for pro_clean_string.
     *
     * @return array
     */
    public function cleanStringIgnoreCoercionProvider(): array
    {
        return [
            'null ignore' => [null, false],
            'empty array ignore' => [[], false],
            'empty string ignore' => ['', false],
            'zero string ignore' => ['0', false],
            'stop word array ignore' => [['alpha'], true],
            'string ignore' => ['alpha', true],
            'integer ignore' => [1, true],
        ];
    }

    /**
     * pro_format applies the default HTML and EE parameter encoding.
     *
     * @return void
     */
    public function testFormatAppliesDefaultHtmlAndEeParameterEncoding(): void
    {
        $this->assertSame(
            'A &amp; &lt;B&gt; &#123;x&#125; &quot;q&quot;',
            pro_format('A & <B> {x} "q"')
        );
    }

    /**
     * pro_format applies requested string formats.
     *
     * @dataProvider formatProvider
     * @param string $value
     * @param string $format
     * @param string $expected
     * @return void
     */
    public function testFormatAppliesRequestedStringFormats(string $value, string $format, string $expected): void
    {
        $this->assertSame($expected, pro_format($value, $format));
    }

    /**
     * Format inputs for pro_format.
     *
     * @return array
     */
    public function formatProvider(): array
    {
        return [
            'url encoding' => [
                'A & <B> {x} "q"',
                'url',
                'A+%26+%3CB%3E+%7Bx%7D+%22q%22',
            ],
            'explicit html encoding' => [
                'A & <B> {x} "q"',
                'html',
                'A &amp; &lt;B&gt; &#123;x&#125; &quot;q&quot;',
            ],
            'ee encode' => [
                'A & <B> {x} "q"',
                'ee-encode',
                'A & <B> &#123;x&#125; &quot;q&quot;',
            ],
            'ee decode' => [
                'A &amp; &lt;B&gt; &#123;x&#125; &quot;q&quot;',
                'ee-decode',
                'A &amp; &lt;B&gt; {x} "q"',
            ],
            'unknown format passthrough' => [
                'A & <B> {x} "q"',
                'raw',
                'A & <B> {x} "q"',
            ],
        ];
    }

    /**
     * pro_format delegates clean formatting through Pro Search words.
     *
     * @return void
     */
    public function testFormatDelegatesCleanFormatThroughProSearchWords(): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->once())
            ->method('library')
            ->with('pro_search_words');
        ee()->setMock('load', $loader);

        $words = $this->getMockBuilder('stdClass')
            ->addMethods(['clean', 'remove_diacritics'])
            ->getMock();
        $words->expects($this->once())
            ->method('clean')
            ->with(' Café Search ', false)
            ->willReturn('cleaned café search');
        $words->expects($this->once())
            ->method('remove_diacritics')
            ->with('cleaned café search')
            ->willReturn('cleaned cafe search');
        ee()->setMock('pro_search_words', $words);

        $this->assertSame('cleaned cafe search', pro_format(' Café Search ', 'clean'));
    }

    /**
     * pro_format preserves clean format's empty-string guard.
     *
     * @return void
     */
    public function testFormatCleanReturnsEmptyStringWithoutLoadingWords(): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->never())->method('library');
        ee()->setMock('load', $loader);

        $this->assertSame('', pro_format('', 'clean'));
    }

    /**
     * pro_param_string builds legacy parameter strings from string values only.
     *
     * @dataProvider paramStringProvider
     * @param array $params
     * @param string $expected
     * @return void
     */
    public function testParamStringBuildsLegacyParameterStrings(array $params, string $expected): void
    {
        $actual = pro_param_string($params);

        $this->assertSame($expected, $actual);
        $this->assertIsString($actual);

        if ($expected === '') {
            return;
        }

        $this->assertSame($expected, trim($actual));
    }

    /**
     * Parameter string inputs.
     *
     * @return array
     */
    public function paramStringProvider(): array
    {
        return [
            'ordered string parameters' => [
                [
                    'keywords' => 'alpha beta',
                    'collection' => 'news',
                    'sort' => 'desc',
                ],
                'keywords="alpha beta" collection="news" sort="desc"',
            ],
            'string boundary values' => [
                [
                    'empty' => '',
                    'zero' => '0',
                    'spaces' => '  alpha  ',
                ],
                'empty="" zero="0" spaces="  alpha  "',
            ],
            'literal string values' => [
                [
                    'quote' => 'a "quoted" value',
                    'html' => '<b>&</b>',
                    'braces' => '{segment_1}',
                ],
                'quote="a "quoted" value" html="<b>&</b>" braces="{segment_1}"',
            ],
            'empty input' => [
                [],
                '',
            ],
            'all non-string values skipped' => [
                [
                    'none' => null,
                    'false' => false,
                    'true' => true,
                    'count' => 3,
                    'items' => ['alpha'],
                    'object' => new stdClass(),
                ],
                '',
            ],
            'mixed values preserve surviving string order' => [
                [
                    'before' => 'alpha',
                    'limit' => 10,
                    'after' => 'beta',
                    'zero_string' => '0',
                    'empty' => '',
                ],
                'before="alpha" after="beta" zero_string="0" empty=""',
            ],
        ];
    }

    /**
     * pro_param_string preserves its legacy null-input boundary.
     *
     * @return void
     */
    public function testParamStringTreatsLegacyNullInputAsEmptyString(): void
    {
        $this->assertSame('', @pro_param_string(null));
    }

    /**
     * pro_prep_in_conditionals expands legacy IN conditionals.
     *
     * @dataProvider prepInConditionalsProvider
     * @param string $tagdata
     * @param string $expected
     * @return void
     */
    public function testPrepInConditionalsExpandsLegacyConditionals(string $tagdata, string $expected): void
    {
        $actual = pro_prep_in_conditionals($tagdata);

        $this->assertSame($expected, $actual);
        $this->assertIsString($actual);
    }

    /**
     * Legacy IN conditional inputs.
     *
     * @return array
     */
    public function prepInConditionalsProvider(): array
    {
        return [
            'plain in values' => [
                'before {if status IN (open|closed)}body{/if} after',
                'before {if status == "open" OR status == "closed"}body{/if} after',
            ],
            'not in values' => [
                '{if entry_id NOT IN (1|2|3)}hidden{/if}',
                '{if entry_id != "1" AND entry_id != "2" AND entry_id != "3"}hidden{/if}',
            ],
            'compact not in hyphenated identifier' => [
                '{if field-name NOTIN (alpha|beta)}hidden{/if}',
                '{if field-name != "alpha" AND field-name != "beta"}hidden{/if}',
            ],
            'legacy ampersand separators' => [
                '{if category IN (news&amp;sports&arts)}',
                '{if category == "news" OR category == "sports" OR category == "arts"}',
            ],
            'quoted operands' => [
                '{if "status" IN ("open"|\'closed\'|pending)}',
                '{if "status" == "open" OR "status" == \'closed\' OR "status" == "pending"}',
            ],
            'multiple conditionals' => [
                'before {if foo IN (1|2)}A{/if} middle {if bar NOT IN (3|4)}B{/if} after',
                'before {if foo == "1" OR foo == "2"}A{/if} middle {if bar != "3" AND bar != "4"}B{/if} after',
            ],
            'empty item list boundary' => [
                '{if status IN ()}empty{/if}',
                '{if status == ""}empty{/if}',
            ],
        ];
    }

    /**
     * pro_prep_in_conditionals leaves non-matching tagdata unchanged.
     *
     * @return void
     */
    public function testPrepInConditionalsLeavesUnmatchedTagdataUnchanged(): void
    {
        $tagdata = '{if status in (open|closed)}body{/if} {if status == "open"}open{/if}';

        $this->assertSame($tagdata, pro_prep_in_conditionals($tagdata));
        $this->assertSame('', pro_prep_in_conditionals());
    }

    /**
     * pro_flatten_results returns indexed values by default.
     *
     * @return void
     */
    public function testFlattenResultsReturnsIndexedValuesByDefault(): void
    {
        $rows = [
            ['channel_id' => '5', 'collection_id' => 'news'],
            ['channel_id' => '6', 'collection_id' => 'blog'],
            ['channel_id' => '5', 'collection_id' => 'archive'],
        ];

        $this->assertSame(['5', '6', '5'], pro_flatten_results($rows, 'channel_id'));
    }

    /**
     * pro_flatten_results returns an empty array for empty result sets.
     *
     * @return void
     */
    public function testFlattenResultsReturnsEmptyArrayForEmptyResultSets(): void
    {
        $this->assertSame([], pro_flatten_results([], 'channel_id'));
    }

    /**
     * pro_flatten_results preserves its legacy null-input boundary.
     *
     * @return void
     */
    public function testFlattenResultsTreatsLegacyNullInputAsEmptyArray(): void
    {
        $this->assertSame([], @pro_flatten_results(null, 'channel_id'));
    }

    /**
     * pro_flatten_results preserves scalar value types without filtering.
     *
     * @return void
     */
    public function testFlattenResultsPreservesScalarValueTypes(): void
    {
        $rows = [
            ['value' => 0],
            ['value' => '0'],
            ['value' => false],
            ['value' => null],
            ['value' => ''],
        ];

        $this->assertSame([0, '0', false, null, ''], pro_flatten_results($rows, 'value'));
    }

    /**
     * pro_flatten_results indexes values by the requested key.
     *
     * @return void
     */
    public function testFlattenResultsIndexesValuesByRequestedKey(): void
    {
        $rows = [
            ['collection_id' => 'news', 'channel_id' => '5'],
            ['collection_id' => 'blog', 'channel_id' => '6'],
        ];

        $this->assertSame(
            ['news' => '5', 'blog' => '6'],
            pro_flatten_results($rows, 'channel_id', 'collection_id')
        );
    }

    /**
     * pro_flatten_results keeps the last value for duplicate keys.
     *
     * @return void
     */
    public function testFlattenResultsKeepsLastValueForDuplicateKeys(): void
    {
        $rows = [
            ['collection_id' => 'news', 'channel_id' => '5'],
            ['collection_id' => 'news', 'channel_id' => '7'],
            ['collection_id' => 'blog', 'channel_id' => '6'],
        ];

        $this->assertSame(
            ['news' => '7', 'blog' => '6'],
            pro_flatten_results($rows, 'channel_id', 'collection_id')
        );
    }

    /**
     * pro_flatten_results treats only false as indexed mode.
     *
     * @dataProvider flattenResultsStrictFalseKeyProvider
     * @param mixed $key
     * @param array $row
     * @param array $expected
     * @return void
     */
    public function testFlattenResultsTreatsOnlyFalseAsIndexedMode($key, array $row, array $expected): void
    {
        $this->assertSame($expected, @pro_flatten_results([$row], 'value', $key));
    }

    /**
     * Strict-false key boundary inputs for pro_flatten_results.
     *
     * @return array
     */
    public function flattenResultsStrictFalseKeyProvider(): array
    {
        return [
            'null key uses empty string row key' => [
                null,
                ['' => 'empty-key', 'value' => 'alpha'],
                ['empty-key' => 'alpha'],
            ],
            'integer zero key uses numeric row key' => [
                0,
                [0 => 'zero-key', 'value' => 'beta'],
                ['zero-key' => 'beta'],
            ],
        ];
    }

    /**
     * pro_associate_results indexes complete rows by the requested key.
     *
     * @return void
     */
    public function testAssociateResultsIndexesRowsByRequestedKey(): void
    {
        $rows = [
            ['collection_id' => 'news', 'channel_id' => '5'],
            ['collection_id' => 'blog', 'channel_id' => '6'],
        ];

        $actual = pro_associate_results($rows, 'collection_id');

        $this->assertSame([
            'news' => ['collection_id' => 'news', 'channel_id' => '5'],
            'blog' => ['collection_id' => 'blog', 'channel_id' => '6'],
        ], $actual);
        $this->assertSame(['news', 'blog'], array_keys($actual));
    }

    /**
     * pro_associate_results keeps first rows and skips rows missing the key.
     *
     * @return void
     */
    public function testAssociateResultsKeepsFirstRowForDuplicateKeysAndIgnoresMissingKeys(): void
    {
        $rows = [
            ['collection_id' => 'news', 'channel_id' => '5'],
            ['collection_id' => 'news', 'channel_id' => '7'],
            ['channel_id' => '8'],
            ['collection_id' => 'blog', 'channel_id' => '6'],
        ];

        $this->assertSame([
            'news' => ['collection_id' => 'news', 'channel_id' => '5'],
            'blog' => ['collection_id' => 'blog', 'channel_id' => '6'],
        ], pro_associate_results($rows, 'collection_id'));
    }

    /**
     * pro_associate_results sorts only when the sort flag is strict true.
     *
     * @return void
     */
    public function testAssociateResultsSortsByKeyOnlyWhenSortIsStrictTrue(): void
    {
        $rows = [
            ['collection_id' => 'beta', 'channel_id' => '6'],
            ['collection_id' => 'alpha', 'channel_id' => '5'],
        ];

        $this->assertSame(
            ['alpha', 'beta'],
            array_keys(pro_associate_results($rows, 'collection_id', true))
        );
        $this->assertSame(
            ['beta', 'alpha'],
            array_keys(pro_associate_results($rows, 'collection_id', 1))
        );
    }

    /**
     * pro_associate_results preserves normalized PHP array-key boundaries.
     *
     * @return void
     */
    public function testAssociateResultsPreservesNormalizedBoundaryKeys(): void
    {
        $zeroIntegerRow = ['collection_id' => 0, 'label' => 'zero integer'];
        $zeroStringRow = ['collection_id' => '0', 'label' => 'zero string'];
        $nullRow = ['collection_id' => null, 'label' => 'null'];
        $emptyStringRow = ['collection_id' => '', 'label' => 'empty string'];

        $actual = pro_associate_results([
            $zeroIntegerRow,
            $zeroStringRow,
            $nullRow,
            $emptyStringRow,
        ], 'collection_id');

        $this->assertSame([
            0 => $zeroIntegerRow,
            '' => $nullRow,
        ], $actual);
    }

    /**
     * pro_associate_results returns empty arrays for empty and legacy null input.
     *
     * @return void
     */
    public function testAssociateResultsReturnsEmptyArrayForEmptyAndLegacyNullResultSets(): void
    {
        $this->assertSame([], pro_associate_results([], 'collection_id'));
        $this->assertSame([], @pro_associate_results(null, 'collection_id'));
    }

    /**
     * pro_associate_results subprocess coverage exercises all reachable branches.
     *
     * @return void
     */
    public function testAssociateResultsCoverageSubprocessCoversBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/pro-search-helper-associate-results-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/pro_search_helper_associate_results_coverage.php';
        $command = escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(__DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php'),
            $result['real_module_path']
        );
        $this->assertSame(['news', 'blog'], array_keys($result['unique_result']));
        $this->assertSame(['news', 'blog'], array_keys($result['duplicate_missing_result']));
        $this->assertSame(['alpha', 'beta'], array_keys($result['sorted_result']));
        $this->assertSame(['beta', 'alpha'], array_keys($result['strict_sort_result']));
        $this->assertSame([], $result['empty_result']);
        $this->assertSame([], $result['null_result']);
        $this->assertSame([0, ''], array_keys($result['boundary_result']));

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * is_ajax returns true only for the exact legacy Ajax header.
     *
     * @return void
     */
    public function testIsAjaxReturnsTrueForExactRequestedWithHeader(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $actual = is_ajax();

        $this->assertTrue($actual);
        $this->assertIsBool($actual);
    }

    /**
     * is_ajax returns false when the request header is absent.
     *
     * @return void
     */
    public function testIsAjaxReturnsFalseWhenRequestedWithHeaderIsMissing(): void
    {
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);

        $actual = is_ajax();

        $this->assertFalse($actual);
        $this->assertIsBool($actual);
    }

    /**
     * is_ajax rejects non-exact Ajax header values.
     *
     * @dataProvider nonAjaxRequestedWithProvider
     * @param mixed $value
     * @return void
     */
    public function testIsAjaxReturnsFalseForNonExactRequestedWithHeaders($value): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $value;

        $actual = is_ajax();

        $this->assertFalse($actual);
        $this->assertIsBool($actual);
    }

    /**
     * Non-Ajax request header values.
     *
     * @return array
     */
    public function nonAjaxRequestedWithProvider(): array
    {
        return [
            'lowercase ajax header' => ['xmlhttprequest'],
            'uppercase ajax header' => ['XMLHTTPREQUEST'],
            'fetch header' => ['fetch'],
            'empty string' => [''],
            'zero string' => ['0'],
            'null header value' => [null],
            'header with surrounding whitespace' => [' XMLHttpRequest '],
        ];
    }

    /**
     * is_ajax subprocess coverage exercises all reachable branches.
     *
     * @return void
     */
    public function testIsAjaxCoverageSubprocessCoversBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/pro-search-helper-is-ajax-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/pro_search_helper_is_ajax_coverage.php';
        $command = escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(__DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php'),
            $result['real_module_path']
        );
        $this->assertTrue($result['exact_header_result']);
        $this->assertFalse($result['missing_header_result']);
        $this->assertFalse($result['lowercase_header_result']);
        $this->assertFalse($result['empty_header_result']);
        $this->assertFalse($result['zero_string_header_result']);
        $this->assertFalse($result['null_header_result']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * pro_prep_word_list lowercases, filters duplicates, and sorts tokens.
     *
     * @return void
     */
    public function testPrepWordListNormalizesSortsAndDeduplicatesWords(): void
    {
        $input = "Beta beta\nAlpha, ALPHA can't!";
        $this->mockProMultibyteStrtolower($input);

        $this->assertSame("alpha beta can't", pro_prep_word_list($input));
    }

    /**
     * pro_prep_word_list preserves legacy punctuation cleanup rules.
     *
     * @return void
     */
    public function testPrepWordListRemovesPunctuationWithoutSplittingWords(): void
    {
        $input = "two-word email@example.com foo_bar O'Malley rock&roll";
        $this->mockProMultibyteStrtolower($input);

        $this->assertSame(
            "emailexamplecom foo_bar o'malley rockroll twoword",
            pro_prep_word_list($input)
        );
    }

    /**
     * pro_prep_word_list collapses whitespace-only input to an empty string.
     *
     * @return void
     */
    public function testPrepWordListReturnsEmptyStringForWhitespaceOnlyInput(): void
    {
        $input = " \n\t ";
        $this->mockProMultibyteStrtolower($input);

        $this->assertSame('', pro_prep_word_list($input));
    }

    /**
     * pro_prep_word_list handles its default empty input.
     *
     * @return void
     */
    public function testPrepWordListReturnsEmptyStringForDefaultInput(): void
    {
        $this->mockProMultibyteStrtolower('');

        $this->assertSame('', pro_prep_word_list());
    }

    /**
     * pro_prep_word_list normalizes the value returned by Pro_multibyte.
     *
     * @return void
     */
    public function testPrepWordListUsesReturnedMultibyteLowercaseValue(): void
    {
        $input = 'MiXeD Input';
        $this->mockProMultibyteStrtolower($input, 'gamma alpha gamma');

        $this->assertSame('alpha gamma', pro_prep_word_list($input));
    }

    /**
     * Mock Pro Search multibyte lowercasing for word-list helper tests.
     *
     * @param mixed $expectedInput
     * @param string|null $lowercaseResult
     * @return void
     */
    private function mockProMultibyteStrtolower($expectedInput, ?string $lowercaseResult = null): void
    {
        $multibyte = $this->getMockBuilder('stdClass')
            ->addMethods(['strtolower'])
            ->getMock();
        $multibyte->expects($this->once())
            ->method('strtolower')
            ->with($expectedInput)
            ->willReturn($lowercaseResult ?? mb_strtolower((string) $expectedInput));

        ee()->setMock('pro_multibyte', $multibyte);
    }

    /**
     * pro_chr decodes numeric and named character references.
     *
     * @dataProvider proChrCharacterReferenceProvider
     * @param mixed $value
     * @param string $expected
     * @return void
     */
    public function testProChrDecodesCharacterReferences($value, string $expected): void
    {
        $this->assertSame($expected, pro_chr($value));
    }

    /**
     * Character reference inputs for pro_chr.
     *
     * @return array
     */
    public function proChrCharacterReferenceProvider(): array
    {
        return [
            'decimal integer' => [65, 'A'],
            'decimal string' => ['65', 'A'],
            'numeric double quote with ENT_QUOTES' => [34, '"'],
            'numeric single quote with ENT_QUOTES' => [39, "'"],
            'named entity' => ['quot', '"'],
            'invalid named entity is preserved' => ['not-a-real-entity', '&not-a-real-entity;'],
            'invalid numeric code point is preserved' => [1114112, '&#1114112;'],
        ];
    }

    /**
     * pro_chr decodes UTF-8 code points without truncating bytes.
     *
     * @dataProvider proChrUtf8CodePointProvider
     * @param mixed $value
     * @param string $expectedHex
     * @return void
     */
    public function testProChrDecodesUtf8CodePoints($value, string $expectedHex): void
    {
        $actual = pro_chr($value);

        $this->assertSame($expectedHex, bin2hex($actual));
        $this->assertSame(strlen(hex2bin($expectedHex)), strlen($actual));
    }

    /**
     * UTF-8 code point inputs for pro_chr.
     *
     * @return array
     */
    public function proChrUtf8CodePointProvider(): array
    {
        return [
            'named copyright entity' => ['copy', 'c2a9'],
            'three-byte numeric code point' => [8364, 'e282ac'],
            'highest valid Unicode code point' => [1114111, 'f48fbfbf'],
        ];
    }

    /**
     * pro_hilite wraps literal needle matches in mark tags.
     *
     * @dataProvider hiliteMatchProvider
     * @param string $haystack
     * @param string $needle
     * @param string $expected
     * @return void
     */
    public function testHiliteWrapsLiteralNeedleMatches(string $haystack, string $needle, string $expected): void
    {
        $this->assertSame($expected, pro_hilite($haystack, $needle));
    }

    /**
     * Match inputs for pro_hilite.
     *
     * @return array
     */
    public function hiliteMatchProvider(): array
    {
        return [
            'multiple matches' => [
                'alpha beta alpha',
                'alpha',
                '<mark>alpha</mark> beta <mark>alpha</mark>',
            ],
            'regex metacharacters are literal' => [
                'Find a+b? before a+b?.',
                'a+b?',
                'Find <mark>a+b?</mark> before <mark>a+b?</mark>.',
            ],
            'regex delimiter is literal' => [
                'a#b#a#b',
                '#',
                'a<mark>#</mark>b<mark>#</mark>a<mark>#</mark>b',
            ],
            'case-sensitive matching' => [
                'Alpha alpha',
                'alpha',
                'Alpha <mark>alpha</mark>',
            ],
            'markup remains unescaped' => [
                '<p>alpha & beta</p>',
                'alpha & beta',
                '<p><mark>alpha & beta</mark></p>',
            ],
        ];
    }

    /**
     * pro_hilite returns the original haystack when no needle is matched.
     *
     * @return void
     */
    public function testHiliteReturnsOriginalHaystackWhenNeedleIsMissing(): void
    {
        $this->assertSame('alpha beta', pro_hilite('alpha beta', 'gamma'));
    }

    /**
     * pro_hilite preserves its legacy empty-needle boundary behavior.
     *
     * @return void
     */
    public function testHilitePreservesEmptyNeedleBoundaryBehavior(): void
    {
        $this->assertSame(
            '<mark></mark>a<mark></mark>b<mark></mark>c<mark></mark>',
            pro_hilite('abc', '')
        );
    }

    /**
     * pro_strpos_all returns every literal match offset.
     *
     * @dataProvider strposAllMatchProvider
     * @param string $haystack
     * @param string $needle
     * @param array $expected
     * @return void
     */
    public function testStrposAllReturnsEveryLiteralMatchOffset(string $haystack, string $needle, array $expected): void
    {
        $actual = pro_strpos_all($haystack, $needle);

        $this->assertSame($expected, $actual);

        foreach ($actual as $offset) {
            $this->assertIsInt($offset);
        }
    }

    /**
     * Match inputs for pro_strpos_all.
     *
     * @return array
     */
    public function strposAllMatchProvider(): array
    {
        return [
            'single match' => ['alpha beta', 'beta', [6]],
            'multiple matches' => ['alpha beta alpha', 'alpha', [0, 11]],
            'regex metacharacter literal' => ['a.b.a.b', '.', [1, 3, 5]],
            'regex delimiter literal' => ['a#b#a#b', '#', [1, 3, 5]],
            'first and last positions' => ['abxxab', 'ab', [0, 4]],
        ];
    }

    /**
     * pro_strpos_all returns an empty array when no needle is matched.
     *
     * @dataProvider strposAllNoMatchProvider
     * @param string|null $haystack
     * @param string $needle
     * @return void
     */
    public function testStrposAllReturnsEmptyArrayWhenNoNeedleIsMatched($haystack, string $needle): void
    {
        $this->assertSame([], pro_strpos_all($haystack, $needle));
    }

    /**
     * No-match inputs for pro_strpos_all.
     *
     * @return array
     */
    public function strposAllNoMatchProvider(): array
    {
        return [
            'null haystack' => [null, 'a'],
            'empty haystack' => ['', 'a'],
            'missing needle' => ['abc', 'z'],
            'case-sensitive mismatch' => ['Alpha', 'alpha'],
            'needle longer than haystack' => ['ab', 'abc'],
        ];
    }

    /**
     * pro_substr_pad returns snippets for each supplied offset.
     *
     * @dataProvider substrPadSnippetProvider
     * @param string $haystack
     * @param array $positions
     * @param int $length
     * @param int $pad
     * @param array $expected
     * @return void
     */
    public function testSubstrPadReturnsSnippetsForOffsets(
        string $haystack,
        array $positions,
        int $length,
        int $pad,
        array $expected
    ): void {
        $actual = pro_substr_pad($haystack, $positions, $length, $pad);

        $this->assertSame($expected, $actual);

        foreach ($actual as $snippet) {
            $this->assertIsString($snippet);
        }
    }

    /**
     * Snippet extraction inputs for pro_substr_pad.
     *
     * @return array
     */
    public function substrPadSnippetProvider(): array
    {
        return [
            'exact length without padding' => [
                'alpha beta gamma',
                [6],
                4,
                0,
                ['beta'],
            ],
            'left and right padding around match' => [
                'alpha beta gamma',
                [6],
                4,
                2,
                ['a beta g'],
            ],
            'left padding clamps to start' => [
                'alpha beta gamma',
                [1],
                3,
                5,
                ['alpha beta ga'],
            ],
            'right padding clamps to haystack end' => [
                'alpha beta gamma',
                [12],
                5,
                3,
                ['a gamma'],
            ],
            'zero length can return surrounding padding only' => [
                'alpha beta gamma',
                [6],
                0,
                2,
                ['a be'],
            ],
            'multiple offsets preserve supplied order' => [
                'alpha beta gamma beta',
                [6, 0],
                5,
                0,
                ['beta ', 'alpha'],
            ],
        ];
    }

    /**
     * pro_substr_pad returns an empty array when no offsets are supplied.
     *
     * @return void
     */
    public function testSubstrPadReturnsEmptyArrayWithoutOffsets(): void
    {
        $this->assertSame([], pro_substr_pad('alpha beta gamma', [], 4, 2));
    }

    /**
     * pro_substr_pad returns an empty array for legacy null offsets.
     *
     * @return void
     */
    public function testSubstrPadReturnsEmptyArrayForNullOffsets(): void
    {
        $this->assertSame([], @pro_substr_pad('alpha beta gamma', null, 4, 2));
    }
}
