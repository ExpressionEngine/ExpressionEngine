<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Member\Display;

use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;

/**
 * Member Field Layout
 */
class MemberFieldLayout extends DefaultLayout {

	public function transform(array $fields)
	{
		usort($fields, function($a, $b) {
			return $a->get('m_field_order') > $b->get('m_field_order');
		});

		return parent::transform($fields);
	}
}

// EOF
