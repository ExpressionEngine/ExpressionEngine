<?php
namespace EllisLab\ExpressionEngine\Core;

use EllisLab\ExpressionEngine\Core\Validation\Validation as Validation;
use EllisLab\ExpressionEngine\Model\Query\QueryBuilder as QueryBuilder;

class Dependencies {
	protected $validation = NULL;
	protected $query_builder = NULL;

	public function __construct(Dependencies $di = NULL)
	{
		if ( $di !== NULL)
		{
			$this->validation = $di->validation;
			$this->query_builder = $di->query_builder;
		}
	}

	public function getValidation() 
	{
		if ( ! isset($this->validation))
		{
			$this->validation = new Validation($this);
		}
	
		return $this->validation;
	}

	public function getQueryBuilder()
	{
		if ( ! isset($this->query_builder))
		{
			$this->query_builder = new QueryBuilder($this);
		}
		
		return $this->query_builder;
	}

}
