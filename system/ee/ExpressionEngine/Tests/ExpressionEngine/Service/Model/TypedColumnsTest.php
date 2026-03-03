<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model;

use Mockery as m;
use ExpressionEngine\Service\Model\Model;
use PHPUnit\Framework\TestCase;

class TypedColumnsTest extends TestCase
{
    public $obj;

    public function setUp(): void
    {
        $class = __NAMESPACE__ . '\TypedColumnsStub';
        $this->obj = new $class();
    }

    public function tearDown(): void
    {
        $this->obj = null;
    }

    public function testInt()
    {
        $obj = $this->obj;

        $this->assertEquals(0, $obj->integer, 'default value');

        $obj->fill(array('integer' => 5));
        $this->assertEquals(5, $obj->integer);

        $obj->fill(array('integer' => '7'));
        $this->assertEquals(7, $obj->integer);

        $obj->fill(array('integer' => 'nonsense'));
        $this->assertEquals(0, $obj->integer);

        $obj->fill(array('integer' => 5));
        $this->assertEquals(5, $obj->integer);

        $obj->integer = '7';
        $this->assertSame(7, $obj->integer);
        $this->assertSame(array('integer' => 7), $obj->getDirty(), 'storage value');
        $this->assertSame(array('integer' => '7'), $obj->getModified(), 'validation value');

        $obj->integer = 'bogus';
        $this->assertSame(0, $obj->integer);
        $this->assertSame(array('integer' => 0), $obj->getDirty(), 'storage value');
        $this->assertSame(array('integer' => 'bogus'), $obj->getModified(), 'validation value');
    }

    public function testYesNo()
    {
        $obj = $this->obj;

        $this->assertEquals(false, $obj->yesno, 'default value');

        $obj->fill(array('yesno' => 'y'));
        $this->assertTrue($obj->yesno);

        $obj->fill(array('yesno' => 'n'));
        $this->assertFalse($obj->yesno);

        $obj->fill(array('yesno' => true));
        $this->assertTrue($obj->yesno);

        $obj->fill(array('yesno' => false));
        $this->assertFalse($obj->yesno);

        $obj->fill(array('yesno' => 1));
        $this->assertTrue($obj->yesno);

        $obj->fill(array('yesno' => 'nonsense'));
        $this->assertFalse($obj->yesno);

        $obj->fill(array('yesno' => 'y'));
        $obj->yesno = 'n';
        $this->assertFalse($obj->yesno);
        $this->assertSame(array('yesno' => 'n'), $obj->getDirty(), 'storage value');
        $this->assertSame(array('yesno' => 'n'), $obj->getModified(), 'validation value');

        $obj->yesno = true;
        $this->assertTrue($obj->yesno);
        $this->assertSame(array('yesno' => 'y'), $obj->getDirty(), 'storage value');
        $this->assertSame(array('yesno' => true), $obj->getModified(), 'validation value');

        $obj->yesno = 'bogus';
        $this->assertFalse($obj->yesno);
        $this->assertSame(array('yesno' => 'n'), $obj->getDirty(), 'storage value');
        $this->assertSame(array('yesno' => 'bogus'), $obj->getModified(), 'validation value');
    }

    public function testBoolean()
    {
        $obj = $this->obj;

        $this->assertFalse($obj->boolean, 'default value');

        $obj->fill(array('boolean' => 1));
        $this->assertTrue($obj->boolean);

        $obj->fill(array('boolean' => 0));
        $this->assertFalse($obj->boolean);

        $obj->fill(array('boolean' => array('not-scalar')));
        $this->assertFalse($obj->boolean);

        $obj->fill(array('boolean' => true));
        $obj->boolean = '1';
        $this->assertTrue($obj->boolean);
        $this->assertSame(array('boolean' => true), $obj->getDirty(), 'storage value');
        $this->assertSame(array('boolean' => '1'), $obj->getModified(), 'validation value');

        $obj->boolean = array('bogus');
        $this->assertFalse($obj->boolean);
        $this->assertSame(array('boolean' => false), $obj->getDirty(), 'storage value');
        $this->assertSame(array('boolean' => array('bogus')), $obj->getModified(), 'validation value');
    }

