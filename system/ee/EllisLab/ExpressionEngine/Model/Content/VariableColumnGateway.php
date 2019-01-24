<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Content;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * Content Variable Column Gateway
 */
class VariableColumnGateway extends Gateway {

	/**
	 *
	 */
	public function getFieldList($cached = TRUE)
	{
		if ($cached && isset($this->_field_list_cache))
		{
			return $this->_field_list_cache;
		}

		$all = ee('Database')
			->newQuery()
			->list_fields($this->getTableName());

		$known = parent::getFieldList();

		return $this->_field_list_cache = array_merge($known, $all);
	}

}

// EOF
