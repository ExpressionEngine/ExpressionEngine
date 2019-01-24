<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\Data;

use EllisLab\ExpressionEngine\Library\Data\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase {

	public function testWorksAsArray()
	{
		$c = new Collection();
		$c[] = 'hello';
		$c[] = 'world';

		$out = array();

		foreach ($c as $item)
		{
			$out[] = $item;
		}

		$this->assertEquals(
			array('hello', 'world'),
			$out
		);
	}

	public function testFirst()
	{
		$c = new Collection();
		$this->assertNull($c->first());

		$c[] = 'hello';
		$c[] = 'world';
		$this->assertEquals('hello', $c->first());
	}

	public function testLast()
	{
		$c = new Collection();
		$this->assertNull($c->last());

		$c[] = 'hello';
		$c[] = 'world';
		$this->assertEquals('world', $c->last());
	}

	public function testReverse()
	{
		$c1 = new Collection();

		$c1[] = 'hello';
		$c1[] = 'beautiful';
		$c1[] = 'world';

		$this->assertEquals('hello', $c1->first());
		$this->assertEquals('world', $c1->last());

		$c2 = $c1->reverse();

		$this->assertEquals('hello', $c2->last());
		$this->assertEquals('world', $c2->first());

		$this->assertNotSame($c1, $c2);
	}

	/**
	 * @dataProvider arrayAndObjectProvider
	 */
	public function testAsArray($data)
	{
		$c = new Collection($data);
		$this->assertEquals($data, $c->asArray());
	}

	/**
	 * @dataProvider arrayAndObjectProvider
	 */
	public function testPluck($data)
	{
		$c = new Collection($data);

		$this->assertEquals(
			array(5, 18),
			$c->pluck('key')
		);
	}

	/**
	 * @dataProvider arrayAndObjectProvider
	 */
	public function testCollectPluck($data)
	{
		$c = new Collection($data);

		$this->assertEquals(
			array('Bob', 'Sarah'),
			$c->collect('name')
		);
	}

	/**
	 * @dataProvider arrayAndObjectProvider
	 */
	public function testCollectCallback($data)
	{
		$c = new Collection($data);

		$out = $c->collect(function($item)
		{
			return is_array($item) ? $item['location'] : $item->location;
		});

		$this->assertEquals(
			array('Norway', 'Iceland'),
			$out
		);
	}

	public function testIndexBy()
	{
		$c = new Collection(array(
			array('one' => 'yes', 'key' => 'positive'),
			array('two' => 'no', 'key' => 'negative')
		));

		$this->assertEquals(
			array(
				'positive' => array('one' => 'yes', 'key' => 'positive'),
				'negative' => array('two' => 'no', 'key' => 'negative')
			),
			$out = $c->indexBy('key')
		);
	}

	public function testMap()
	{
		$c = new Collection(array(
			array('one' => 'yes', 'key' => 'positive'),
			array('two' => 'no', 'key' => 'negative')
		));

		$out = $c->map(function($item)
		{
			return $item['key'].'!';
		});

		$this->assertEquals(
			array('positive!', 'negative!'),
			$out
		);
	}

	public function testFilter()
	{
		$c1 = new Collection(array(
			'hello',
			'bonjour',
			'guten tag',
			'bye',
			'tschüß',
			'au revoir'
		));

		$c2 = $c1->filter(function($item)
		{
			return ($item == 'hello' || $item == 'bye');
		});

		$this->assertEquals(
			array('hello', 'bye'),
			$c2->asArray()
		);

		$this->assertNotSame($c1, $c2);
	}

	/**
	 * @dataProvider arrayAndObjectProvider
	 */
	public function testGetDictionary($data)
	{
		$c = new Collection($data);

		$this->assertEquals(
			array(
				'Bob' => 'Norway',
				'Sarah' => 'Iceland'
			),
			$c->getDictionary('name', 'location')
		);
	}


	public function arrayAndObjectProvider()
	{
		return array(
			array($this->severalArrays(), 'arrays'),
			array($this->severalObjects(), 'objects')
		);
	}

	protected function severalArrays()
	{
		return array(
			array(
				'key' => 5,
				'name' => 'Bob',
				'last' => 'Bobson',
				'location' => 'Norway'
			),
			array(
				'key' => 18,
				'name' => 'Sarah',
				'last' => 'Sarahsdottir',
				'location' => 'Iceland'
			)
		);
	}

	protected function severalObjects()
	{
		return array_map(
			function($arr) { return (object) $arr; },
			$this->severalArrays()
		);
	}

}

// EOF
