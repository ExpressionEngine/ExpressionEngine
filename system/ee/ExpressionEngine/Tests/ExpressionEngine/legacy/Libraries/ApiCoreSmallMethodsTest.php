<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/libraries/Api.php';

use PHPUnit\Framework\TestCase;

class ApiCoreSmallMethodsShim extends \Api
{
    public function callInitialize(array $params = []): void
    {
        $this->initialize($params);
    }

    public function callUniqueUrlTitle(string $urlTitle, $selfId, $typeId = '', string $type = 'channel')
    {
        return $this->_unique_url_title($urlTitle, $selfId, $typeId, $type);
    }
}

class ApiUniqueUrlTitleDbMock
{
    public $whereCalls = [];
    public $countCalls = [];
    public $getCalls = [];

    private $countQueue;
    private $suffixQueue;

    public function __construct(array $countQueue = [], array $suffixQueue = [])
    {
        $this->countQueue = $countQueue;
        $this->suffixQueue = $suffixQueue;
    }

    public function where($field = null, $value = null)
    {
        if (is_array($field)) {
            $this->whereCalls[] = $field;
        } else {
            $this->whereCalls[] = [$field => $value];
        }

        return $this;
    }

    public function count_all_results($table = '')
    {
        $this->countCalls[] = $table;

        if ($this->countQueue === []) {
            return 0;
        }

        return array_shift($this->countQueue);
    }

    public function select($fields, $escape = true)
    {
        return $this;
    }

    public function escape_str($value)
    {
        return $value;
    }

    public function order_by($field, $direction = '')
    {
        return $this;
    }

    public function limit($limit)
    {
        return $this;
    }

    public function get($table = '')
    {
        $this->getCalls[] = $table;
        $suffix = $this->suffixQueue === [] ? null : array_shift($this->suffixQueue);

        return new class($suffix) {
            private $suffix;

            public function __construct($suffix)
            {
                $this->suffix = $suffix;
            }

            public function row($field)
            {
                return $this->suffix;
            }
        };
    }
}

class ApiCoreSmallMethodsTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testErrorCountReturnsCurrentErrorArraySize()
    {
        $api = new ApiCoreSmallMethodsShim();

        $this->assertSame(0, $api->error_count());

        $api->errors = ['a', 'b', 'c'];
        $this->assertSame(3, $api->error_count());
    }

    public function testSetErrorUsesTranslatedMessageWhenAvailable()
    {
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key === 'invalid_email' ? 'Translated invalid email' : '';
            }
        });

        $api = new ApiCoreSmallMethodsShim();
        $api->_set_error('invalid_email');

        $this->assertSame(['Translated invalid email'], $api->errors);
    }

    public function testSetErrorFallsBackToHumanizedErrorKey()
    {
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return '';
            }
        });

        $api = new ApiCoreSmallMethodsShim();
        $api->_set_error('invalid_member_group');

        $this->assertSame(['Invalid member group'], $api->errors);
    }

    public function testMakeUrlSafeStripsTrailingInvalidCharactersOnly()
    {
        $api = new ApiCoreSmallMethodsShim();

        $this->assertSame('abc_def-123', $api->make_url_safe('abc_def-123!!!'));
        $this->assertSame('foo bar', $api->make_url_safe('foo bar'));
    }

    public function testInitializeResetsErrorsAndIgnoresNumericKeys()
    {
        $api = new ApiCoreSmallMethodsShim();
        $api->errors = ['old'];
        $api->callInitialize(['site_id' => 7, 0 => 'skip-me']);

        $this->assertSame([], $api->errors);
        $this->assertSame(7, $api->site_id);
        $this->assertFalse(property_exists($api, '0'));
    }

    public function testInstantiateLoadsOnlyKnownApisFromStringAndArrayInput()
    {
        $loader = new class {
            public $calls = [];

            public function library($name)
            {
                $this->calls[] = $name;
            }
        };
        ee()->setMock('load', $loader);

        $api = new ApiCoreSmallMethodsShim();
        $api->instantiate('members');
        $api->instantiate(['unknown_api', 'channel_fields']);

        $this->assertSame(['api/api_members', 'api/api_channel_fields'], $loader->calls);
    }

    public function testIsUrlSafeReturnsTrueOnlyForAllowedCharacters()
    {
        $api = new ApiCoreSmallMethodsShim();

        $this->assertTrue($api->is_url_safe('alpha-Num_123.test'));
        $this->assertFalse($api->is_url_safe('contains space'));
    }

    public function testUniqueUrlTitleReturnsFalseWhenTypeIdIsMissing()
    {
        $api = new ApiCoreSmallMethodsShim();

        $this->assertFalse($api->callUniqueUrlTitle('slug', '', ''));
    }

    public function testUniqueUrlTitleCategoryTypeUsesCategoryFieldsAndTrimsToSeventyFiveCharacters()
    {
        $db = new ApiUniqueUrlTitleDbMock([0]);
        ee()->setMock('db', $db);

        $api = new ApiCoreSmallMethodsShim();
        $result = $api->callUniqueUrlTitle(str_repeat('a', 80), 15, 7, 'category');

        $this->assertSame(str_repeat('a', 75), $result);
        $this->assertContains(['category_id !=' => 15], $db->whereCalls);
        $this->assertContains(['cat_url_title' => str_repeat('a', 75), 'group_id' => 7], $db->whereCalls);
        $this->assertSame(['categories'], $db->countCalls);
    }

    public function testUniqueUrlTitleShortensFurtherWhenSuffixWouldExceedLimitThenReturnsAvailableSuffix()
    {
        $db = new ApiUniqueUrlTitleDbMock([1, 1, 0], [999999, 2]);
        ee()->setMock('db', $db);

        $api = new ApiCoreSmallMethodsShim();
        $result = $api->callUniqueUrlTitle(str_repeat('b', 76), 11, 5, 'channel');

        $this->assertSame(str_repeat('b', 69) . '2', $result);
        $this->assertSame(['channel_titles', 'channel_titles', 'channel_titles'], $db->countCalls);
        $this->assertSame(['channel_titles', 'channel_titles'], $db->getCalls);
        $this->assertContains(['entry_id !=' => 11], $db->whereCalls);
    }

    public function testUniqueUrlTitleReturnsFalseWhenGeneratedSuffixStillConflicts()
    {
        $db = new ApiUniqueUrlTitleDbMock([1, 1], [1]);
        ee()->setMock('db', $db);

        $api = new ApiCoreSmallMethodsShim();

        $this->assertFalse($api->callUniqueUrlTitle('slug', 42, 9, 'channel'));
        $this->assertContains(['entry_id !=' => 42], $db->whereCalls);
    }

    public function testUniqueUrlTitleFallsBackToSuffixOneWhenQueryReturnsArrayNextSuffix()
    {
        $db = new ApiUniqueUrlTitleDbMock([1, 0], [[]]);
        ee()->setMock('db', $db);

        $api = new ApiCoreSmallMethodsShim();

        $this->assertSame('slug1', $api->callUniqueUrlTitle('slug', '', 9, 'channel'));
    }
}
