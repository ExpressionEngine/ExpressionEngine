<?php

require_once PATH_ADDONS . 'pro_variables/types/type.pro_variables.php';
require_once PATH_ADDONS . 'pro_variables/types/pro_table/vt.pro_table.php';

use PHPUnit\Framework\TestCase;

class ProTableDataTest extends TestCase
{
    public function testSavedRowsUseTheCurrentDataFormat(): void
    {
        $rows = array(array('first', 'second'));
        $saved = $this->makeType()->save($rows);

        $this->assertMatchesRegularExpression('/^<!--[A-Za-z0-9+\/=]+-->$/', $saved);
        $payload = json_decode(base64_decode(substr($saved, 4, -3)), true);

        $this->assertSame($rows, $payload);
        $this->assertSame($rows, $this->decode(substr($saved, 4, -3)));
    }

    public function testEncodingFailureReturnsValidationError(): void
    {
        $type = $this->makeType();

        $this->assertFalse($type->save(array(array("\xB1\x31"))));
        $this->assertSame('invalid_value', $type->error_msg);
    }

    public function testLegacyRowsRemainReadable(): void
    {
        $rows = array(array('first', 'second'));

        $this->assertSame($rows, $this->decode(base64_encode(serialize($rows))));
    }

    public function testUnsupportedLegacyValuesAreRejected(): void
    {
        $rows = array(array('first', new stdClass()));

        $this->assertSame(array(), $this->decode(base64_encode(serialize($rows))));
    }

    public function testMalformedDataIsRejected(): void
    {
        $this->assertSame(array(), $this->decode('not-encoded-data'));
    }

    /**
     * Make an initialized Pro Table type.
     *
     * @return Pro_table
     */
    private function makeType()
    {
        $type = new Pro_table();
        $type->init(array(
            'variable_id' => 1,
            'variable_name' => 'example_table',
            'variable_data' => '',
            'variable_settings' => '{}',
        ));

        return $type;
    }

    /**
     * Decode a Pro Table payload.
     *
     * @param mixed $value
     * @return array
     */
    private function decode($value)
    {
        $method = new ReflectionMethod(Pro_table::class, 'decode');
        TestReflectionHelper::makeAccessible($method);

        return $method->invoke($this->makeType(), $value);
    }
}
