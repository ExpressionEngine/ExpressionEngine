<?php

namespace EllisLab\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamParameter extends Model {

	protected static $_table_name = 'spam_parameters';
	protected static $_primary_key = 'parameter_id';

	protected static $_relationships = array(
		'Kernel' => array(
			'type' => 'belongsTo',
			'model' => 'SpamKernel',
			'to_key' => 'kernel_id'
		)
	);

	protected $parameter_id;
	protected $kernel_id;
	protected $index;
	protected $term;
	protected $class;
	protected $mean;
	protected $variance;

}

// EOF
