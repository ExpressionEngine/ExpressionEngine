<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Category\Display;

use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;

/**
 * Category Field Layout
 */
class CategoryFieldLayout extends DefaultLayout {

	public function transform(array $fields)
	{
		usort($fields, function($a, $b) {
			return $a->get('field_order') > $b->get('field_order');
		});

		return parent::transform($fields);
	}
}
