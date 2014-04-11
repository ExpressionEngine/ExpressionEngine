<?php
namespace EllisLab\Tests\ExpressionEngine\Library\CP;

use EllisLab\ExpressionEngine\Library\CP\URL;

class URLTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider dataProvider
	 */
	public function testWithCompile($description, $cpUrl, $expected_out)
	{
		$this->assertEquals($expected_out, $cpUrl->compile(), $description);
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testWithToString($description, $cpUrl, $expected_out)
	{
		$this->assertEquals($expected_out, (string) $cpUrl, $description);
	}

	public function dataProvider()
	{
		// Assemble the tests
		return array_merge(
			array(),

			$this->URLsByConstructor(),
			$this->URLsBySetters()
		);
	}

	public function URLsByConstructor()
	{
		$session_id = '7594769f19d82af2af30c72fee7e4183';
		return array(
			array('Constructor URL',							new URL('foo/bar'),														'index.php?/cp/foo/bar'),
			array('Constructor URL with Session',				new URL('foo/bar', $session_id),										'index.php?/cp/foo/bar?S='.$session_id),
			array('Constructor URL with one QS',				new URL('foo/bar', '', array('sort' => 'asc')),							'index.php?/cp/foo/bar?sort=asc'),
			array('Constructor URL with many QS',				new URL('foo/bar', '', array('sort' => 'asc', 'limit' => 25)),			'index.php?/cp/foo/bar?sort=asc&limit=25'),
			array('Constructor URL with Session and one QS',	new URL('foo/bar', $session_id, array('sort' => 'asc')),				'index.php?/cp/foo/bar?sort=asc&S='.$session_id),
			array('Constructor URL with Session and many QS',	new URL('foo/bar', $session_id, array('sort' => 'asc', 'limit' => 25)),	'index.php?/cp/foo/bar?sort=asc&limit=25&S='.$session_id),
		);
	}

	public function URLsBySetters()
	{
		$session_id = '7594769f19d82af2af30c72fee7e4183';

		$url_with_session = new URL('foo/bar');
		$url_with_session->session_id = $session_id;

		$url_with_one_qs = new URL('foo/bar');
		$url_with_one_qs->setQueryStringVariable('sort', 'asc');

		$url_with_many_qs = new URL('foo/bar');
		$url_with_many_qs->setQueryStringVariable('sort', 'asc');
		$url_with_many_qs->setQueryStringVariable('limit', 25);

		$url_with_session_and_one_qs = new URL('foo/bar');
		$url_with_session_and_one_qs->setQueryStringVariable('sort', 'asc');
		$url_with_session_and_one_qs->session_id = $session_id;

		$url_with_session_and_many_qs = new URL('foo/bar');
		$url_with_session_and_many_qs->setQueryStringVariable('sort', 'asc');
		$url_with_session_and_many_qs->setQueryStringVariable('limit', 25);
		$url_with_session_and_many_qs->session_id = $session_id;

		return array(
			array('Setter URL with Session',				$url_with_session,				'index.php?/cp/foo/bar?S='.$session_id),
			array('Setter URL with one QS',					$url_with_one_qs,				'index.php?/cp/foo/bar?sort=asc'),
			array('Setter URL with many QS',				$url_with_many_qs,				'index.php?/cp/foo/bar?sort=asc&limit=25'),
			array('Setter URL with Session and one QS',		$url_with_session_and_one_qs,	'index.php?/cp/foo/bar?sort=asc&S='.$session_id),
			array('Setter URL with Session and many QS',	$url_with_session_and_many_qs,	'index.php?/cp/foo/bar?sort=asc&limit=25&S='.$session_id),
		);
	}
}