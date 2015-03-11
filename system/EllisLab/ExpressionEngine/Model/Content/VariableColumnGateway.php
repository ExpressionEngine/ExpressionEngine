<?php

namespace EllisLab\ExpressionEngine\Model\Content;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class VariableColumnGateway extends Gateway {

	protected $_field_list_cache;

	/**
	 *
	 */
	public function getFieldList()
	{
		if ( ! isset($this->_field_list_cache))
		{
			$all = ee('Database')
				->newQuery()
				->list_fields($this->getTableName());

			$known = parent::getFieldList();

			$this->_field_list_cache = array_merge($known, $all);
		}

		return $this->_field_list_cache;
	}

}