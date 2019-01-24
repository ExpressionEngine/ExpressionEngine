<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\CP;

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase {

	public function testGreen()
	{
		$this->assertTrue(TRUE);
	}

	/**
	 * Test the constructor for things that should fail
	 *
	 * @dataProvider badConstructorDataProvider
	 * @expectedException \InvalidArgumentException
	 */
	// public function testConstructor($description, $per_page, $total_count, $current_page)
	// {
	// 	new Pagination($per_page, $total_count, $current_page);
	// }

	public function badConstructorDataProvider()
	{
		$obj = new \stdClass;

		return array(
			array("Array: per_page",		array(1), 1, 1),
			array("Array: total_count",		1, array(1), 1),
			array("Array: current_page",	1, 1, array(1)),

			array("String: per_page",		"foo", 1, 1),
			array("String: total_count",	1, "foo", 1),
			array("String: current_page",	1, 1, "foo"),

			array("Boolean: per_page",		FALSE, 1, 1),
			array("Boolean: total_count",	1, FALSE, 1),
			array("Boolean: current_page",	1, 1, FALSE),

			array("Object: per_page",		$obj, 1, 1),
			array("Object: total_count",	1, $obj, 1),
			array("Object: current_page",	1, 1, $obj),

			array("Zero per_page",			0, 1, 1),
			array("Zero current_page",		1, 1, 0),

			array("Negative per_page",		-1, 1, 1),
			array("Negative total_count",	1, -1, 1),
			array("Negative current_page",	1, 1, -1),
		);
	}

	/**
	 * Test the cp_links method for things that should fail
	 *
	 * @dataProvider badCpLinksDataProvider
	 * @expectedException \InvalidArgumentException
	 */
	// public function testBadCpLinks($description, $base_url, $pages, $page_variable)
	// {
	// 	$pagination = new Pagination(10, 100, 1);
	// 	$pagination->cp_links($base_url, $pages, $page_variable);
	// }

	public function badCpLinksDataProvider()
	{
		$url = new \EllisLab\ExpressionEngine\Library\CP\URL('foo/bar');
		$obj = new \stdClass;

		return array(
			array('Array for $pages',		$url, array(1),	'page'),
			array('String for $pages',		$url, "foo",	'page'),
			array('Boolean for $pages',		$url, FALSE,	'page'),
			array('Object for $pages',		$url, $obj,		'page'),
			array('Zero for $pages',		$url, 0,		'page'),
			array('Negative for $pages',	$url, -1,		'page'),

			array('Array for $page_variable',	$url, 1, array('page')),
			array('Object for $page_variable',	$url, 1, $obj),
		);
	}

	/**
	 * Test that pagination for 0 or 1 pages will return an empty array()
	 */
	// public function testNothingToPaginate()
	// {
	// 	$url = new \EllisLab\ExpressionEngine\Library\CP\URL('foo/bar');
	// }

	/**
	 * Test the cp_links() method
	 *
	 * @dataProvider cpLinksDataProvider
	 */
	// public function testCpLinks($description, $per_page, $total_count, $current_page, $url, $pages, $page_variable, $expected)
	// {
	// 	// First with the constructor
	// 	$pagination = new Pagination($per_page, $total_count, $current_page);
	// 	$links = $pagination->cp_links($url, $pages, $page_variable);
	// 	$this->assertEquals($expected, $links, $description);
	// }

	public function cpLinksDataProvider()
	{
		$url = new \EllisLab\ExpressionEngine\Library\CP\URL('foo/bar');

		$return = array();

		// When the current page is the first page there should be no "prev" link
		$expected = array(
			'total_count' => 100,
			'current_page' => 1,
			'first' => 'index.php?/cp/foo/bar',
			'next' => 'index.php?/cp/foo/bar&page=2',
			'last' => 'index.php?/cp/foo/bar&page=10',
			'pages' => array(
				'1' => 'index.php?/cp/foo/bar&page=1',
				'2' => 'index.php?/cp/foo/bar&page=2',
				'3' => 'index.php?/cp/foo/bar&page=3',
			)
		);

		$return[] = array('No "Prev" Link', 10, 100, 1, $url, 3, 'page', $expected);

		// When the current page is the last page there should be no "next" link
		$expected = array(
			'total_count' => 100,
			'current_page' => 10,
			'first' => 'index.php?/cp/foo/bar',
			'prev' => 'index.php?/cp/foo/bar&page=9',
			'last' => 'index.php?/cp/foo/bar&page=10',
			'pages' => array(
				'8' => 'index.php?/cp/foo/bar&page=8',
				'9' => 'index.php?/cp/foo/bar&page=9',
				'10' => 'index.php?/cp/foo/bar&page=10',
			)
		);
		$return[] = array('No "Next" Link', 10, 100, 10, $url, 3, 'page', $expected);

		// Test the 'page' variable
		// Also testing that current_page 2 still starts the pages array at 1
		$expected = array(
			'total_count' => 100,
			'current_page' => 2,
			'first' => 'index.php?/cp/foo/bar',
			'prev' => 'index.php?/cp/foo/bar&p=1',
			'next' => 'index.php?/cp/foo/bar&p=3',
			'last' => 'index.php?/cp/foo/bar&p=10',
			'pages' => array(
				'1' => 'index.php?/cp/foo/bar&p=1',
				'2' => 'index.php?/cp/foo/bar&p=2',
				'3' => 'index.php?/cp/foo/bar&p=3',
			)
		);

		$return[] = array('"p" for the page_variable', 10, 100, 2, $url, 3, 'p', $expected);

		// Testing a small $pages
		$expected = array(
			'total_count' => 100,
			'current_page' => 2,
			'first' => 'index.php?/cp/foo/bar',
			'prev' => 'index.php?/cp/foo/bar&p=1',
			'next' => 'index.php?/cp/foo/bar&p=3',
			'last' => 'index.php?/cp/foo/bar&p=10',
			'pages' => array(
				'1' => 'index.php?/cp/foo/bar&p=1',
				'2' => 'index.php?/cp/foo/bar&p=2',
			)
		);

		$return[] = array('"p" for the page_variable', 10, 100, 2, $url, 2, 'p', $expected);

		// Testing a large $pages
		$expected = array(
			'total_count' => 100,
			'current_page' => 2,
			'first' => 'index.php?/cp/foo/bar',
			'prev' => 'index.php?/cp/foo/bar&p=1',
			'next' => 'index.php?/cp/foo/bar&p=3',
			'last' => 'index.php?/cp/foo/bar&p=10',
			'pages' => array(
				'1' => 'index.php?/cp/foo/bar&p=1',
				'2' => 'index.php?/cp/foo/bar&p=2',
				'3' => 'index.php?/cp/foo/bar&p=3',
				'4' => 'index.php?/cp/foo/bar&p=4',
				'5' => 'index.php?/cp/foo/bar&p=5',
				'6' => 'index.php?/cp/foo/bar&p=6',
				'7' => 'index.php?/cp/foo/bar&p=7',
				'8' => 'index.php?/cp/foo/bar&p=8',
				'9' => 'index.php?/cp/foo/bar&p=9',
				'10' => 'index.php?/cp/foo/bar&p=10',
			)
		);

		$return[] = array('"p" for the page_variable', 10, 100, 2, $url, 10, 'p', $expected);

		// Testing where we want to show more pages than available
		$expected = array(
			'total_count' => 30,
			'current_page' => 2,
			'first' => 'index.php?/cp/foo/bar',
			'prev' => 'index.php?/cp/foo/bar&p=1',
			'next' => 'index.php?/cp/foo/bar&p=3',
			'last' => 'index.php?/cp/foo/bar&p=3',
			'pages' => array(
				'1' => 'index.php?/cp/foo/bar&p=1',
				'2' => 'index.php?/cp/foo/bar&p=2',
				'3' => 'index.php?/cp/foo/bar&p=3',
			)
		);

		$return[] = array('"p" for the page_variable', 10, 30, 2, $url, 4, 'p', $expected);

		return $return;
	}
}
