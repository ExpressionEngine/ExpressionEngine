<?php

namespace EllisLab\ExpressionEngine\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamTraining extends Model {

	protected static $_primary_key = 'vocabulary_id';

	protected $training_id;
	protected $kernel_id;
	protected $source;
	protected $type;
	protected $class;

}