    public function testFloat()
    {
        $obj = $this->obj;

        $this->assertSame(0.0, $obj->float, 'default value');

        $obj->fill(array('float' => 7.25));
        $this->assertSame(7.25, $obj->float);

        $obj->fill(array('float' => '2.5'));
        $this->assertSame(2.5, $obj->float);

        $obj->fill(array('float' => 'nonsense'));
        $this->assertSame(0.0, $obj->float);

        $obj->fill(array('float' => array('not-scalar')));
        $this->assertSame(0.0, $obj->float);

        $obj->fill(array('float' => 7.25));
        $obj->float = '3.75';
        $this->assertSame(3.75, $obj->float);
        $this->assertSame(array('float' => 3.75), $obj->getDirty(), 'storage value');
        $this->assertSame(array('float' => '3.75'), $obj->getModified(), 'validation value');

        $obj->float = array('bogus');
        $this->assertSame(0.0, $obj->float);
        $this->assertSame(array('float' => 0.0), $obj->getDirty(), 'storage value');
        $this->assertSame(array('float' => array('bogus')), $obj->getModified(), 'validation value');
    }

    public function testString()
    {
        $obj = $this->obj;

        $this->assertSame('', $obj->string, 'default value');

        $obj->fill(array('string' => 123));
        $this->assertSame('123', $obj->string);

        $obj->fill(array('string' => false));
        $this->assertSame('', $obj->string);

        $obj->fill(array('string' => array('not-scalar')));
        $this->assertSame('', $obj->string);

        $obj->fill(array('string' => 'foo'));
        $obj->string = 456;
        $this->assertSame('456', $obj->string);
        $this->assertSame(array('string' => '456'), $obj->getDirty(), 'storage value');
        $this->assertSame(array('string' => 456), $obj->getModified(), 'validation value');

        $obj->string = array('bogus');
        $this->assertSame('', $obj->string);
        $this->assertSame(array('string' => ''), $obj->getDirty(), 'storage value');
        $this->assertSame(array('string' => array('bogus')), $obj->getModified(), 'validation value');
    }

    public function testJsonSerializedType()
    {
        $obj = $this->obj;

        $this->assertSame(array(), $obj->json, 'default value');

        $encoded = json_encode(array('name' => 'bob', 'age' => 35));
        $obj->fill(array('json' => $encoded));
        $this->assertSame(array('name' => 'bob', 'age' => 35), $obj->json);

        $obj->fill(array('json' => ''));
        $this->assertSame(array(), $obj->json);

        $obj->fill(array('json' => null));
        $this->assertSame(array(), $obj->json);

        $obj->fill(array('json' => '{'));
        $this->assertNull($obj->json, 'invalid json is returned as null');

        $payload = array('name' => 'mary', 'age' => 28);
        $obj->json = $payload;
        $this->assertSame($payload, $obj->json);
        $this->assertSame(array('json' => json_encode($payload)), $obj->getDirty(), 'storage value');
        $this->assertSame(array('json' => $payload), $obj->getModified(), 'validation value');
    }

    public function testSerialized()
    {
        $obj = $this->obj;

        $bob = array('name' => 'bob');
        $mary = array('name' => 'mary', 'age' => 35);

        $bob_data = serialize($bob);
        $mary_data = serialize($mary);

        $this->assertSame(array(), $obj->native, 'default value');

        $obj->fill(array('native' => $bob_data));
        $this->assertEquals($bob, $obj->native);

        $this->assertSame(array(), $obj->getDirty(), 'storage value');
        $this->assertSame(array(), $obj->getModified(), 'validation value');

        $obj->native = $mary;
        $this->assertEquals($mary, $obj->native);

        $this->assertSame(array('native' => $mary_data), $obj->getDirty(), 'storage value');
        $this->assertSame(array('native' => $mary), $obj->getModified(), 'validation value');
    }

