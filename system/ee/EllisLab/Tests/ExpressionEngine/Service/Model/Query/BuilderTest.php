<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Test\ExpressionEngine\Service\Model\Query;

use Mockery as m;

use EllisLab\ExpressionEngine\Service\Model\Query\Builder;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testFields()
	{
		$builder = new Builder('Test');

		$this->assertEquals(array(), $builder->getFields());

		$builder->fields('foo', 'bar');
		$builder->fields('baz', 'bat');

		$this->assertEquals(
			array('foo', 'bar', 'baz', 'bat'),
			$builder->getFields()
		);
	}

	public function testLimitAndOffset()
	{
		$builder = new Builder('Test');

		$this->assertEquals('18446744073709551615', $builder->getLimit());
		$this->assertEquals(0, $builder->getOffset());

		$builder->limit(5);
		$builder->offset(10);

		$this->assertEquals(5, $builder->getLimit());
		$this->assertEquals(10, $builder->getOffset());
	}

	public function testSet()
	{
		$builder = new Builder('Test');

		$this->assertEquals(array(), $builder->getSet());

		$builder->set('name', 'Bob');
		$builder->set(array('age' => 5, 'location' => 'Boston'));

		$this->assertEquals(
			array('name' => 'Bob', 'age' => 5, 'location' => 'Boston'),
			$builder->getSet()
		);
	}

	public function testFilters()
	{
		$builder = new Builder('Test');

		$this->assertEquals(array(), $builder->getFilters());

		$builder->filter('name', 'Bob');
		$builder->orFilter('age', '>', 5);

		$this->assertEquals(
			array(
				array('name', '==', 'Bob', 'and'),
				array('age', '>', 5, 'or')
			),
			$builder->getFilters()
		);
	}

	public function testFilterGroups()
	{
		$builder = new Builder('Test');

		$builder
			->filterGroup()
				->filter('name', 'Bob')
				->orFilter('name', 'Wendy')
			->endFilterGroup()
			->orFilterGroup()
				->filter('name', 'Farmer Pickles')
				->filter('companion', 'Spud')
			->endFilterGroup();

		$this->assertEquals(
			array(
				array('and', array(
					array('name', '==', 'Bob', 'and'),
					array('name', '==', 'Wendy', 'or')
				)),
				array('or', array(
					array('name', '==', 'Farmer Pickles', 'and'),
					array('companion', '==', 'Spud', 'and')
				))
			),
			$builder->getFilters()
		);
	}

	public function testWithsOneLeveL()
	{
		$builder = new Builder('Test');

		$this->assertEquals(array(), $builder->getWiths());

		$builder->with('one', 'two');
		$builder->with(array('four', 'five'));

		$this->assertEquals(
			array(
				'one' => array(),
				'two' => array(),
				'four' => array(),
				'five' => array()
			),
			$builder->getWiths()
		);
	}

	public function testWithsGrandkids()
	{
		$builder = new Builder('Test');

		$builder->with(array('one' => 'cow', 'two', 'three' => array('dog', 'cat')));

		$this->assertEquals(
			array(
				'one' => array(
					'cow' => array()
				),
				'two' => array(),
				'three' => array(
					'dog' => array(),
					'cat' => array()
				)
			),
			$builder->getWiths()
		);
	}

	public function testWithsMergeDescendants()
	{
		$builder = new Builder('Test');

		$builder->with('one');
		$builder->with(array('one' => 'cow'));
		$builder->with(array('two' => 'dog'));
		$builder->with('one', 'two');
		$builder->with(array('one' => array('cat' => 'meow')));

		$this->assertEquals(
			array(
				'one' => array('cow' => array(), 'cat' => array('meow' => array())),
				'two' => array('dog' => array()),
			),
			$builder->getWiths()
		);
	}

	public function testSearch()
	{
		$builder = new Builder('Test');

		$builder->search('words', 'hello world');
		$builder->search('wordnotword', 'hello -world');
		$builder->search('phrase', '"hello world"');
		$builder->search('phraseword', '"hello world" people');
		$builder->search('notphraseword', '-"hello world" people');
		$builder->search('apostrophe', "hello world's people");

		$this->assertEquals(
			array(
				'words' => array('hello' => TRUE, 'world' => TRUE),
				'wordnotword' => array('hello' => TRUE, 'world' => FALSE),
				'phrase' => array('hello world' => TRUE),
				'phraseword' => array('hello world' => TRUE, 'people' => TRUE),
				'notphraseword' => array('hello world' => FALSE, 'people' => TRUE),
				'apostrophe' => array('hello' => TRUE, "world's" => TRUE, 'people' => TRUE),
			),
			$builder->getSearch()
		);
	}
}
