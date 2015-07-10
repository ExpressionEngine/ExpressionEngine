<?php

namespace EllisLab\ExpressionEngine\Model\Category\Display;

use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;

class CategoryFieldLayout extends DefaultLayout {

	public function transform(array $fields)
	{
		usort($fields, function($a, $b) {
			return $a->get('field_order') > $b->get('field_order');
		});

		return parent::transform($fields);
	}
}