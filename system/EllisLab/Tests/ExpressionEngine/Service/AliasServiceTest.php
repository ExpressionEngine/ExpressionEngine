<?php
namespace EllisLab\Tests\ExpressionEngine\Service;

class AliasServiceTest extends \PHPUnit_Framework_TestCase {

	// not sure this test should be here
	public function testVerifyDefaultModelAliases()
	{

		$model_alias_path = APPPATH . 'config/model_aliases.php';
		$aliases = include $model_alias_path;

		foreach ($aliases as $alias => $class)
		{
			$this->assertTrue(class_exists($class), 'Does not exists: '.$class);
		}
	}
}