<?php
namespace EllisLab\ExpressionEngine\Core;

use EllisLab\ExpressionEngine\Service\Validation\ValidationService as ValidationService;
use EllisLab\ExpressionEngine\Model\QueryBuilder as QueryBuilder;

class Dependencies {
	protected $validation_service = NULL;
	protected $query_builder = NULL;

	public function getValidationService() 
	{
		if ( ! isset($this->validation_service))
		{
			$this->validation_service = new ValidationService();
		}
	
		return $this->validation_service;
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
