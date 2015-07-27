<?php

namespace EllisLab\ExpressionEngine\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamKernel extends Model {

	protected static $_primary_key = 'kernel_id';

	protected $kernel_id;
	protected $name;

}