    public function testAdditionalSerializedTypes()
    {
        $obj = $this->obj;

        $this->assertSame('', $obj->base64, 'base64 default value');
        $this->assertSame(array(), $obj->base64serialized, 'base64 serialized default value');
        $this->assertSame(array(), $obj->comma, 'comma-delimited default value');
        $this->assertSame(array(), $obj->pipe, 'pipe-delimited default value');

        $obj->fill(array('base64' => base64_encode('hello')));
        $this->assertSame('hello', $obj->base64);

        $obj->fill(array('base64' => ''));
        $this->assertSame('', $obj->base64);

        $payload = array('name' => 'bob', 'age' => 35);
        $obj->fill(array('base64serialized' => base64_encode(serialize($payload))));
        $this->assertSame($payload, $obj->base64serialized);

        $obj->fill(array('base64serialized' => null));
        $this->assertSame(array(), $obj->base64serialized);

        $obj->fill(array('comma' => 'red,green,,blue'));
        $this->assertSame(array(0 => 'red', 1 => 'green', 3 => 'blue'), $obj->comma);

        $obj->fill(array('comma' => null));
        $this->assertSame(array(), $obj->comma);

        $obj->fill(array('pipe' => 'left|middle||right'));
        $this->assertSame(array(0 => 'left', 1 => 'middle', 3 => 'right'), $obj->pipe);

        $obj->fill(array('pipe' => null));
        $this->assertSame(array(), $obj->pipe);

        $obj->base64 = 'world';
        $this->assertSame(base64_encode('world'), $obj->getDirty()['base64'], 'base64 storage value');
        $this->assertSame('world', $obj->getModified()['base64'], 'base64 validation value');

        $new_payload = array('team' => 'ee');
        $obj->base64serialized = $new_payload;
        $this->assertSame(base64_encode(serialize($new_payload)), $obj->getDirty()['base64serialized'], 'base64 serialized storage value');
        $this->assertSame($new_payload, $obj->getModified()['base64serialized'], 'base64 serialized validation value');

        $obj->comma = array('a', '', 'c');
        $this->assertSame('a,,c', $obj->getDirty()['comma'], 'comma-delimited storage value');
        $this->assertSame(array('a', '', 'c'), $obj->getModified()['comma'], 'comma-delimited validation value');

        $obj->pipe = array('x', '', 'z');
        $this->assertSame('x||z', $obj->getDirty()['pipe'], 'pipe-delimited storage value');
        $this->assertSame(array('x', '', 'z'), $obj->getModified()['pipe'], 'pipe-delimited validation value');
    }

    public function testStaticTypeDefaults()
    {
        $first = StaticTypeStub::create();
        $second = StaticTypeStub::create();
        $other = StaticTypeOtherStub::create();

        $this->assertSame($first, $second, 'create should return singleton per class');
        $this->assertNotSame($first, $other, 'singletons should be isolated per subclass');

        $this->assertSame('db-value', StaticTypeStub::load('db-value'));
        $this->assertSame('store-value', StaticTypeStub::store('store-value'));
        $this->assertSame('get-value', StaticTypeStub::get('get-value'));
        $this->assertSame('set-value', StaticTypeStub::set('set-value'));
    }

    public function testCustomTypeMethods()
    {
        $type = CustomTypeStub::create();

        $this->assertInstanceOf(CustomTypeStub::class, $type);

        $loaded = $type->load('hello');
        $this->assertSame('hello', $loaded);
        $this->assertSame('hello', $type->alpha);
        $this->assertSame(5, $type->beta);

        $this->assertSame('hello|5', $type->store(array('ignored' => true)));

        $set_data = array('alpha' => 'next', 'beta' => 4);
        $this->assertSame($set_data, $type->set($set_data));
        $this->assertSame('hello', $type->alpha, 'set() does not mutate custom type fields directly');
        $this->assertSame(5, $type->beta, 'set() does not mutate custom type fields directly');

        $this->assertSame($type, $type->get());
    }
}

class TypedColumnsStub extends Model
{
    public static $_typed_columns = array(
        'boolean' => 'boolean',
        'float' => 'float',
        'integer' => 'int',
        'string' => 'string',
        'yesno' => 'yesNo',
        'base64' => 'base64',
        'base64serialized' => 'base64Serialized',
        'comma' => 'commaDelimited',
        'pipe' => 'pipeDelimited',
        'json' => 'json',
        'native' => 'serialized'
    );

    protected $boolean;
    protected $float;
    protected $integer;
    protected $string;
    protected $yesno;
    protected $base64;
    protected $base64serialized;
    protected $comma;
    protected $pipe;

    protected $json;
    protected $native;
}

class StaticTypeStub extends \ExpressionEngine\Service\Model\Column\StaticType
{
}

class StaticTypeOtherStub extends \ExpressionEngine\Service\Model\Column\StaticType
{
}

class CustomTypeStub extends \ExpressionEngine\Service\Model\Column\CustomType
{
    public $alpha;
    public $beta;

    public function unserialize($db_data)
    {
        return array(
            'alpha' => (string) $db_data,
            'beta' => strlen((string) $db_data),
        );
    }

    public function serialize($data)
    {
        return $data['alpha'] . '|' . $data['beta'];
    }
}

// EOF
