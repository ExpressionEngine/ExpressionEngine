<?php

namespace EllisLab\ExpressionEngine\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamParameter extends Model {

	protected static $_primary_key = 'parameter_id';

	protected $parameter_id;
	protected $kernel_id;
	protected $term;
	protected $class;
	protected $mean;
	protected $variance;

}
