<?php

namespace EllisLab\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamTraining extends Model {

	protected static $_table_name = 'spam_training';
	protected static $_primary_key = 'training_id';

	protected static $_relationships = array(
		'Kernel' => array(
			'type' => 'belongsTo',
			'model' => 'SpamKernel',
			'to_key' => 'kernel_id'
		)
	);

	protected $training_id;
	protected $kernel_id;
	protected $source;
	protected $type;
	protected $class;

}
