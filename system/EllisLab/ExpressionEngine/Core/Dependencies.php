<?php
namespace EllisLab\ExpressionEngine\Core;

use EllisLab\ExpressionEngine\Core\Validation\Validation as Validation;
use EllisLab\ExpressionEngine\Model\QueryBuilder as QueryBuilder;

class Dependencies {
	protected $validation = NULL;
	protected $query_builder = NULL;

	public function __construct(Dependencies $di)
	{
		$this->validation = $di->validation;
		$this->query_builder = $di->query_builder;
	}

	public function getValidation() 
	{
		if ( ! isset($this->validation))
		{
			$this->validation = new Validation();
		}
	
		return $this->validation;
	}

	public function getQueryBuilder()
	{
		if ( ! isset($this->query_builder))
		{
			$this->query_builder = new QueryBuilder();
		}
		
		return $this->query_builder;
	}

}
