<?php

namespace EllisLab\ExpressionEngine\Model\Member\Display;

use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;

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
