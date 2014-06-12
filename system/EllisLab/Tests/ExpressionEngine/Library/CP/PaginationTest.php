<?php
namespace EllisLab\Tests\ExpressionEngine\Library\CP;

use EllisLab\ExpressionEngine\Library\CP\Pagination;

class PaginationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Test the constructor for things that should fail
	 *
	 * @dataProvider badConstructorDataProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testConstructor($description, $per_page, $total_count, $current_page)
	{
		new Pagination($per_page, $total_count, $current_page);
	}

	/**
	 * Test the create method for things that should fail
	 *
	 * @dataProvider badConstructorDataProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testCreate($description, $per_page, $total_count, $current_page)
	{
		Pagination::create($per_page, $total_count, $current_page);
	}

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
			array("Zero total_count",		1, 0, 1),
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
	public function testBadCpLinks($description, $base_url, $pages, $page_variable)
	{
		$pagination = new Pagination(10, 100, 1);
		$pagination->cp_links($base_url, $pages, $page_variable);
	}

	public function badCpLinksDataProvider()
	{
		$url = new \EllisLab\ExpressionEngine\Library\CP\Url('foo/bar');
		$obj = new \stdClass;

		return array(
			array('Int for $base_url',		1,			3, 'page'),
			array('Float for $base_url',	1.1,		3, 'page'),
			array('Array for $base_url',	array(1),	3, 'page'),
			array('String for $base_url',	"foo/bar",	3, 'page'),
			array('Boolean for $base_url',	FALSE,		3, 'page'),
			array('stdClass for $base_url',	$obj,		3, 'page'),

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
}